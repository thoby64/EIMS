<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetInspection extends Model
{
    protected $fillable = ['public_id', 'inspection_number', 'asset_id', 'inspector_user_id', 'scheduled_by_user_id', 'scheduled_at', 'performed_at', 'status', 'physical_status', 'location_verified', 'custody_verified', 'condition_assessed', 'findings', 'recommendation', 'recommendation_notes', 'followup_status'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'performed_at' => 'datetime', 'location_verified' => 'boolean', 'custody_verified' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by_user_id');
    }
}
