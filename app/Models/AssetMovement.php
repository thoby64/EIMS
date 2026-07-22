<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMovement extends Model
{
    protected $fillable = ['public_id', 'movement_number', 'type', 'asset_id', 'from_assignment_id', 'initiated_by_user_id', 'target_type', 'target_user_id', 'target_department_id', 'target_location_id', 'reason', 'condition_reported', 'status', 'decided_by_user_id', 'decision_comments', 'decided_at', 'confirmed_by_user_id', 'condition_confirmed', 'confirmation_comments', 'confirmed_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime', 'confirmed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignment()
    {
        return $this->belongsTo(AssetAssignment::class, 'from_assignment_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetDepartment()
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function targetLocation()
    {
        return $this->belongsTo(Location::class, 'target_location_id');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function receivers()
    {
        return $this->belongsToMany(User::class, 'asset_movement_receivers')->withPivot(['status', 'confirmed_at'])->withTimestamps();
    }
}
