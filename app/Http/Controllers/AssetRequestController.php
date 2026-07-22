<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRequest;
use App\Models\Location;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetRequestController extends Controller
{
    public function index(Request $r): View
    {
        $u = $r->user();
        $requests = AssetRequest::with(['category', 'requester', 'assignment.asset'])->when(! $u->hasPermission('requests.approve'), fn ($q) => $q->where('requested_by_user_id', $u->id))->when($r->filled('search'), fn ($q) => $q->where(fn ($search) => $search->where('request_number', 'like', '%'.$r->string('search').'%')->orWhere('purpose', 'like', '%'.$r->string('search').'%')->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$r->string('search').'%'))->orWhereHas('requester', fn ($user) => $user->where('name', 'like', '%'.$r->string('search').'%'))))->when($r->filled('status'), fn ($q) => $q->where('status', $r->string('status')))->when($r->filled('category'), fn ($q) => $q->where('asset_category_id', $r->integer('category')))->latest('submitted_at')->paginate(15)->withQueryString();

        return view('requests.index', ['requests' => $requests, 'categories' => AssetCategory::where('is_active', true)->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('requests.create', ['categories' => AssetCategory::with('group')->where('is_active', 1)->orderBy('name')->get(), 'locations' => Location::where('is_active', 1)->orderBy('name')->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $v = $r->validate(['asset_category_id' => ['required', 'exists:asset_categories,id'], 'preferred_location_id' => ['nullable', 'exists:locations,id'], 'purpose' => ['required', 'string', 'max:500'], 'justification' => ['required', 'string', 'max:3000'], 'properties' => ['nullable', 'array', 'max:20'], 'properties.*.name' => ['required_with:properties.*.value', 'nullable', 'regex:/^[\pL\pN ]+$/u', 'max:100'], 'properties.*.value' => ['required_with:properties.*.name', 'nullable', 'regex:/^[\pL\pN ]+$/u', 'max:255']]);
        $props = collect($v['properties'] ?? [])->filter(fn ($p) => filled($p['name'] ?? null))->map(fn ($p) => ['name' => trim($p['name']), 'key' => Str::slug($p['name'], '_'), 'value' => trim($p['value'])]);
        if ($props->pluck('key')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['properties' => 'Requested property names must be unique.']);
        }$request = DB::transaction(function () use ($v, $props, $r) {
            $request = AssetRequest::create([...collect($v)->except('properties')->all(), 'public_id' => (string) Str::ulid(), 'request_number' => 'AR-'.now()->format('Y').'-'.Str::upper(Str::random(8)), 'requested_by_user_id' => $r->user()->id, 'status' => 'submitted', 'submitted_at' => now()]);
            $request->properties()->createMany($props->values()->all());

            return $request;
        });
        $this->procurement()->each->notify(new WorkflowNotification('New asset request', $request->request_number.' awaits a Procurement decision.', route('requests.show', $request)));

        return redirect()->route('requests.show', $request)->with('success', 'Asset request submitted to Procurement.');
    }

    public function show(Request $r, AssetRequest $assetRequest): View
    {
        abort_unless($assetRequest->requested_by_user_id === $r->user()->id || $r->user()->hasPermission('requests.approve'), 403);
        $assetRequest->load(['category.group', 'location', 'requester', 'properties', 'assignment.asset']);
        $available = Asset::where('asset_category_id', $assetRequest->asset_category_id)->where('lifecycle_status', 'in_stock')->get();

        return view('requests.show', ['assetRequest' => $assetRequest, 'availableAssets' => $available]);
    }

    public function decide(Request $r, AssetRequest $assetRequest): RedirectResponse
    {
        abort_unless($r->user()->hasPermission('requests.approve'), 403);
        $v = $r->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'comments' => ['required', 'string', 'max:2000']]);
        if ($assetRequest->status !== 'submitted') {
            throw ValidationException::withMessages(['decision' => 'This request has already been decided.']);
        }$assetRequest->update(['status' => $v['decision'], 'decided_by_user_id' => $r->user()->id, 'decision_comments' => $v['comments'], 'decided_at' => now()]);
        $assetRequest->requester->notify(new WorkflowNotification('Asset request '.ucfirst($v['decision']), $v['comments'], route('requests.show', $assetRequest)));

        return back()->with('success', 'Decision submitted to the requesting officer.');
    }

    public function allocate(Request $r, AssetRequest $assetRequest): RedirectResponse
    {
        abort_unless($r->user()->hasPermission('requests.allocate'), 403);
        $v = $r->validate(['asset_id' => ['required', 'exists:assets,id']]);
        DB::transaction(function () use ($assetRequest, $v, $r) {
            $request = AssetRequest::lockForUpdate()->findOrFail($assetRequest->id);
            if ($request->status !== 'approved' || $request->assignment()->exists()) {
                throw ValidationException::withMessages(['asset_id' => 'This request is not available for allocation.']);
            }$asset = Asset::lockForUpdate()->findOrFail($v['asset_id']);
            if ($asset->asset_category_id !== $request->asset_category_id || $asset->lifecycle_status !== 'in_stock') {
                throw ValidationException::withMessages(['asset_id' => 'Select an available asset from the requested category.']);
            }$assignment = $asset->assignments()->create(['public_id' => (string) Str::ulid(), 'active_asset_id' => $asset->id, 'asset_request_id' => $request->id, 'assigned_to_user_id' => $request->requested_by_user_id, 'location_id' => $request->preferred_location_id ?: $request->requester->primary_location_id, 'assigned_by' => $r->user()->id, 'assigned_at' => now(), 'condition_at_issue' => $asset->condition, 'purpose' => $request->purpose, 'status' => 'pending_receipt']);
            $asset->update(['lifecycle_status' => 'reserved']);
            $request->update(['status' => 'allocated']);
            $request->requester->notify(new WorkflowNotification('Requested asset allocated', $asset->asset_tag.' is ready for handover confirmation.', route('handovers.confirm', $assignment)));
        });

        return back()->with('success', 'Asset allocated. The requester must confirm handover receipt.');
    }

    private function procurement()
    {
        return User::where('status','active')->whereHas('roles.permissions',fn ($q) => $q->where('permissions.slug','requests.approve'))->get();
    }
}
