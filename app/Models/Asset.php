<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'public_id', 'asset_category_id', 'parent_asset_id', 'organizational_unit_id', 'location_id',
        'custodian_user_id', 'custodian_department_id', 'registered_by', 'asset_tag', 'name', 'description', 'manufacturer', 'brand',
        'model', 'serial_number', 'condition', 'lifecycle_status', 'ownership_type', 'acquired_on',
        'acquisition_cost', 'currency', 'warranty_expires_on', 'commissioned_on', 'retired_on', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquired_on' => 'date',
            'warranty_expires_on' => 'date',
            'commissioned_on' => 'date',
            'retired_on' => 'date',
            'acquisition_cost' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function custodianDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'custodian_department_id');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(AssetIdentifier::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(AssetAttributeValue::class);
    }

    public function customProperties(): HasMany
    {
        return $this->hasMany(AssetCustomProperty::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function isEditable(): bool
    {
        return ! $this->assignments()->exists();
    }

    public function events(): HasMany
    {
        return $this->hasMany(AssetEvent::class)->latest('occurred_at');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(AssetInspection::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }
}
