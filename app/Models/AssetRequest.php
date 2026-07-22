<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class AssetRequest extends Model {
 protected $fillable=['public_id','request_number','requested_by_user_id','asset_category_id','preferred_location_id','purpose','justification','status','decided_by_user_id','decision_comments','submitted_at','decided_at'];
 protected function casts(): array{return ['submitted_at'=>'datetime','decided_at'=>'datetime'];}
 public function getRouteKeyName(): string{return 'public_id';}
 public function requester(): BelongsTo{return $this->belongsTo(User::class,'requested_by_user_id');}
 public function category(): BelongsTo{return $this->belongsTo(AssetCategory::class,'asset_category_id');}
 public function location(): BelongsTo{return $this->belongsTo(Location::class,'preferred_location_id');}
 public function properties(): HasMany{return $this->hasMany(AssetRequestProperty::class);}
 public function assignment(): HasOne{return $this->hasOne(AssetAssignment::class);}
}
