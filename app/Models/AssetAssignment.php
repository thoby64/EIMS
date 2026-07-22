<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetAssignment extends Model
{
    protected $fillable = [
        'public_id', 'asset_id', 'active_asset_id', 'asset_request_id', 'assignment_type', 'assigned_to_user_id', 'assigned_to_unit_id', 'assigned_to_department_id', 'location_id', 'assigned_by',
        'assigned_at', 'expected_return_at', 'returned_at', 'condition_at_issue', 'condition_at_return',
        'purpose', 'accessories', 'issue_notes', 'return_notes', 'status',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'expected_return_at' => 'datetime', 'returned_at' => 'datetime', 'accessories' => 'array'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_to_department_id');
    }

    public function authorizedReceivers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asset_assignment_receivers')->withPivot(['status', 'confirmed_at'])->withTimestamps();
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(HandoverReceipt::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
