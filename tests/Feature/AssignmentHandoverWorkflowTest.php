<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssignmentHandoverWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_procurement_prepares_assignment_and_recipient_accepts_handover(): void
    {
        $this->seed();
        $procurement = $this->userWithRole('PROC-001', 'procurement-officer');
        $recipient = $this->userWithRole('STAFF-001', 'staff-member');
        $asset = $this->registerAsset($procurement);

        $response = $this->actingAs($procurement)->post(route('assignments.store'), [
            'assignment_type' => 'individual',
            'asset_id' => $asset->id,
            'assigned_to_user_id' => $recipient->id,
            'location_id' => $recipient->primary_location_id,
            'condition_at_issue' => 'excellent',
            'purpose' => 'Official teaching and research duties',
            'accessories' => ['Laptop Charger', 'Carrying Bag'],
        ]);

        $assignment = AssetAssignment::firstOrFail();
        $response->assertRedirect(route('assignments.show', $assignment));
        $this->assertSame('reserved', $asset->fresh()->lifecycle_status);
        $this->assertSame($asset->id, $assignment->active_asset_id);
        $this->assertSame('pending_receipt', $assignment->status);

        $this->actingAs($recipient)->post(route('handovers.store', $assignment), [
            'handed_over_by_user_id' => $procurement->id,
            'decision' => 'accepted',
            'matches_expected_asset' => true,
            'condition_received' => 'excellent',
        ])->assertRedirect(route('handovers.pending'));

        $this->assertDatabaseHas('handover_receipts', [
            'asset_assignment_id' => $assignment->id,
            'confirmed_by_user_id' => $recipient->id,
            'decision' => 'accepted',
            'matches_expected_asset' => true,
        ]);
        $this->assertDatabaseHas('asset_assignments', ['id' => $assignment->id, 'status' => 'active', 'active_asset_id' => $asset->id]);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'lifecycle_status' => 'assigned', 'custodian_user_id' => $recipient->id]);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'handover_accepted']);
    }

    public function test_rejected_handover_returns_asset_to_stock_and_requires_reason(): void
    {
        $this->seed();
        $procurement = $this->userWithRole('PROC-002', 'procurement-officer');
        $recipient = $this->userWithRole('STAFF-002', 'staff-member');
        $asset = $this->registerAsset($procurement);
        $this->actingAs($procurement)->post(route('assignments.store'), [
            'assignment_type' => 'individual',
            'asset_id' => $asset->id,
            'assigned_to_user_id' => $recipient->id,
            'location_id' => $recipient->primary_location_id,
            'condition_at_issue' => 'good',
            'purpose' => 'Department office duties',
        ]);
        $assignment = AssetAssignment::firstOrFail();

        $this->actingAs($recipient)->post(route('handovers.store', $assignment), [
            'handed_over_by_user_id' => $procurement->id,
            'decision' => 'rejected',
            'matches_expected_asset' => false,
            'condition_received' => 'damaged',
        ])->assertSessionHasErrors('remarks');

        $this->actingAs($recipient)->post(route('handovers.store', $assignment), [
            'handed_over_by_user_id' => $procurement->id,
            'decision' => 'rejected',
            'matches_expected_asset' => false,
            'condition_received' => 'damaged',
            'remarks' => 'The serial number differs from the expected property',
        ])->assertRedirect(route('handovers.pending'));

        $this->assertDatabaseHas('asset_assignments', ['id' => $assignment->id, 'status' => 'rejected', 'active_asset_id' => null]);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'lifecycle_status' => 'in_stock', 'custodian_user_id' => null]);
    }

    public function test_role_boundaries_and_category_scopes_are_enforced(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $procurement = $this->userWithRole('PROC-003', 'procurement-officer');
        $maintenance = $this->userWithRole('MAINT-001', 'maintenance-officer');
        $reviewer = $this->userWithRole('MRC-001', 'maintenance-review-officer');
        $laptop = AssetCategory::where('code', 'LAP')->firstOrFail();
        $chair = AssetCategory::where('code', 'CHR')->firstOrFail();

        $this->assertFalse($admin->hasPermission('assets.assign'));
        $this->assertTrue($procurement->hasPermission('assets.assign'));
        $this->assertTrue($procurement->hasPermission('maintenance.spares.procure'));
        $this->assertFalse($procurement->hasPermission('maintenance.review'));
        $this->assertDatabaseMissing('roles', ['slug' => 'head-ict']);

        $maintenance->maintainableCategories()->attach($laptop->id, [
            'responsibility' => 'maintenance', 'assigned_by_user_id' => $admin->id, 'is_active' => true, 'assigned_at' => now(),
        ]);
        $reviewer->reviewableMaintenanceCategories()->attach($chair->id, [
            'responsibility' => 'review', 'assigned_by_user_id' => $admin->id, 'is_active' => true, 'assigned_at' => now(),
        ]);

        $this->assertTrue($maintenance->maintainableCategories()->whereKey($laptop->id)->exists());
        $this->assertFalse($maintenance->maintainableCategories()->whereKey($chair->id)->exists());
        $this->assertTrue($reviewer->reviewableMaintenanceCategories()->whereKey($chair->id)->exists());
    }

    public function test_department_assignment_allows_any_authorized_department_officer_to_confirm(): void
    {
        $this->seed();
        $procurement = $this->userWithRole('PROC-DEP', 'procurement-officer');
        $department = Department::where('code', 'MAINT')->firstOrFail();
        $receiverOne = $this->userWithRole('DEP-ONE', 'staff-member');
        $receiverTwo = $this->userWithRole('DEP-TWO', 'staff-member');
        $receiverOne->update(['department_id' => $department->id]);
        $receiverTwo->update(['department_id' => $department->id]);
        $asset = $this->registerAsset($procurement);

        $this->actingAs($procurement)->post(route('assignments.store'), [
            'assignment_type' => 'department',
            'asset_id' => $asset->id,
            'assigned_to_department_id' => $department->id,
            'authorized_receiver_ids' => [$receiverOne->id, $receiverTwo->id],
            'location_id' => $receiverOne->primary_location_id,
            'condition_at_issue' => 'excellent',
            'purpose' => 'Shared departmental operations',
        ])->assertSessionHasNoErrors();
        $assignment = AssetAssignment::firstOrFail();

        $this->actingAs($receiverTwo)->post(route('handovers.store', $assignment), [
            'handed_over_by_user_id' => $procurement->id,
            'decision' => 'accepted',
            'matches_expected_asset' => 1,
            'condition_received' => 'excellent',
        ])->assertRedirect(route('handovers.pending'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'custodian_user_id' => null, 'custodian_department_id' => $department->id]);
        $this->assertDatabaseHas('asset_assignment_receivers', ['asset_assignment_id' => $assignment->id, 'user_id' => $receiverTwo->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('asset_assignment_receivers', ['asset_assignment_id' => $assignment->id, 'user_id' => $receiverOne->id, 'status' => 'closed']);
    }

    private function registerAsset(User $registrar): Asset
    {
        $category = AssetCategory::where('code', 'LAP')->firstOrFail();
        $this->actingAs($registrar)->post(route('assets.store'), [
            'asset_category_id' => $category->id,
            'name' => 'Assignment Test Laptop',
            'condition' => 'excellent',
            'ownership_type' => 'purchased',
            'currency' => 'TZS',
        ])->assertSessionHasNoErrors();

        return Asset::latest('id')->firstOrFail();
    }

    private function userWithRole(string $staffNumber, string $role): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $user = User::create([
            'public_id' => (string) Str::ulid(),
            'staff_number' => $staffNumber,
            'name' => str_replace('-', ' ', $staffNumber),
            'email' => strtolower($staffNumber).'@eims.local',
            'status' => 'active',
            'organizational_unit_id' => $admin->organizational_unit_id,
            'department_id' => $admin->department_id,
            'primary_location_id' => $admin->primary_location_id,
            'password' => 'Password123',
        ]);
        $user->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $user;
    }
}
