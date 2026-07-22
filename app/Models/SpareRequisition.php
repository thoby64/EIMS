<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SpareRequisition extends Model {
    protected $fillable=['public_id','requisition_number','maintenance_report_id','status','requested_items','decided_by_user_id','procurement_comments','decided_at','issued_items','issued_quantity','issued_by_user_id','issued_at','relayed_by_user_id','relayed_at','received_by_user_id','receipt_remarks','received_at'];
    protected function casts(): array{return ['decided_at'=>'datetime','issued_at'=>'datetime','relayed_at'=>'datetime','received_at'=>'datetime'];}
    public function getRouteKeyName(): string{return 'public_id';}
    public function report(): BelongsTo{return $this->belongsTo(MaintenanceReport::class,'maintenance_report_id');}
}
