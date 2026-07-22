<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Services\AssetTagGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $assets = Asset::query()
            ->with(['category.group', 'location', 'custodian'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('asset_tag', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('identifiers', fn ($identifier) => $identifier->where('value', $search))
                    ->orWhereHas('customProperties', fn ($property) => $property
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('value', 'like', "%{$search}%"));
            }))
            ->when($request->filled('category'), fn ($query) => $query->where('asset_category_id', $request->integer('category')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('assets.index', [
            'assets' => $assets,
            'categories' => AssetCategory::where('is_active', true)->orderBy('name')->get(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $categories = AssetCategory::query()
            ->with(['group', 'attributes' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('assets.create', [
            'categories' => $categories,
            'categorySchema' => $categories->mapWithKeys(fn ($category) => [$category->id => [
                'tracking_mode' => $category->tracking_mode,
                'attributes' => $category->attributes->map(fn ($attribute) => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'type' => $attribute->data_type,
                    'unit' => $attribute->unit,
                    'options' => $attribute->options,
                    'required' => (bool) $attribute->pivot->is_required,
                    'help' => $attribute->help_text,
                ])->values(),
            ]]),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AssetTagGenerator $tagGenerator): RedirectResponse
    {
        $validated = $request->validate([
            'asset_category_id' => ['required', Rule::exists('asset_categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:180'],
            'external_barcode' => ['nullable', 'string', 'max:255', 'unique:asset_identifiers,value'],
            'condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged', 'beyond_repair'])],
            'ownership_type' => ['required', Rule::in(['purchased', 'donated', 'leased', 'borrowed', 'grant_funded'])],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'acquired_on' => ['nullable', 'date', 'before_or_equal:today'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'warranty_expires_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'attributes' => ['nullable', 'array'],
            'custom_properties' => ['nullable', 'array', 'max:30'],
            'custom_properties.*.name' => ['required_with:custom_properties.*.value', 'nullable', 'string', 'max:100', 'regex:/^[\pL\pN ]+$/u'],
            'custom_properties.*.value' => ['required_with:custom_properties.*.name', 'nullable', 'string', 'max:255', 'regex:/^[\pL\pN ]+$/u'],
        ]);

        $category = AssetCategory::with(['group', 'attributes'])->findOrFail($validated['asset_category_id']);
        $submittedAttributes = collect($request->input('attributes', []));
        $this->validateCategoryAttributes($category, $submittedAttributes);

        $customProperties = $this->validatedCustomProperties($validated['custom_properties'] ?? []);

        $asset = DB::transaction(function () use ($validated, $submittedAttributes, $customProperties, $category, $tagGenerator, $request) {
            $assetTag = $tagGenerator->next($category);
            $asset = Asset::create([
                ...collect($validated)->except(['external_barcode', 'attributes', 'custom_properties'])->all(),
                'public_id' => (string) Str::ulid(),
                'asset_tag' => $assetTag,
                'registered_by' => $request->user()->id,
                'organizational_unit_id' => $request->user()->organizational_unit_id,
                'lifecycle_status' => 'in_stock',
                'currency' => Str::upper($validated['currency']),
            ]);

            $asset->identifiers()->createMany(array_values(array_filter([
                ['type' => 'asset_tag', 'value' => $assetTag, 'is_primary' => true],
                filled($validated['external_barcode'] ?? null) ? ['type' => 'external_barcode', 'value' => $validated['external_barcode'], 'is_primary' => false] : null,
                ['type' => 'qr_token', 'value' => Str::random(48), 'is_primary' => false],
            ])));

            $asset->customProperties()->createMany($customProperties);

            foreach ($category->attributes as $attribute) {
                $value = $submittedAttributes->get((string) $attribute->id);
                if (blank($value)) {
                    continue;
                }
                $column = match ($attribute->data_type) {
                    'number' => 'number_value',
                    'date' => 'date_value',
                    'boolean' => 'boolean_value',
                    'multiselect', 'json' => 'json_value',
                    default => 'text_value',
                };
                $asset->attributeValues()->create([
                    'attribute_definition_id' => $attribute->id,
                    $column => $column === 'json_value' ? (array) $value : $value,
                ]);
            }

            $asset->events()->create([
                'actor_user_id' => $request->user()->id,
                'event_type' => 'registered',
                'summary' => 'Asset registered in EIMS',
                'after' => ['asset_tag' => $assetTag, 'condition' => $asset->condition, 'lifecycle_status' => $asset->lifecycle_status],
                'occurred_at' => now(),
            ]);

            return $asset;
        }, 3);

        return redirect()->route('assets.show', $asset)->with('success', "Asset {$asset->asset_tag} registered successfully.");
    }

    public function show(Asset $asset): View
    {
        $asset->load(['category.group', 'location', 'custodian', 'custodianDepartment', 'registrar', 'identifiers', 'events.actor', 'attributeValues.definition', 'customProperties']);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset): View
    {
        abort_unless($asset->isEditable(), 423, 'This asset can no longer be edited because it has assignment history.');

        $asset->load(['category.group', 'category.attributes', 'attributeValues', 'customProperties', 'identifiers']);

        return view('assets.edit', [
            'asset' => $asset,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'externalBarcode' => $asset->identifiers->firstWhere('type', 'external_barcode')?->value,
            'attributeValues' => $asset->attributeValues->mapWithKeys(function ($value) {
                $stored = $value->text_value ?? $value->number_value ?? optional($value->date_value)->format('Y-m-d');

                return [$value->attribute_definition_id => $stored];
            }),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($asset->isEditable(), 423, 'This asset can no longer be edited because it has assignment history.');

        $existingBarcode = $asset->identifiers()->where('type', 'external_barcode')->first();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:180'],
            'external_barcode' => ['nullable', 'string', 'max:255', Rule::unique('asset_identifiers', 'value')->ignore($existingBarcode?->id)],
            'condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged', 'beyond_repair'])],
            'ownership_type' => ['required', Rule::in(['purchased', 'donated', 'leased', 'borrowed', 'grant_funded'])],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'acquired_on' => ['nullable', 'date', 'before_or_equal:today'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'warranty_expires_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'attributes' => ['nullable', 'array'],
            'custom_properties' => ['nullable', 'array', 'max:30'],
            'custom_properties.*.name' => ['required_with:custom_properties.*.value', 'nullable', 'string', 'max:100', 'regex:/^[\pL\pN ]+$/u'],
            'custom_properties.*.value' => ['required_with:custom_properties.*.name', 'nullable', 'string', 'max:255', 'regex:/^[\pL\pN ]+$/u'],
        ]);

        $asset->category->load('attributes');
        $submittedAttributes = collect($validated['attributes'] ?? []);
        $this->validateCategoryAttributes($asset->category, $submittedAttributes);
        $customProperties = $this->validatedCustomProperties($validated['custom_properties'] ?? []);

        DB::transaction(function () use ($asset, $validated, $submittedAttributes, $customProperties, $request, $existingBarcode) {
            $before = $asset->only(['name', 'condition', 'location_id', 'serial_number']);
            $asset->update([
                ...collect($validated)->except(['external_barcode', 'attributes', 'custom_properties'])->all(),
                'currency' => Str::upper($validated['currency']),
            ]);

            if (filled($validated['external_barcode'] ?? null)) {
                $asset->identifiers()->updateOrCreate(
                    ['type' => 'external_barcode'],
                    ['value' => $validated['external_barcode'], 'is_primary' => false],
                );
            } elseif ($existingBarcode) {
                $existingBarcode->delete();
            }

            $asset->customProperties()->delete();
            $asset->customProperties()->createMany($customProperties);
            $asset->attributeValues()->delete();
            $this->storeAttributeValues($asset, $asset->category, $submittedAttributes);

            $asset->events()->create([
                'actor_user_id' => $request->user()->id,
                'event_type' => 'updated',
                'summary' => 'Asset details updated before assignment',
                'before' => $before,
                'after' => $asset->fresh()->only(['name', 'condition', 'location_id', 'serial_number']),
                'occurred_at' => now(),
            ]);
        });

        return redirect()->route('assets.show', $asset)->with('success', "Asset {$asset->asset_tag} updated successfully.");
    }

    /** @return array<int, array{name: string, key: string, value: string}> */
    private function validatedCustomProperties(array $properties): array
    {
        $normalized = collect($properties)
            ->filter(fn ($property) => filled($property['name'] ?? null) || filled($property['value'] ?? null))
            ->map(fn ($property) => [
                'name' => trim($property['name']),
                'key' => Str::slug(trim($property['name']), '_'),
                'value' => trim($property['value']),
            ]);

        if ($normalized->pluck('key')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['custom_properties' => 'Each additional property name must be unique for this asset.']);
        }

        return $normalized->values()->all();
    }

    private function storeAttributeValues(Asset $asset, AssetCategory $category, $submittedAttributes): void
    {
        foreach ($category->attributes as $attribute) {
            $value = $submittedAttributes->get((string) $attribute->id);
            if (blank($value)) {
                continue;
            }
            $column = match ($attribute->data_type) {
                'number' => 'number_value',
                'date' => 'date_value',
                'boolean' => 'boolean_value',
                'multiselect', 'json' => 'json_value',
                default => 'text_value',
            };
            $asset->attributeValues()->create([
                'attribute_definition_id' => $attribute->id,
                $column => $column === 'json_value' ? (array) $value : $value,
            ]);
        }
    }

    private function validateCategoryAttributes(AssetCategory $category, $submittedAttributes): void
    {
        $allowedAttributeIds = $category->attributes->pluck('id')->map(fn ($id) => (string) $id);
        if ($submittedAttributes->keys()->diff($allowedAttributeIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['attributes' => 'One or more attributes do not belong to this asset category.']);
        }

        foreach ($category->attributes as $attribute) {
            $value = $submittedAttributes->get((string) $attribute->id);
            if ($attribute->pivot->is_required && blank($value)) {
                throw ValidationException::withMessages(["attributes.{$attribute->id}" => "{$attribute->name} is required."]);
            }
            if (filled($value) && $attribute->data_type === 'number' && ! is_numeric($value)) {
                throw ValidationException::withMessages(["attributes.{$attribute->id}" => "{$attribute->name} must be a number."]);
            }
            if (filled($value) && $attribute->data_type === 'date' && strtotime((string) $value) === false) {
                throw ValidationException::withMessages(["attributes.{$attribute->id}" => "{$attribute->name} must be a valid date."]);
            }
            if (filled($value) && $attribute->data_type === 'select' && ! in_array($value, $attribute->options ?? [], true)) {
                throw ValidationException::withMessages(["attributes.{$attribute->id}" => "{$attribute->name} contains an invalid selection."]);
            }
        }
    }
}
