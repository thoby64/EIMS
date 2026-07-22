<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetIdentityAndEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_barcode_public_verification_and_authenticated_scan_are_safe_and_functional(): void
    {
        [$admin, $asset] = $this->registerAsset();

        $this->actingAs($admin)->get(route('assets.label.qr', $asset))
            ->assertOk()->assertHeader('content-type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('<svg', false);
        $this->actingAs($admin)->get(route('assets.label.barcode', $asset))
            ->assertOk()->assertHeader('content-type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('<svg', false);

        $token = $asset->identifiers()->where('type', 'qr_token')->value('value');
        $this->get(route('assets.verify', $token))
            ->assertOk()
            ->assertSee($asset->asset_tag)
            ->assertDontSee('PRIVATE-SERIAL-77')
            ->assertDontSee('Main Campus')
            ->assertDontSee('2500000');

        $this->actingAs($admin)->post(route('assets.scan.lookup'), [
            'code' => route('assets.verify', $token),
        ])->assertRedirect(route('assets.show', $asset));
    }

    public function test_asset_can_be_edited_before_assignment_and_is_permanently_locked_after_assignment(): void
    {
        [$admin, $asset] = $this->registerAsset();

        $this->actingAs($admin)->patch(route('assets.update', $asset), [
            'name' => 'Updated Office Chair',
            'condition' => 'good',
            'ownership_type' => 'purchased',
            'currency' => 'TZS',
            'custom_properties' => [
                ['name' => 'Fabric Type', 'value' => 'Leather'],
                ['name' => 'Maximum Load', 'value' => '150KG'],
            ],
        ])->assertRedirect(route('assets.show', $asset));

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'name' => 'Updated Office Chair']);
        $this->assertDatabaseHas('asset_custom_properties', ['asset_id' => $asset->id, 'key' => 'maximum_load', 'value' => '150KG']);

        $asset->assignments()->create([
            'public_id' => (string) Str::ulid(),
            'assigned_to_user_id' => $admin->id,
            'location_id' => $admin->primary_location_id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'condition_at_issue' => 'good',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('assets.edit', $asset))->assertStatus(423);
        $this->actingAs($admin)->patch(route('assets.update', $asset), [
            'name' => 'Unauthorized Rewrite',
            'condition' => 'good',
            'ownership_type' => 'purchased',
            'currency' => 'TZS',
        ])->assertStatus(423);
        $this->assertDatabaseMissing('assets', ['id' => $asset->id, 'name' => 'Unauthorized Rewrite']);
    }

    public function test_additional_properties_reject_special_characters_and_duplicate_names(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $category = AssetCategory::where('code', 'CHR')->firstOrFail();

        $this->actingAs($admin)->post(route('assets.store'), [
            'asset_category_id' => $category->id,
            'name' => 'Invalid Chair',
            'condition' => 'good',
            'ownership_type' => 'purchased',
            'currency' => 'TZS',
            'custom_properties' => [['name' => 'Plate@Number', 'value' => 'ABC#123']],
        ])->assertSessionHasErrors(['custom_properties.0.name', 'custom_properties.0.value']);

        $this->actingAs($admin)->post(route('assets.store'), [
            'asset_category_id' => $category->id,
            'name' => 'Duplicate Property Chair',
            'condition' => 'good',
            'ownership_type' => 'purchased',
            'currency' => 'TZS',
            'custom_properties' => [
                ['name' => 'Plate Number', 'value' => 'ABC123'],
                ['name' => 'plate number', 'value' => 'XYZ789'],
            ],
        ])->assertSessionHasErrors('custom_properties');
    }

    /** @return array{User, Asset} */
    private function registerAsset(): array
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $category = AssetCategory::where('code', 'CHR')->firstOrFail();

        $this->actingAs($admin)->post(route('assets.store'), [
            'asset_category_id' => $category->id,
            'name' => 'Executive Office Chair',
            'serial_number' => 'PRIVATE-SERIAL-77',
            'condition' => 'excellent',
            'ownership_type' => 'purchased',
            'location_id' => $admin->primary_location_id,
            'acquisition_cost' => 2500000,
            'currency' => 'TZS',
            'custom_properties' => [['name' => 'Fabric Type', 'value' => 'Mesh']],
        ])->assertSessionHasNoErrors();

        return [$admin, Asset::firstOrFail()];
    }
}
