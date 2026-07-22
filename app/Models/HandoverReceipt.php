<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoverReceipt extends Model
{
    protected $fillable = [
        'public_id', 'asset_assignment_id', 'handed_over_by_user_id', 'confirmed_by_user_id', 'decision',
        'matches_expected_asset', 'condition_received', 'remarks', 'received_at',
    ];

    protected function casts(): array
    {
        return ['matches_expected_asset' => 'boolean', 'received_at' => 'datetime'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssetAssignment::class, 'asset_assignment_id');
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by_user_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
