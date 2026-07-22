<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAttributeValue extends Model
{
    protected $fillable = ['asset_id', 'attribute_definition_id', 'text_value', 'number_value', 'date_value', 'boolean_value', 'json_value'];

    protected function casts(): array
    {
        return ['date_value' => 'datetime', 'boolean_value' => 'boolean', 'json_value' => 'array'];
    }

    public function definition()
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }
}
