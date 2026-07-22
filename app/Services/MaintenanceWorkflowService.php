<?php
namespace App\Services;
use App\Models\MaintenanceCase;
use App\Models\MaintenanceReport;
use App\Models\SpareRequisition;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaintenanceWorkflowService {
    public function submitReport(MaintenanceCase $case,User $officer,array $data): MaintenanceReport {
        return DB::transaction(function()use($case,$officer,$data){$case=MaintenanceCase::lockForUpdate()->findOrFail($case->id);$this->ensureOfficer($case,$officer);
            if(in_array($case->status,['closed','ready_for_collection','awaiting_review','awaiting_procurement'],true))throw ValidationException::withMessages(['case'=>'This case cannot accept another work report now.']);
            if($data['spare_needed']&&!filled($data['spare_description']??null))throw ValidationException::withMessages(['spare_description'=>'Describe every spare required.']);
            $report=$case->reports()->create([...$data,'public_id'=>(string)Str::ulid(),'maintenance_officer_id'=>$officer->id,'cycle_number'=>(int)$case->reports()->max('cycle_number')+1,'status'=>'awaiting_review','submitted_at'=>now()]);
            $case->update(['status'=>'awaiting_review']);
            $this->reviewers($case)->each->notify(new WorkflowNotification('Maintenance report awaiting review',$case->asset->asset_tag.' has a new technical report.',route('maintenance.show',$case)));
            return $report;});}

    public function review(MaintenanceReport $report,User $reviewer,string $decision,string $comments): void {
        DB::transaction(function()use($report,$reviewer,$decision,$comments){$report=MaintenanceReport::with('maintenanceCase.asset')->lockForUpdate()->findOrFail($report->id);$case=$report->maintenanceCase;$this->ensureReviewer($case,$reviewer);if($report->status!=='awaiting_review')throw ValidationException::withMessages(['report'=>'This report has already been reviewed.']);
            $report->review()->create(['public_id'=>(string)Str::ulid(),'reviewed_by_user_id'=>$reviewer->id,'decision'=>$decision,'comments'=>$comments,'reviewed_at'=>now()]);
            if($decision==='rejected'){$report->update(['status'=>'review_rejected']);$case->update(['status'=>'review_rejected']);$case->officer->notify(new WorkflowNotification('Maintenance report rejected',$comments,route('maintenance.show',$case)));return;}
            $report->update(['status'=>'review_approved']);
            if($report->spare_needed){$req=$report->spareRequisition()->create(['public_id'=>(string)Str::ulid(),'requisition_number'=>'SPR-'.now()->format('Y').'-'.Str::upper(Str::random(8)),'requested_items'=>$report->spare_description,'status'=>'pending_procurement']);$case->update(['status'=>'awaiting_procurement']);$this->procurementUsers()->each->notify(new WorkflowNotification('Spare requisition awaiting decision',$req->requisition_number.' requires Procurement action.',route('maintenance.show',$case)));}
            else{$case->update(['status'=>'review_approved']);$case->officer->notify(new WorkflowNotification('Maintenance report approved','Review approved your report. Finalize the outcome for the reporting officer.',route('maintenance.show',$case)));}});}

    public function procurementDecision(SpareRequisition $req,User $user,string $decision,string $comments): void {
        DB::transaction(function()use($req,$user,$decision,$comments){$req=SpareRequisition::with('report.maintenanceCase.asset')->lockForUpdate()->findOrFail($req->id);if(!$user->hasPermission('maintenance.spares.procure'))abort(403);if($req->status!=='pending_procurement')throw ValidationException::withMessages(['requisition'=>'This requisition has already been decided.']);$req->update(['status'=>$decision==='approved'?'approved':'rejected','decided_by_user_id'=>$user->id,'procurement_comments'=>$comments,'decided_at'=>now()]);$case=$req->report->maintenanceCase;$case->update(['status'=>$decision==='approved'?'spare_approved':'procurement_rejected']);$this->reviewers($case)->each->notify(new WorkflowNotification('Procurement spare decision',ucfirst($decision).': '.$comments,route('maintenance.show',$case)));});}

    public function issueSpare(SpareRequisition $req,User $user,string $items,int $quantity): void {
        DB::transaction(function()use($req,$user,$items,$quantity){$req=SpareRequisition::with('report.maintenanceCase')->lockForUpdate()->findOrFail($req->id);if(!$user->hasPermission('maintenance.spares.procure'))abort(403);if($req->status!=='approved')throw ValidationException::withMessages(['requisition'=>'Only approved spares can be issued.']);$req->update(['status'=>'issued','issued_items'=>$items,'issued_quantity'=>$quantity,'issued_by_user_id'=>$user->id,'issued_at'=>now()]);$req->report->maintenanceCase->update(['status'=>'spare_issued_awaiting_relay']);$this->reviewers($req->report->maintenanceCase)->each->notify(new WorkflowNotification('Approved spare issued','Review and relay the issued spare to the maintenance officer.',route('maintenance.show',$req->report->maintenanceCase)));});}

    public function relay(SpareRequisition $req,User $reviewer): void {$case=$req->load('report.maintenanceCase.asset')->report->maintenanceCase;$this->ensureReviewer($case,$reviewer);if(!in_array($req->status,['rejected','issued'],true)||$req->relayed_at)throw ValidationException::withMessages(['requisition'=>'This decision cannot be relayed.']);$req->update(['relayed_by_user_id'=>$reviewer->id,'relayed_at'=>now()]);$case->update(['status'=>$req->status==='issued'?'spare_issued':'procurement_rejected_relayed']);$case->officer->notify(new WorkflowNotification('Spare decision relayed',$req->status==='issued'?'Procurement issued the approved spare. Confirm receipt to continue.':'Procurement rejected the spare. Finalize the unresolved repair.',route('maintenance.show',$case)));}

    public function confirmSpare(SpareRequisition $req,User $officer,string $remarks): void {$case=$req->load('report.maintenanceCase')->report->maintenanceCase;$this->ensureOfficer($case,$officer);if($req->status!=='issued'||!$req->relayed_at||$req->received_at)throw ValidationException::withMessages(['requisition'=>'This spare is not ready for receipt confirmation.']);$req->update(['status'=>'received','received_by_user_id'=>$officer->id,'receipt_remarks'=>$remarks,'received_at'=>now()]);$case->update(['status'=>'spare_received']);}

    public function finalize(MaintenanceCase $case,User $officer,string $outcome,string $reason,?string $instructions): void {$this->ensureOfficer($case,$officer);if(!in_array($case->status,['review_approved','review_rejected','procurement_rejected_relayed'],true))throw ValidationException::withMessages(['case'=>'This case is not ready for finalization.']);$repaired=$outcome==='repaired';$case->update(['status'=>$repaired?'ready_for_collection':'closed','final_outcome'=>$outcome,'final_reason'=>$reason,'collection_instructions'=>$instructions,'finalized_at'=>now()]);$case->reporter->notify(new WorkflowNotification($repaired?'Asset ready for collection':'Asset could not be repaired',$repaired?($instructions?:'Contact Maintenance to collect your asset.'):$reason.' You may request a replacement in the Asset Requests section.',route('maintenance.show',$case)));}

    private function ensureOfficer(MaintenanceCase $case,User $user): void {abort_unless($case->maintenance_officer_id===$user->id&&$user->maintainableCategories()->whereKey($case->asset->asset_category_id)->exists(),403);}
    private function ensureReviewer(MaintenanceCase $case,User $user): void {abort_unless($user->reviewableMaintenanceCategories()->whereKey($case->asset->asset_category_id)->exists(),403);}
    private function reviewers(MaintenanceCase $case){return User::where('status','active')->whereHas('reviewableMaintenanceCategories',fn($q)=>$q->where('asset_categories.id',$case->asset->asset_category_id))->get();}
    private function procurementUsers(){return User::where('status','active')->whereHas('roles.permissions',fn($q)=>$q->where('permissions.slug','maintenance.spares.procure'))->get();}
}
