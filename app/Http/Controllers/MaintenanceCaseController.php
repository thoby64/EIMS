<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceCase;
use App\Models\MaintenanceReport;
use App\Models\SpareRequisition;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\MaintenanceWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceCaseController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        $categoryIds = $u->reviewableMaintenanceCategories()->pluck('asset_categories.id');
        $cases = MaintenanceCase::with(['asset.category', 'reporter', 'officer'])->where(function ($q) use ($u, $categoryIds) {
            $q->where('reported_by_user_id', $u->id)->orWhere('maintenance_officer_id', $u->id);
            if ($u->hasPermission('maintenance.review')) {
                $q->orWhereHas('asset', fn ($a) => $a->whereIn('asset_category_id', $categoryIds));
            }if ($u->hasPermission('maintenance.spares.procure')) {
                $q->orWhereIn('status', ['awaiting_procurement', 'spare_approved']);
            }
        })->when($request->filled('search'), fn ($q) => $q->where(fn ($search) => $search->where('problem_summary', 'like', '%'.$request->string('search').'%')->orWhereHas('asset', fn ($asset) => $asset->where('name', 'like', '%'.$request->string('search').'%')->orWhere('asset_tag', 'like', '%'.$request->string('search').'%'))->orWhereHas('reporter', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%'))))->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))->latest()->paginate(15)->withQueryString();

        return view('maintenance.index', compact('cases'));
    }

    public function create(Request $request): View
    {
        return view('maintenance.create', ['assets' => Asset::where('custodian_user_id', $request->user()->id)->where('lifecycle_status', 'assigned')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate(['asset_id' => ['required', 'exists:assets,id'], 'problem_summary' => ['required', 'string', 'max:500'], 'problem_details' => ['required', 'string', 'max:3000'], 'severity' => ['required', Rule::in(['low', 'normal', 'high', 'critical'])]]);
        $asset = Asset::with('category')->whereKey($v['asset_id'])->where('custodian_user_id', $request->user()->id)->where('lifecycle_status', 'assigned')->firstOrFail();
        $officer = User::where('status', 'active')->whereHas('maintainableCategories', fn ($q) => $q->where('asset_categories.id', $asset->asset_category_id))->first();
        if (! $officer) {
            throw ValidationException::withMessages(['asset_id' => 'No maintenance officer is currently assigned to this asset category.']);
        }$case = DB::transaction(function () use ($v, $asset, $officer, $request) {
            $case = MaintenanceCase::create([...$v, 'public_id' => (string) Str::ulid(), 'reported_by_user_id' => $request->user()->id, 'maintenance_officer_id' => $officer->id, 'status' => 'assigned']);
            $asset->update(['lifecycle_status' => 'under_maintenance']);
            $officer->notify(new WorkflowNotification('New maintenance case', $asset->asset_tag.': '.$v['problem_summary'], route('maintenance.show', $case)));

            return $case;
        });

        return redirect()->route('maintenance.show', $case)->with('success', 'Problem reported and routed to a category-qualified maintenance officer.');
    }

    public function show(Request $request, MaintenanceCase $case): View
    {
        $case->load(['asset.category.group', 'reporter', 'officer', 'reports.review', 'reports.spareRequisition']);
        abort_unless($this->canView($request->user(), $case), 403);

        return view('maintenance.show', compact('case'));
    }

    public function report(Request $request, MaintenanceCase $case, MaintenanceWorkflowService $flow): RedirectResponse
    {
        $v = $request->validate(['technical_outcome' => ['required', Rule::in(['repaired', 'repair_in_progress', 'awaiting_spare', 'not_repaired', 'beyond_repair', 'no_fault_found'])], 'findings' => ['required', 'string', 'max:4000'], 'work_performed' => ['nullable', 'string', 'max:4000'], 'spare_needed' => ['required', 'boolean'], 'spare_description' => ['nullable', 'string', 'max:4000']]);
        $flow->submitReport($case, $request->user(), $v);

        return back()->with('success', 'Maintenance report submitted for independent review.');
    }

    public function review(Request $request, MaintenanceReport $report, MaintenanceWorkflowService $flow): RedirectResponse
    {
        $v = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'comments' => ['required', 'string', 'max:3000']]);
        $flow->review($report, $request->user(), $v['decision'], $v['comments']);

        return back()->with('success', 'Review decision recorded.');
    }

    public function procurement(Request $request, SpareRequisition $requisition, MaintenanceWorkflowService $flow): RedirectResponse
    {
        $v = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'comments' => ['required', 'string', 'max:3000']]);
        $flow->procurementDecision($requisition, $request->user(), $v['decision'], $v['comments']);

        return back()->with('success', 'Procurement decision recorded and sent to Maintenance Review.');
    }

    public function issue(Request $request, SpareRequisition $requisition, MaintenanceWorkflowService $flow): RedirectResponse
    {
        $v = $request->validate(['issued_items' => ['required', 'string', 'max:3000'], 'issued_quantity' => ['required', 'integer', 'min:1']]);
        $flow->issueSpare($requisition, $request->user(), $v['issued_items'], $v['issued_quantity']);

        return back()->with('success', 'Spare issue recorded. Maintenance Review has been notified.');
    }

    public function relay(Request $request, SpareRequisition $requisition, MaintenanceWorkflowService $flow): RedirectResponse
    {
        $flow->relay($requisition, $request->user());

        return back()->with('success', 'Procurement decision relayed to the maintenance officer.');
    }

    public function receiveSpare(Request $request, SpareRequisition $requisition, MaintenanceWorkflowService $flow): RedirectResponse
    {
        $v = $request->validate(['receipt_remarks' => ['required', 'string', 'max:2000']]);
        $flow->confirmSpare($requisition, $request->user(), $v['receipt_remarks']);

        return back()->with('success', 'Spare receipt confirmed. Continue repair and submit the next report cycle.');
    }

    public function finalize(Request $request, MaintenanceCase $case, MaintenanceWorkflowService $flow): RedirectResponse
    {
        $v = $request->validate(['final_outcome' => ['required', Rule::in(['repaired', 'not_repaired', 'beyond_repair'])], 'final_reason' => ['required', 'string', 'max:3000'], 'collection_instructions' => ['nullable', 'string', 'max:2000']]);
        $flow->finalize($case, $request->user(), $v['final_outcome'], $v['final_reason'], $v['collection_instructions'] ?? null);

        return back()->with('success', 'Final outcome submitted to the reporting officer.');
    }

    public function confirmReturn(Request $request, MaintenanceCase $case): RedirectResponse
    {
        abort_unless($case->reported_by_user_id === $request->user()->id && $case->status === 'ready_for_collection', 403);
        $v = $request->validate(['returned_by_user_id' => ['required', 'exists:users,id'], 'condition_received' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])], 'correct_asset' => ['required', 'boolean'], 'comment' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($case, $v, $request) {
            DB::table('maintenance_return_confirmations')->insert([...$v, 'public_id' => (string) Str::ulid(), 'maintenance_case_id' => $case->id, 'confirmed_by_user_id' => $request->user()->id, 'confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $case->update(['status' => 'closed']);
            $case->asset->update(['lifecycle_status' => 'assigned', 'condition' => $v['condition_received']]);
        });

        return back()->with('success','Asset return confirmed and maintenance case closed.');
    }

    private function canView(User $u,MaintenanceCase $case): bool
    {
        return $case->reported_by_user_id === $u->id || $case->maintenance_officer_id === $u->id || ($u->hasPermission('maintenance.review') && $u->reviewableMaintenanceCategories()->whereKey($case->asset->asset_category_id)->exists()) || $u->hasPermission('maintenance.spares.procure');
    }
}
