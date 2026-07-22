<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetMovement;
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

class AssetMovementController extends Controller
{
    public function index(Request $request): View
    {
        $query = AssetMovement::with(['asset.category', 'initiator', 'targetUser', 'targetDepartment', 'targetLocation', 'receivers']);
        if (! $request->user()->hasPermission('assets.transfer')) {
            $query->where(fn ($q) => $q->where('initiated_by_user_id', $request->user()->id)->orWhereHas('receivers', fn ($r) => $r->where('users.id', $request->user()->id)));
        }
        $query->when($request->filled('search'), fn ($q) => $q->where(fn ($search) => $search
            ->where('movement_number', 'like', '%'.$request->string('search').'%')
            ->orWhereHas('asset', fn ($asset) => $asset->where('name', 'like', '%'.$request->string('search').'%')->orWhere('asset_tag', 'like', '%'.$request->string('search').'%'))))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return view('movements.index', ['movements' => $query->latest()->paginate(15)->withQueryString()]);
    }

    public function create(Request $request, Asset $asset): View
    {
        $assignment = $this->activeAssignment($asset);
        $this->authorizeCustodianOrProcurement($request, $assignment);

        return view('movements.create', [
            'asset' => $asset->load('category'), 'assignment' => $assignment,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'users' => User::with('department')->where('status', 'active')->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $assignment = $this->activeAssignment($asset);
        $this->authorizeCustodianOrProcurement($request, $assignment);
        $rules = [
            'type' => ['required', Rule::in(['return', 'transfer'])],
            'target_type' => [Rule::requiredIf(fn () => $request->input('type') === 'transfer'), 'nullable', Rule::in(['individual', 'department'])],
            'target_department_id' => [Rule::requiredIf(fn () => $request->input('type') === 'transfer'), 'nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'target_user_id' => [Rule::requiredIf(fn () => $request->input('type') === 'transfer' && $request->input('target_type') === 'individual'), 'nullable', Rule::exists('users', 'id')->where('status', 'active')],
            'receiver_ids' => [Rule::requiredIf(fn () => $request->input('type') === 'transfer' && $request->input('target_type') === 'department'), 'nullable', 'array', 'min:1'],
            'receiver_ids.*' => ['integer', Rule::exists('users', 'id')->where('status', 'active')],
            'target_location_id' => [Rule::requiredIf(fn () => $request->input('type') === 'transfer'), 'nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'max:2000'],
            'condition_reported' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
        ];
        $v = $request->validate($rules);
        if ($v['type'] === 'transfer') {
            $ids = $v['target_type'] === 'individual' ? [$v['target_user_id']] : array_unique($v['receiver_ids']);
            $valid = User::where('department_id', $v['target_department_id'])->whereIn('id', $ids)->count();
            if ($valid !== count($ids)) {
                throw ValidationException::withMessages(['receiver_ids' => 'Every receiving officer must belong to the selected destination department.']);
            }
        }
        $movement = DB::transaction(function () use ($request, $asset, $assignment, $v) {
            $locked = AssetAssignment::lockForUpdate()->findOrFail($assignment->id);
            if ($locked->status !== 'active' || ! $locked->active_asset_id || AssetMovement::where('from_assignment_id', $locked->id)->whereIn('status', ['pending_procurement', 'awaiting_receipt'])->exists()) {
                throw ValidationException::withMessages(['type' => 'This asset already has a movement in progress.']);
            }
            $procurementInitiated = $request->user()->hasPermission('assets.transfer');
            $movement = AssetMovement::create([
                ...collect($v)->except('receiver_ids')->all(), 'public_id' => (string) Str::ulid(),
                'movement_number' => 'MOV-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
                'asset_id' => $asset->id, 'from_assignment_id' => $locked->id, 'initiated_by_user_id' => $request->user()->id,
                'status' => $procurementInitiated && $v['type'] === 'transfer' ? 'awaiting_receipt' : 'pending_procurement',
                'decided_by_user_id' => $procurementInitiated && $v['type'] === 'transfer' ? $request->user()->id : null,
                'decision_comments' => $procurementInitiated && $v['type'] === 'transfer' ? 'Transfer initiated by Procurement.' : null,
                'decided_at' => $procurementInitiated && $v['type'] === 'transfer' ? now() : null,
            ]);
            if ($v['type'] === 'transfer') {
                $ids = $v['target_type'] === 'individual' ? [$v['target_user_id']] : array_unique($v['receiver_ids']);
                $movement->receivers()->attach($ids);
            }
            $asset->events()->create(['actor_user_id' => $request->user()->id, 'event_type' => $v['type'].'_requested', 'summary' => ucfirst($v['type']).' requested: '.$v['reason'], 'after' => ['movement' => $movement->movement_number], 'occurred_at' => now()]);
            if ($movement->status === 'awaiting_receipt') {
                $this->notifyReceivers($movement);
            } else {
                $this->procurementUsers()->each->notify(new WorkflowNotification('Asset movement awaiting approval', $movement->movement_number.' requires a Procurement decision.', route('movements.show', $movement)));
            }

            return $movement;
        }, 3);

        return redirect()->route('movements.show', $movement)->with('success', $movement->status === 'awaiting_receipt' ? 'Transfer prepared and sent to the recipient for confirmation.' : 'Movement request submitted to Procurement.');
    }

    public function show(Request $request, AssetMovement $movement): View
    {
        $movement->load(['asset.category', 'assignment.assignee', 'assignment.department', 'initiator', 'targetUser.department', 'targetDepartment', 'targetLocation', 'receivers', 'decidedBy', 'confirmedBy']);
        $allowed = $request->user()->hasPermission('assets.transfer') || $movement->initiated_by_user_id === $request->user()->id || $movement->receivers->contains($request->user());
        abort_unless($allowed, 403);

        return view('movements.show', compact('movement'));
    }

    public function decide(Request $request, AssetMovement $movement): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('assets.transfer'), 403);
        $v = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'comments' => ['required', 'string', 'max:2000'], 'condition_confirmed' => [Rule::requiredIf(fn () => $request->input('decision') === 'approved' && $movement->type === 'return'), 'nullable', Rule::in(['excellent', 'good', 'fair', 'damaged'])]]);
        DB::transaction(function () use ($request, $movement, $v) {
            $movement = AssetMovement::lockForUpdate()->findOrFail($movement->id);
            if ($movement->status !== 'pending_procurement') {
                throw ValidationException::withMessages(['decision' => 'This movement has already been decided.']);
            }
            if ($v['decision'] === 'rejected') {
                $movement->update(['status' => 'rejected', 'decided_by_user_id' => $request->user()->id, 'decision_comments' => $v['comments'], 'decided_at' => now()]);
                $movement->initiator->notify(new WorkflowNotification('Asset movement rejected', $v['comments'], route('movements.show', $movement)));

                return;
            }
            if ($movement->type === 'return') {
                $this->completeReturn($movement, $request->user(), $v['condition_confirmed'], $v['comments']);

                return;
            }
            $movement->update(['status' => 'awaiting_receipt', 'decided_by_user_id' => $request->user()->id, 'decision_comments' => $v['comments'], 'decided_at' => now()]);
            $movement->initiator->notify(new WorkflowNotification('Asset transfer approved', 'Procurement approved the transfer. Proceed with physical handover.', route('movements.show', $movement)));
            $this->notifyReceivers($movement);
        }, 3);

        return back()->with('success', $v['decision'] === 'rejected' ? 'Movement rejected and the custodian notified.' : ($movement->type === 'return' ? 'Return accepted and custody closed.' : 'Transfer approved and sent for recipient confirmation.'));
    }

    public function confirm(Request $request, AssetMovement $movement): RedirectResponse
    {
        $v = $request->validate(['decision' => ['required', Rule::in(['accepted', 'rejected'])], 'condition_confirmed' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])], 'comments' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($request, $movement, $v) {
            $movement = AssetMovement::with('receivers')->lockForUpdate()->findOrFail($movement->id);
            abort_unless($movement->receivers->contains($request->user()), 403);
            if ($movement->status !== 'awaiting_receipt') {
                throw ValidationException::withMessages(['decision' => 'This transfer is no longer awaiting receipt.']);
            }
            if ($v['decision'] === 'rejected') {
                $movement->update(['status' => 'receipt_rejected', 'confirmed_by_user_id' => $request->user()->id, 'condition_confirmed' => $v['condition_confirmed'], 'confirmation_comments' => $v['comments'], 'confirmed_at' => now()]);
                $movement->receivers()->updateExistingPivot($request->user()->id, ['status' => 'rejected', 'confirmed_at' => now()]);
                $this->notifyProcurementAndInitiator($movement, 'Transfer receipt rejected', $v['comments']);

                return;
            }
            $this->completeTransfer($movement, $request->user(), $v['condition_confirmed'], $v['comments']);
        }, 3);

        return back()->with('success', $v['decision'] === 'accepted' ? 'Receipt confirmed. Custody has been transferred to the destination.' : 'Receipt rejected. Existing custody remains unchanged.');
    }

    private function completeTransfer(AssetMovement $movement, User $receiver, string $condition, string $comments): void
    {
        $old = AssetAssignment::lockForUpdate()->findOrFail($movement->from_assignment_id);
        $asset = Asset::lockForUpdate()->findOrFail($movement->asset_id);
        if ($old->status !== 'active' || ! $old->active_asset_id) {
            throw ValidationException::withMessages(['decision' => 'The source custody is no longer active.']);
        }
        $old->update(['status' => 'returned', 'returned_at' => now(), 'condition_at_return' => $condition, 'return_notes' => 'Transferred through '.$movement->movement_number, 'active_asset_id' => null]);
        $new = $asset->assignments()->create(['public_id' => (string) Str::ulid(), 'active_asset_id' => $asset->id, 'assignment_type' => $movement->target_type, 'assigned_to_user_id' => $movement->target_type === 'individual' ? $movement->target_user_id : null, 'assigned_to_department_id' => $movement->target_type === 'department' ? $movement->target_department_id : null, 'location_id' => $movement->target_location_id, 'assigned_by' => $movement->decided_by_user_id, 'assigned_at' => now(), 'condition_at_issue' => $condition, 'purpose' => 'Approved transfer '.$movement->movement_number, 'issue_notes' => $movement->reason, 'status' => 'active']);
        if ($movement->target_type === 'department') {
            $new->authorizedReceivers()->attach($movement->receivers->pluck('id')->all(), ['status' => 'closed']);
        }
        $asset->update(['custodian_user_id' => $movement->target_type === 'individual' ? $movement->target_user_id : null, 'custodian_department_id' => $movement->target_type === 'department' ? $movement->target_department_id : null, 'location_id' => $movement->target_location_id, 'condition' => $condition, 'lifecycle_status' => 'assigned']);
        $movement->update(['status' => 'completed', 'confirmed_by_user_id' => $receiver->id, 'condition_confirmed' => $condition, 'confirmation_comments' => $comments, 'confirmed_at' => now()]);
        DB::table('asset_movement_receivers')->where('asset_movement_id', $movement->id)->update(['status' => 'closed', 'updated_at' => now()]);
        DB::table('asset_movement_receivers')->where('asset_movement_id', $movement->id)->where('user_id', $receiver->id)->update(['status' => 'confirmed', 'confirmed_at' => now(), 'updated_at' => now()]);
        $asset->events()->create(['actor_user_id' => $receiver->id, 'event_type' => 'transfer_completed', 'summary' => 'Transfer receipt confirmed and custody updated', 'before' => ['assignment_id' => $old->public_id], 'after' => ['assignment_id' => $new->public_id, 'movement' => $movement->movement_number], 'occurred_at' => now()]);
        $this->notifyProcurementAndInitiator($movement, 'Asset transfer completed', $asset->asset_tag.' was received and its new custody is active.');
    }

    private function completeReturn(AssetMovement $movement, User $officer, string $condition, string $comments): void
    {
        $assignment = AssetAssignment::lockForUpdate()->findOrFail($movement->from_assignment_id);
        $asset = Asset::lockForUpdate()->findOrFail($movement->asset_id);
        if ($assignment->status !== 'active' || ! $assignment->active_asset_id) {
            throw ValidationException::withMessages(['decision' => 'The custody is no longer active.']);
        }
        $assignment->update(['status' => 'returned', 'returned_at' => now(), 'condition_at_return' => $condition, 'return_notes' => $comments, 'active_asset_id' => null]);
        $asset->update(['custodian_user_id' => null, 'custodian_department_id' => null, 'condition' => $condition, 'lifecycle_status' => 'in_stock']);
        $movement->update(['status' => 'completed', 'decided_by_user_id' => $officer->id, 'decision_comments' => $comments, 'decided_at' => now(), 'confirmed_by_user_id' => $officer->id, 'condition_confirmed' => $condition, 'confirmation_comments' => $comments, 'confirmed_at' => now()]);
        $asset->events()->create(['actor_user_id' => $officer->id, 'event_type' => 'return_accepted', 'summary' => 'Procurement accepted the asset return', 'after' => ['movement' => $movement->movement_number, 'lifecycle_status' => 'in_stock'], 'occurred_at' => now()]);
        $movement->initiator->notify(new WorkflowNotification('Asset return completed', $asset->asset_tag.' was accepted into stock.', route('movements.show', $movement)));
    }

    private function activeAssignment(Asset $asset): AssetAssignment
    {
        return $asset->assignments()->with(['assignee', 'department', 'authorizedReceivers'])->whereNotNull('active_asset_id')->where('status', 'active')->firstOrFail();
    }

    private function authorizeCustodianOrProcurement(Request $request, AssetAssignment $assignment): void
    {
        abort_unless($request->user()->hasPermission('assets.transfer') || $assignment->assigned_to_user_id === $request->user()->id || $assignment->authorizedReceivers->contains($request->user()), 403);
    }

    private function procurementUsers()
    {
        return User::where('status', 'active')->whereHas('roles.permissions', fn ($q) => $q->where('slug', 'assets.transfer'))->get();
    }

    private function notifyReceivers(AssetMovement $movement): void
    {
        $movement->receivers()->get()->each->notify(new WorkflowNotification('Asset transfer awaiting receipt', $movement->asset->asset_tag.' is approved for transfer to you. Confirm only after physical receipt.', route('movements.show', $movement)));
    }

    private function notifyProcurementAndInitiator(AssetMovement $movement, string $title, string $message): void
    {
        $users = $this->procurementUsers()->push($movement->initiator)->unique('id');
        $users->each->notify(new WorkflowNotification($title, $message, route('movements.show', $movement)));
    }
}
