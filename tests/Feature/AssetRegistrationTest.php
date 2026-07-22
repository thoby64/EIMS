<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AttributeDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_register_an_asset_with_typed_attributes(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $laptop = AssetCategory::where('code', 'LAP')->firstOrFail();
        $ram = AttributeDefinition::where('slug', 'ram_gb')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('assets.store'), [
            'asset_category_id' => $laptop->id,
            'name' => 'Research Office Laptop',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad T14',
            'serial_number' => 'LAPTOP-SERIAL-001',
            'external_barcode' => 'BARCODE-001',
            'condition' => 'excellent',
            'ownership_type' => 'purchased',
            'currency' => 'TZS',
            'attributes' => [$ram->id => '16'],
            'custom_properties' => [
                ['name' => 'Internal Reference', 'value' => 'RESEARCH2026'],
            ],
        ]);

        $asset = Asset::firstOrFail();
        $response->assertRedirect(route('assets.show', $asset));
        $this->assertSame('EIMS-ICT-LAP-'.now()->year.'-000001', $asset->asset_tag);
        $this->assertDatabaseHas('asset_attribute_values', [
            'asset_id' => $asset->id,
            'attribute_definition_id' => $ram->id,
            'number_value' => 16,
        ]);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'registered']);
        $this->assertDatabaseHas('asset_identifiers', ['asset_id' => $asset->id, 'type' => 'external_barcode', 'value' => 'BARCODE-001']);
        $this->assertDatabaseHas('asset_custom_properties', ['asset_id' => $asset->id, 'key' => 'internal_reference', 'value' => 'RESEARCH2026']);
    }

    public function test_asset_tag_sequence_increments_per_category_and_year(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $category = AssetCategory::where('code', 'CHR')->firstOrFail();

        foreach (['Executive Chair', 'Visitor Chair'] as $name) {
            $this->actingAs($admin)->post(route('assets.store'), [
                'asset_category_id' => $category->id,
                'name' => $name,
                'condition' => 'good',
                'ownership_type' => 'purchased',
                'currency' => 'TZS',
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame([
            'EIMS-FUR-CHR-'.now()->year.'-000001',
            'EIMS-FUR-CHR-'.now()->year.'-000002',
        ], Asset::orderBy('id')->pluck('asset_tag')->all());
    }
}
