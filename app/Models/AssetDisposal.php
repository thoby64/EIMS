<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    protected $fillable = ['public_id', 'disposal_number', 'asset_id', 'proposed_by_user_id', 'reason', 'justification', 'status', 'reviewed_by_user_id', 'review_decision', 'review_comments', 'reviewed_at', 'approved_by_user_id', 'approval_decision', 'approval_comments', 'approved_at', 'surrendered_by_user_id', 'surrender_comments', 'surrendered_at', 'finalized_by_user_id', 'disposal_method', 'disposed_on', 'witness_name', 'finalization_comments', 'finalized_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'surrendered_at' => 'datetime', 'disposed_on' => 'date', 'finalized_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function proposer()
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function surrenderedBy()
    {
        return $this->belongsTo(User::class, 'surrendered_by_user_id');
    }

    public function finalizedBy()
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }
}
