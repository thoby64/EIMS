<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class MaintenanceCase extends Model {
    protected $fillable=['public_id','asset_id','reported_by_user_id','maintenance_officer_id','problem_summary','problem_details','severity','status','final_outcome','final_reason','collection_instructions','finalized_at'];
    protected function casts(): array{return ['finalized_at'=>'datetime'];}
    public function getRouteKeyName(): string{return 'public_id';}
    public function asset(): BelongsTo{return $this->belongsTo(Asset::class);}
    public function reporter(): BelongsTo{return $this->belongsTo(User::class,'reported_by_user_id');}
    public function officer(): BelongsTo{return $this->belongsTo(User::class,'maintenance_officer_id');}
    public function reports(): HasMany{return $this->hasMany(MaintenanceReport::class);}
}
