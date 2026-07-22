<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = ['public_id', 'asset_group_id', 'parent_id', 'name', 'code', 'tracking_mode', 'icon', 'description', 'requires_asset_tag', 'is_maintainable', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['requires_asset_tag' => 'boolean', 'is_maintainable' => 'boolean', 'is_active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AssetGroup::class, 'asset_group_id');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeDefinition::class, 'asset_category_attribute')
            ->withPivot(['is_required', 'is_unique', 'sort_order', 'overrides'])
            ->orderByPivot('sort_order');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function maintenanceOfficers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'maintenance_category_responsibilities')
            ->wherePivot('responsibility', 'maintenance')
            ->withPivot(['responsibility', 'assigned_by_user_id', 'is_active', 'assigned_at'])
            ->withTimestamps();
    }

    public function maintenanceReviewOfficers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'maintenance_category_responsibilities')
            ->wherePivot('responsibility', 'review')
            ->withPivot(['responsibility', 'assigned_by_user_id', 'is_active', 'assigned_at'])
            ->withTimestamps();
    }
}
