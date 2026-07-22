<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class MaintenanceReport extends Model {
    protected $fillable=['public_id','maintenance_case_id','maintenance_officer_id','cycle_number','technical_outcome','findings','work_performed','spare_needed','spare_description','status','submitted_at'];
    protected function casts(): array{return ['spare_needed'=>'boolean','submitted_at'=>'datetime'];}
    public function getRouteKeyName(): string{return 'public_id';}
    public function maintenanceCase(): BelongsTo{return $this->belongsTo(MaintenanceCase::class);}
    public function officer(): BelongsTo{return $this->belongsTo(User::class,'maintenance_officer_id');}
    public function review(): HasOne{return $this->hasOne(MaintenanceReview::class);}
    public function spareRequisition(): HasOne{return $this->hasOne(SpareRequisition::class);}
}
