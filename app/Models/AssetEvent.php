<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetEvent extends Model
{
    protected $fillable = ['asset_id', 'actor_user_id', 'event_type', 'summary', 'before', 'after', 'metadata', 'occurred_at'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'metadata' => 'array', 'occurred_at' => 'datetime'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
