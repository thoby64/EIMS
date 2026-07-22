<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetIdentifier extends Model
{
    protected $fillable = ['asset_id', 'type', 'value', 'is_primary', 'metadata'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'metadata' => 'array'];
    }
}
