<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeDefinition extends Model
{
    protected $fillable = ['name', 'slug', 'data_type', 'unit', 'options', 'validation_rules', 'help_text', 'is_searchable', 'is_active'];

    protected function casts(): array
    {
        return ['options' => 'array', 'is_searchable' => 'boolean', 'is_active' => 'boolean'];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(AssetCategory::class, 'asset_category_attribute');
    }
}
