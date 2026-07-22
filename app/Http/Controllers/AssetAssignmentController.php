<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Department;
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

class AssetAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = AssetAssignment::query()
            ->with(['asset.category', 'assignee', 'department', 'assigner', 'location', 'receipt'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search
                ->whereHas('asset', fn ($asset) => $asset->where('name', 'like', '%'.$request->string('search').'%')->orWhere('asset_tag', 'like', '%'.$request->string('search').'%'))
                ->orWhereHas('assignee', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%'))
                ->orWhereHas('department', fn ($department) => $department->where('name', 'like', '%'.$request->string('search').'%'))))
            ->when($request->filled('type'), fn ($query) => $query->where('assignment_type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('assigned_at')
            ->paginate(15)
            ->withQueryString();

        return view('assignments.index', compact('assignments'));
    }

    public function create(Request $request): View
    {
        return view('assignments.create', [
            'selectedAsset' => $request->filled('asset') ? Asset::where('public_id', $request->string('asset'))->firstOrFail() : null,
            'assets' => Asset::query()->with('category')->where('lifecycle_status', 'in_stock')->orderBy('name')->get(),
            'users' => User::where('status', 'active')->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'assignment_type' => ['required', Rule::in(['individual', 'department'])],
            'assigned_to_user_id' => [Rule::requiredIf(fn () => $request->input('assignment_type') === 'individual'), 'nullable', Rule::exists('users', 'id')->where('status', 'active')],
            'assigned_to_department_id' => [Rule::requiredIf(fn () => $request->input('assignment_type') === 'department'), 'nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'authorized_receiver_ids' => [Rule::requiredIf(fn () => $request->input('assignment_type') === 'department'), 'nullable', 'array', 'min:1'],
            'authorized_receiver_ids.*' => ['integer', Rule::exists('users', 'id')->where('status', 'active')],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'expected_return_at' => ['nullable', 'date', 'after:today'],
            'condition_at_issue' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'purpose' => ['required', 'string', 'max:500'],
            'accessories' => ['nullable', 'array', 'max:20'],
            'accessories.*' => ['nullable', 'string', 'max:100', 'regex:/^[\pL\pN ]+$/u'],
            'issue_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $accessories = collect($validated['accessories'] ?? [])->filter()->map(fn ($item) => trim($item))->unique()->values()->all();
        if ($validated['assignment_type'] === 'department') {
            $validReceivers = User::where('department_id', $validated['assigned_to_department_id'])->whereIn('id', $validated['authorized_receiver_ids'])->count();
            if ($validReceivers !== count(array_unique($validated['authorized_receiver_ids']))) {
                throw ValidationException::withMessages(['authorized_receiver_ids' => 'Every authorized receiver must belong to the selected department.']);
            }
        }

        $assignment = DB::transaction(function () use ($validated, $accessories, $request) {
            $asset = Asset::query()->lockForUpdate()->findOrFail($validated['asset_id']);
            if ($asset->lifecycle_status !== 'in_stock' || $asset->assignments()->whereNotNull('active_asset_id')->exists()) {
                throw ValidationException::withMessages(['asset_id' => 'This asset is no longer available for assignment.']);
            }

            $assignment = $asset->assignments()->create([
                ...collect($validated)->except(['asset_id', 'accessories', 'authorized_receiver_ids'])->all(),
                'public_id' => (string) Str::ulid(),
                'active_asset_id' => $asset->id,
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
                'accessories' => $accessories ?: null,
                'status' => 'pending_receipt',
            ]);

            if ($validated['assignment_type'] === 'department') {
                $assignment->authorizedReceivers()->attach(array_unique($validated['authorized_receiver_ids']));
            }

            $asset->update(['lifecycle_status' => 'reserved']);
            $asset->events()->create([
                'actor_user_id' => $request->user()->id,
                'event_type' => 'assignment_prepared',
                'summary' => 'Asset reserved for '.($assignment->assignment_type === 'department' ? $assignment->department->name : $assignment->assignee->name),
                'before' => ['lifecycle_status' => 'in_stock'],
                'after' => ['lifecycle_status' => 'reserved', 'assignment_id' => $assignment->public_id],
                'occurred_at' => now(),
            ]);

            $recipients = $assignment->assignment_type === 'department' ? $assignment->authorizedReceivers : collect([$assignment->assignee]);
            $recipients->each->notify(new WorkflowNotification('Asset handover awaiting confirmation', $asset->asset_tag.' has been allocated for your confirmation.', route('handovers.pending')));

            return $assignment;
        }, 3);

        return redirect()->route('assignments.show', $assignment)->with('success', 'Assignment prepared. The recipient must now confirm the handover.');
    }

    public function show(AssetAssignment $assignment): View
    {
        $assignment->load(['asset.category.group', 'assignee', 'department', 'authorizedReceivers', 'assigner', 'location', 'receipt.handedOverBy', 'receipt.confirmedBy']);

        return view('assignments.show', compact('assignment'));
    }
}
