<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetInspection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetInspectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_officer_schedules_and_completes_inspection_without_silently_changing_asset(): void
    {
        $this->seed();
        $scheduler = $this->user('INSP-1', 'procurement-officer');
        $inspector = $this->user('INSP-2', 'maintenance-review-officer');
        $asset = $this->asset($scheduler);
        $this->actingAs($scheduler)->post(route('inspections.store'), ['asset_id' => $asset->id, 'inspector_user_id' => $inspector->id, 'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s')])->assertSessionHasNoErrors();
        $inspection = AssetInspection::firstOrFail();
        $this->assertSame(1, $inspector->unreadNotifications()->count());
        $this->actingAs($inspector)->post(route('inspections.complete', $inspection), ['physical_status' => 'present', 'location_verified' => 0, 'custody_verified' => 1, 'condition_assessed' => 'damaged', 'findings' => 'Asset is present but has visible casing damage.', 'recommendation' => 'maintenance', 'recommendation_notes' => 'Submit a maintenance case for technical assessment.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asset_inspections', ['id' => $inspection->id, 'status' => 'completed', 'followup_status' => 'required']);
        $this->assertSame('good', $asset->fresh()->condition);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'inspection_completed']);
    }

    public function test_staff_cannot_schedule_or_complete_another_officers_inspection(): void
    {
        $this->seed();
        $manager = $this->user('INSP-3', 'maintenance-review-officer');
        $staff = $this->user('INSP-4', 'staff-member');
        $asset = $this->asset($manager);
        $this->actingAs($staff)->get(route('inspections.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('inspections.store'), ['asset_id' => $asset->id, 'inspector_user_id' => $manager->id, 'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s')]);
        $this->actingAs($staff)->post(route('inspections.complete', AssetInspection::first()), [])->assertForbidden();
    }

    private function asset(User $u): Asset
    {
        return Asset::create(['public_id' => (string) Str::ulid(), 'asset_tag' => 'EIMS-INSP-'.Str::upper(Str::random(6)), 'verification_token' => Str::random(48), 'asset_category_id' => AssetCategory::where('code', 'LAP')->value('id'), 'name' => 'Inspection Test Asset', 'condition' => 'good', 'lifecycle_status' => 'in_stock', 'ownership_type' => 'purchased', 'currency' => 'TZS', 'registered_by' => $u->id]);
    }

    private function user(string $staff, string $role): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $u = User::create(['public_id' => (string) Str::ulid(), 'staff_number' => $staff, 'name' => $staff, 'email' => strtolower($staff).'@eims.local', 'status' => 'active', 'organizational_unit_id' => $admin->organizational_unit_id, 'department_id' => $admin->department_id, 'primary_location_id' => $admin->primary_location_id, 'password' => 'Password123']);
        $u->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $u;
    }
}
