<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDisposal;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetDisposalController extends Controller
{
    public function index(Request $r): View
    {
        $q = AssetDisposal::with(['asset.category', 'proposer'])
            ->when($r->filled('status'), fn ($x) => $x->where('status', $r->string('status')))
            ->when($r->filled('reason'), fn ($x) => $x->where('reason', $r->string('reason')))
            ->when($r->filled('search'), fn ($x) => $x->where(fn ($search) => $search
                ->where('disposal_number', 'like', '%'.$r->string('search').'%')
                ->orWhereHas('asset', fn ($asset) => $asset->where('name', 'like', '%'.$r->string('search').'%')->orWhere('asset_tag', 'like', '%'.$r->string('search').'%'))));
        if (! $r->user()->hasPermission('disposals.review') && ! $r->user()->hasPermission('disposals.approve')) {
            $q->where('proposed_by_user_id', $r->user()->id);
        }

        return view('disposals.index', ['disposals' => $q->latest()->paginate(15)->withQueryString()]);
    }

    public function create(Request $r, Asset $asset): View
    {
        abort_unless($this->mayPropose($r, $asset), 403);
        abort_if(in_array($asset->lifecycle_status, ['disposed', 'retired', 'awaiting_disposal'], true), 409);

        return view('disposals.create', compact('asset'));
    }

    public function store(Request $r, Asset $asset): RedirectResponse
    {
        abort_unless($this->mayPropose($r, $asset), 403);
        $v = $r->validate(['reason' => ['required', Rule::in(['beyond_repair', 'obsolete', 'unsafe', 'missing', 'end_of_service', 'uneconomical_to_repair'])], 'justification' => ['required', 'string', 'max:4000']]);
        $disposal = DB::transaction(function () use ($r, $asset, $v) {
            $asset = Asset::lockForUpdate()->findOrFail($asset->id);
            if ($asset->disposals()->whereIn('status', ['pending_review', 'pending_approval', 'awaiting_surrender', 'ready_for_finalization'])->exists()) {
                throw ValidationException::withMessages(['reason' => 'This asset already has an active retirement proposal.']);
            }$d = $asset->disposals()->create([...$v, 'public_id' => (string) Str::ulid(), 'disposal_number' => 'DSP-'.now()->format('Y').'-'.Str::upper(Str::random(8)), 'proposed_by_user_id' => $r->user()->id, 'status' => 'pending_review']);
            $this->usersWith('disposals.review')->each->notify(new WorkflowNotification('Retirement proposal awaiting review', $d->disposal_number.' requires independent Maintenance Review.', route('disposals.show', $d)));
            $asset->events()->create(['actor_user_id' => $r->user()->id, 'event_type' => 'retirement_proposed', 'summary' => 'Retirement proposed: '.str_replace('_', ' ', $v['reason']), 'after' => ['disposal' => $d->disposal_number], 'occurred_at' => now()]);

            return $d;
        });

        return redirect()->route('disposals.show', $disposal)->with('success', 'Proposal submitted to Maintenance Review.');
    }

    public function show(Request $r, AssetDisposal $disposal): View
    {
        $disposal->load(['asset.category.group', 'asset.custodian', 'asset.custodianDepartment', 'asset.assignments.authorizedReceivers', 'proposer', 'reviewer', 'approver', 'surrenderedBy', 'finalizedBy']);
        $can = $r->user()->hasPermission('disposals.review') || $r->user()->hasPermission('disposals.approve') || $r->user()->hasPermission('disposals.finalize') || $disposal->proposed_by_user_id === $r->user()->id || $this->isCustodian($r, $disposal->asset);
        abort_unless($can, 403);

        return view('disposals.show', compact('disposal'));
    }

    public function review(Request $r, AssetDisposal $disposal): RedirectResponse
    {
        $v = $r->validate(['decision' => ['required', Rule::in(['verified', 'rejected'])], 'comments' => ['required', 'string', 'max:3000']]);
        DB::transaction(function () use ($r, $disposal, $v) {
            $d = AssetDisposal::lockForUpdate()->findOrFail($disposal->id);
            if ($d->status !== 'pending_review') {
                throw ValidationException::withMessages(['decision' => 'Review has already been completed.']);
            }$next = $v['decision'] === 'verified' ? 'pending_approval' : 'review_rejected';
            $d->update(['status' => $next, 'reviewed_by_user_id' => $r->user()->id, 'review_decision' => $v['decision'], 'review_comments' => $v['comments'], 'reviewed_at' => now()]);
            if ($next === 'pending_approval') {
                $this->usersWith('disposals.approve')->each->notify(new WorkflowNotification('Retirement proposal awaiting decision', $d->disposal_number.' was technically verified.', route('disposals.show', $d)));
            } else {
                $d->proposer->notify(new WorkflowNotification('Retirement proposal rejected', $v['comments'], route('disposals.show', $d)));
            }
        });

        return back()->with('success', 'Independent review recorded.');
    }

    public function approve(Request $r, AssetDisposal $disposal): RedirectResponse
    {
        $v = $r->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'comments' => ['required', 'string', 'max:3000']]);
        DB::transaction(function () use ($r, $disposal, $v) {
            $d = AssetDisposal::with('asset')->lockForUpdate()->findOrFail($disposal->id);
            if ($d->status !== 'pending_approval') {
                throw ValidationException::withMessages(['decision' => 'Procurement decision is not available.']);
            }$approved = $v['decision'] === 'approved';
            $assigned = $d->asset->assignments()->whereNotNull('active_asset_id')->where('status', 'active')->exists();
            $status = $approved ? ($assigned && $d->reason !== 'missing' ? 'awaiting_surrender' : 'ready_for_finalization') : 'approval_rejected';
            $d->update(['status' => $status, 'approved_by_user_id' => $r->user()->id, 'approval_decision' => $v['decision'], 'approval_comments' => $v['comments'], 'approved_at' => now()]);
            if ($approved) {
                $d->asset->update(['lifecycle_status' => 'awaiting_disposal']);
                $title = $status === 'awaiting_surrender' ? 'Asset surrender required' : 'Disposal ready for finalization';
                $targets = $status === 'awaiting_surrender' ? $this->custodians($d->asset) : $this->usersWith('disposals.finalize');
                $targets->each->notify(new WorkflowNotification($title, $d->disposal_number.' was approved.', route('disposals.show', $d)));
            } else {
                $d->proposer->notify(new WorkflowNotification('Retirement proposal rejected by Procurement', $v['comments'], route('disposals.show', $d)));
            }
        });

        return back()->with('success', 'Procurement decision recorded.');
    }

    public function surrender(Request $r, AssetDisposal $disposal): RedirectResponse
    {
        abort_unless($this->isCustodian($r, $disposal->asset), 403);
        $v = $r->validate(['comments' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($r, $disposal, $v) {
            $d = AssetDisposal::with('asset')->lockForUpdate()->findOrFail($disposal->id);
            if ($d->status !== 'awaiting_surrender') {
                throw ValidationException::withMessages(['comments' => 'This asset is not awaiting surrender.']);
            }$a = AssetAssignment::where('asset_id', $d->asset_id)->whereNotNull('active_asset_id')->where('status', 'active')->lockForUpdate()->firstOrFail();
            $a->update(['status' => 'returned', 'returned_at' => now(), 'condition_at_return' => $d->asset->condition, 'return_notes' => 'Surrendered for approved disposal '.$d->disposal_number, 'active_asset_id' => null]);
            $d->asset->update(['custodian_user_id' => null, 'custodian_department_id' => null]);
            $d->update(['status' => 'ready_for_finalization', 'surrendered_by_user_id' => $r->user()->id, 'surrender_comments' => $v['comments'], 'surrendered_at' => now()]);
            $this->usersWith('disposals.finalize')->each->notify(new WorkflowNotification('Approved disposal ready to finalize', $d->disposal_number.' has been surrendered.', route('disposals.show', $d)));
        });

        return back()->with('success', 'Asset surrender confirmed.');
    }

    public function finalize(Request $r, AssetDisposal $disposal): RedirectResponse
    {
        $v = $r->validate(['disposal_method' => ['required', Rule::in(['retired', 'scrapped', 'donated', 'sold', 'lost_write_off', 'archived'])], 'disposed_on' => ['required', 'date', 'before_or_equal:today'], 'witness_name' => ['required', 'string', 'max:180'], 'finalization_comments' => ['required', 'string', 'max:3000']]);
        DB::transaction(function () use ($r, $disposal, $v) {
            $d = AssetDisposal::with('asset')->lockForUpdate()->findOrFail($disposal->id);
            if ($d->status !== 'ready_for_finalization') {
                throw ValidationException::withMessages(['disposal_method' => 'This disposal is not ready for finalization.']);
            }
            $d->update([...$v, 'status' => 'completed', 'finalized_by_user_id' => $r->user()->id, 'finalized_at' => now()]);
            AssetAssignment::where('asset_id', $d->asset_id)->whereNotNull('active_asset_id')->where('status', 'active')->update(['status' => 'returned', 'returned_at' => now(), 'return_notes' => 'Custody closed by finalized disposal '.$d->disposal_number, 'active_asset_id' => null, 'updated_at' => now()]);
            $d->asset->update(['lifecycle_status' => $v['disposal_method'] === 'retired' ? 'retired' : 'disposed', 'retired_on' => $v['disposed_on'], 'custodian_user_id' => null, 'custodian_department_id' => null]);
            $d->asset->events()->create(['actor_user_id' => $r->user()->id, 'event_type' => 'disposal_completed', 'summary' => 'Asset disposal finalized as '.str_replace('_', ' ', $v['disposal_method']), 'after' => ['disposal' => $d->disposal_number, 'method' => $v['disposal_method']], 'occurred_at' => now()]);
            $d->proposer->notify(new WorkflowNotification('Asset disposal completed', $d->asset->asset_tag.' was finalized as '.str_replace('_', ' ', $v['disposal_method']).'.', route('disposals.show', $d)));
        });

        return back()->with('success', 'Disposal finalized. The asset can no longer be assigned.');
    }

    private function usersWith(string $permission)
    {
        return User::where('status', 'active')->whereHas('roles.permissions', fn ($q) => $q->where('slug', $permission))->get();
    }

    private function custodians(Asset $asset)
    {
        $assignment = $asset->assignments()->whereNotNull('active_asset_id')->where('status', 'active')->first();
        if (! $assignment) {
            return collect();
        }

        return $assignment->assignment_type === 'individual' ? collect([$assignment->assignee]) : $assignment->authorizedReceivers;
    }

    private function isCustodian(Request $r, Asset $asset): bool
    {
        $a = $asset->assignments()->with('authorizedReceivers')->whereNotNull('active_asset_id')->where('status', 'active')->first();

        return $a && ($a->assigned_to_user_id === $r->user()->id || $a->authorizedReceivers->contains($r->user()));
    }

    private function mayPropose(Request $r, Asset $asset): bool
    {
        return $r->user()->hasPermission('disposals.approve')
            || $r->user()->hasPermission('disposals.review')
            || $r->user()->hasPermission('maintenance.manage')
            || $this->isCustodian($r, $asset);
    }
}
