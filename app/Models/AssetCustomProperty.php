<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCustomProperty extends Model
{
    protected $fillable = ['asset_id', 'name', 'key', 'value'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
