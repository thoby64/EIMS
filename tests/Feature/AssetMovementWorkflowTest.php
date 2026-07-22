<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetMovement;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetMovementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_custodian_transfer_requires_procurement_approval_then_recipient_receipt(): void
    {
        $this->seed();
        [$procurement, $custodian, $recipient] = [$this->user('PROC-M', 'procurement-officer'), $this->user('FROM-M'), $this->user('TO-M')];
        $department = Department::where('code', 'MAINT')->firstOrFail();
        $recipient->update(['department_id' => $department->id]);
        [$asset, $old] = $this->assignedAsset($procurement, $custodian);

        $this->actingAs($custodian)->post(route('movements.store', $asset), [
            'type' => 'transfer', 'target_type' => 'individual', 'target_department_id' => $department->id,
            'target_user_id' => $recipient->id, 'target_location_id' => $recipient->primary_location_id,
            'condition_reported' => 'good', 'reason' => 'Responsibilities moved to the receiving officer',
        ])->assertSessionHasNoErrors();
        $movement = AssetMovement::firstOrFail();
        $this->assertSame('pending_procurement', $movement->status);
        $this->assertSame('active', $old->fresh()->status);

        $this->actingAs($procurement)->post(route('movements.decision', $movement), ['decision' => 'approved', 'comments' => 'Approved for physical handover'])->assertSessionHasNoErrors();
        $this->assertSame('awaiting_receipt', $movement->fresh()->status);
        $this->assertSame($custodian->id, $asset->fresh()->custodian_user_id);

        $this->actingAs($recipient)->post(route('movements.confirm', $movement), ['decision' => 'accepted', 'condition_confirmed' => 'good', 'comments' => 'Asset and tag verified'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asset_movements', ['id' => $movement->id, 'status' => 'completed', 'confirmed_by_user_id' => $recipient->id]);
        $this->assertDatabaseHas('asset_assignments', ['id' => $old->id, 'status' => 'returned', 'active_asset_id' => null]);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'custodian_user_id' => $recipient->id, 'custodian_department_id' => null]);
        $this->assertSame(1, AssetAssignment::where('asset_id', $asset->id)->whereNotNull('active_asset_id')->count());
    }

    public function test_rejected_transfer_preserves_original_custody(): void
    {
        $this->seed();
        $procurement = $this->user('PROC-R', 'procurement-officer');
        $custodian = $this->user('FROM-R');
        $recipient = $this->user('TO-R');
        $department = Department::findOrFail($recipient->department_id);
        [$asset, $old] = $this->assignedAsset($procurement, $custodian);
        $this->actingAs($custodian)->post(route('movements.store', $asset), ['type' => 'transfer', 'target_type' => 'individual', 'target_department_id' => $department->id, 'target_user_id' => $recipient->id, 'target_location_id' => $recipient->primary_location_id, 'condition_reported' => 'good', 'reason' => 'Requested operational reallocation']);
        $movement = AssetMovement::firstOrFail();
        $this->actingAs($procurement)->post(route('movements.decision', $movement), ['decision' => 'rejected', 'comments' => 'Custody remains required in the current office']);
        $this->assertSame('active', $old->fresh()->status);
        $this->assertSame($custodian->id, $asset->fresh()->custodian_user_id);
        $this->assertDatabaseHas('asset_movements', ['id' => $movement->id, 'status' => 'rejected']);
    }

    public function test_procurement_accepts_return_and_releases_asset_to_stock(): void
    {
        $this->seed();
        $procurement = $this->user('PROC-B', 'procurement-officer');
        $custodian = $this->user('FROM-B');
        [$asset, $old] = $this->assignedAsset($procurement, $custodian);
        $this->actingAs($custodian)->post(route('movements.store', $asset), ['type' => 'return', 'condition_reported' => 'fair', 'reason' => 'No longer required by this office']);
        $movement = AssetMovement::firstOrFail();
        $this->actingAs($procurement)->post(route('movements.decision', $movement), ['decision' => 'approved', 'condition_confirmed' => 'fair', 'comments' => 'Returned asset physically inspected and accepted'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'lifecycle_status' => 'in_stock', 'custodian_user_id' => null]);
        $this->assertDatabaseHas('asset_assignments', ['id' => $old->id, 'status' => 'returned', 'active_asset_id' => null]);
        $this->assertDatabaseHas('asset_movements', ['id' => $movement->id, 'status' => 'completed']);
    }

    private function assignedAsset(User $procurement, User $custodian): array
    {
        $asset = Asset::create(['public_id' => (string) Str::ulid(), 'asset_tag' => 'EIMS-T-'.Str::upper(Str::random(5)), 'verification_token' => Str::random(48), 'asset_category_id' => AssetCategory::where('code', 'LAP')->value('id'), 'name' => 'Movement Test Asset', 'condition' => 'good', 'lifecycle_status' => 'assigned', 'ownership_type' => 'purchased', 'currency' => 'TZS', 'custodian_user_id' => $custodian->id, 'location_id' => $custodian->primary_location_id, 'registered_by' => $procurement->id]);
        $assignment = $asset->assignments()->create(['public_id' => (string) Str::ulid(), 'active_asset_id' => $asset->id, 'assignment_type' => 'individual', 'assigned_to_user_id' => $custodian->id, 'location_id' => $custodian->primary_location_id, 'assigned_by' => $procurement->id, 'assigned_at' => now(), 'condition_at_issue' => 'good', 'purpose' => 'Official duties', 'status' => 'active']);

        return [$asset, $assignment];
    }

    private function user(string $staff, string $role = 'staff-member'): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $user = User::create(['public_id' => (string) Str::ulid(), 'staff_number' => $staff, 'name' => $staff, 'email' => strtolower($staff).'@eims.local', 'status' => 'active', 'organizational_unit_id' => $admin->organizational_unit_id, 'department_id' => $admin->department_id, 'primary_location_id' => $admin->primary_location_id, 'password' => 'Password123']);
        $user->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $user;
    }
}
