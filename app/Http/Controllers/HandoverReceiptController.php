<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HandoverReceiptController extends Controller
{
    public function pending(Request $request): View
    {
        $assignments = AssetAssignment::query()
            ->with(['asset.category', 'assignee', 'department', 'assigner', 'location'])
            ->where(function ($query) use ($request) {
                $query->where('assigned_to_user_id', $request->user()->id)
                    ->orWhereHas('authorizedReceivers', fn ($receivers) => $receivers->where('users.id', $request->user()->id)->where('asset_assignment_receivers.status', 'pending'));
            })
            ->where('status', 'pending_receipt')
            ->latest('assigned_at')
            ->get();

        return view('handovers.pending', compact('assignments'));
    }

    public function create(Request $request, AssetAssignment $assignment): View
    {
        $this->authorizeRecipient($request, $assignment);
        abort_unless($assignment->status === 'pending_receipt' && ! $assignment->receipt()->exists(), 409, 'This handover has already been decided.');
        $assignment->load(['asset.category.group', 'assignee', 'department', 'assigner', 'location']);

        return view('handovers.confirm', [
            'assignment' => $assignment,
            'handoverUsers' => User::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AssetAssignment $assignment): RedirectResponse
    {
        $this->authorizeRecipient($request, $assignment);
        $validated = $request->validate([
            'handed_over_by_user_id' => ['required', Rule::exists('users', 'id')->where('status', 'active')],
            'decision' => ['required', Rule::in(['accepted', 'accepted_with_remarks', 'rejected'])],
            'matches_expected_asset' => ['required', 'boolean'],
            'condition_received' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'remarks' => [Rule::requiredIf(fn () => in_array($request->input('decision'), ['accepted_with_remarks', 'rejected'], true)), 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($assignment, $validated, $request) {
            $lockedAssignment = AssetAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $authorized = $lockedAssignment->assigned_to_user_id === $request->user()->id
                || DB::table('asset_assignment_receivers')->where('asset_assignment_id', $lockedAssignment->id)->where('user_id', $request->user()->id)->where('status', 'pending')->exists();
            abort_unless($authorized, 403);
            abort_unless($lockedAssignment->status === 'pending_receipt' && ! $lockedAssignment->receipt()->exists(), 409, 'This handover has already been decided.');
            $asset = Asset::query()->lockForUpdate()->findOrFail($lockedAssignment->asset_id);

            $lockedAssignment->receipt()->create([
                ...$validated,
                'public_id' => (string) Str::ulid(),
                'confirmed_by_user_id' => $request->user()->id,
                'received_at' => now(),
            ]);

            $accepted = $validated['decision'] !== 'rejected';
            $lockedAssignment->update([
                'status' => $accepted ? 'active' : 'rejected',
                'active_asset_id' => $accepted ? $asset->id : null,
            ]);
            $asset->update($accepted ? [
                'custodian_user_id' => $lockedAssignment->assignment_type === 'individual' ? $request->user()->id : null,
                'custodian_department_id' => $lockedAssignment->assignment_type === 'department' ? $lockedAssignment->assigned_to_department_id : null,
                'location_id' => $lockedAssignment->location_id,
                'condition' => $validated['condition_received'],
                'lifecycle_status' => 'assigned',
            ] : [
                'custodian_user_id' => null,
                'custodian_department_id' => null,
                'lifecycle_status' => 'in_stock',
            ]);
            if ($lockedAssignment->assignment_type === 'department') {
                DB::table('asset_assignment_receivers')->where('asset_assignment_id', $lockedAssignment->id)->update(['status' => $accepted ? 'closed' : 'cancelled', 'updated_at' => now()]);
                DB::table('asset_assignment_receivers')->where('asset_assignment_id', $lockedAssignment->id)->where('user_id', $request->user()->id)->update(['status' => $accepted ? 'confirmed' : 'rejected', 'confirmed_at' => now(), 'updated_at' => now()]);
            }
            if ($lockedAssignment->asset_request_id) {
                AssetRequest::whereKey($lockedAssignment->asset_request_id)->update([
                    'status' => $accepted ? 'fulfilled' : 'approved',
                ]);
            }

            $asset->events()->create([
                'actor_user_id' => $request->user()->id,
                'event_type' => $accepted ? 'handover_accepted' : 'handover_rejected',
                'summary' => $accepted ? 'Recipient confirmed and accepted the asset' : 'Recipient rejected the asset handover',
                'before' => ['lifecycle_status' => 'reserved'],
                'after' => ['lifecycle_status' => $asset->lifecycle_status, 'decision' => $validated['decision']],
                'metadata' => ['assignment_id' => $lockedAssignment->public_id],
                'occurred_at' => now(),
            ]);
        }, 3);

        return redirect()->route('handovers.pending')->with('success', $validated['decision'] === 'rejected' ? 'Handover rejected and asset returned to available stock.' : 'Receipt confirmed. The asset is now assigned to you.');
    }

    private function authorizeRecipient(Request $request, AssetAssignment $assignment): void
    {
        $authorized = $assignment->assigned_to_user_id === $request->user()->id
            || $assignment->authorizedReceivers()->where('users.id', $request->user()->id)->wherePivot('status', 'pending')->exists();
        abort_unless($authorized, 403);
    }
}
