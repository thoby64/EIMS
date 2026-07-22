<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetInspection;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetInspectionController extends Controller
{
    public function index(Request $r): View
    {
        $q = AssetInspection::with(['asset.category', 'inspector'])
            ->when($r->filled('status'), fn ($x) => $x->where('status', $r->string('status')))
            ->when($r->filled('search'), fn ($x) => $x->where(fn ($search) => $search
                ->where('inspection_number', 'like', '%'.$r->string('search').'%')
                ->orWhereHas('asset', fn ($asset) => $asset->where('name', 'like', '%'.$r->string('search').'%')->orWhere('asset_tag', 'like', '%'.$r->string('search').'%'))
                ->orWhereHas('inspector', fn ($user) => $user->where('name', 'like', '%'.$r->string('search').'%'))));
        if (! $r->user()->hasPermission('inspections.manage')) {
            $q->where('inspector_user_id', $r->user()->id);
        }

        return view('inspections.index', ['inspections' => $q->latest('scheduled_at')->paginate(15)->withQueryString()]);
    }

    public function create(Request $r): View
    {
        return view('inspections.create', ['assets' => Asset::with('category')->whereNotIn('lifecycle_status', ['disposed', 'retired'])->orderBy('name')->get(), 'inspectors' => User::where('status', 'active')->whereHas('roles.permissions', fn ($q) => $q->where('slug', 'inspections.manage'))->orderBy('name')->get(), 'selectedAsset' => $r->filled('asset') ? Asset::where('public_id', $r->string('asset'))->first() : null]);
    }

    public function store(Request $r): RedirectResponse
    {
        $v = $r->validate(['asset_id' => ['required', 'exists:assets,id'], 'inspector_user_id' => ['required', 'exists:users,id'], 'scheduled_at' => ['required', 'date', 'after_or_equal:today']]);
        $inspector = User::findOrFail($v['inspector_user_id']);
        if (! $inspector->hasPermission('inspections.manage')) {
            throw ValidationException::withMessages(['inspector_user_id' => 'Select an officer authorized to conduct inspections.']);
        }$inspection = AssetInspection::create([...$v, 'public_id' => (string) Str::ulid(), 'inspection_number' => 'INS-'.now()->format('Y').'-'.Str::upper(Str::random(8)), 'scheduled_by_user_id' => $r->user()->id, 'status' => 'scheduled']);
        $inspector->notify(new WorkflowNotification('Asset inspection scheduled', $inspection->asset->asset_tag.' is scheduled for inspection.', route('inspections.show', $inspection)));

        return redirect()->route('inspections.show', $inspection)->with('success', 'Inspection scheduled and the officer notified.');
    }

    public function show(Request $r, AssetInspection $inspection): View
    {
        $inspection->load(['asset.category.group', 'asset.location', 'asset.custodian', 'asset.custodianDepartment', 'inspector', 'scheduler']);
        abort_unless($r->user()->hasPermission('inspections.manage') || $inspection->inspector_user_id === $r->user()->id, 403);

        return view('inspections.show', compact('inspection'));
    }

    public function complete(Request $r, AssetInspection $inspection): RedirectResponse
    {
        abort_unless($inspection->inspector_user_id === $r->user()->id, 403);
        $v = $r->validate(['physical_status' => ['required', Rule::in(['present', 'missing', 'inaccessible'])], 'location_verified' => ['required', 'boolean'], 'custody_verified' => ['required', 'boolean'], 'condition_assessed' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])], 'findings' => ['required', 'string', 'max:4000'], 'recommendation' => ['required', Rule::in(['none', 'maintenance', 'custody_correction', 'transfer_or_return', 'retirement_assessment', 'missing_asset_investigation'])], 'recommendation_notes' => [Rule::requiredIf(fn () => $r->input('recommendation') !== 'none'), 'nullable', 'string', 'max:3000']]);
        DB::transaction(function () use ($inspection, $r, $v) {
            $locked = AssetInspection::lockForUpdate()->findOrFail($inspection->id);
            if ($locked->status !== 'scheduled') {
                throw ValidationException::withMessages(['inspection' => 'This inspection has already been completed.']);
            }$locked->update([...$v, 'status' => 'completed', 'performed_at' => now(), 'followup_status' => $v['recommendation'] === 'none' ? 'not_required' : 'required']);
            $locked->asset->events()->create(['actor_user_id' => $r->user()->id, 'event_type' => 'inspection_completed', 'summary' => 'Inspection '.$locked->inspection_number.' completed', 'after' => ['physical_status' => $v['physical_status'], 'condition_assessed' => $v['condition_assessed'], 'recommendation' => $v['recommendation']], 'occurred_at' => now()]);
        });

        return back()->with('success', 'Inspection recorded. Any recommended change must follow its controlled workflow.');
    }
}
