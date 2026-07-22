<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResponsibilityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registers_category_scoped_maintenance_and_review_officers(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $laptop = AssetCategory::where('code', 'LAP')->firstOrFail();

        $this->actingAs($admin)->post(route('administration.users.store'), [
            'name' => 'Maintenance Specialist',
            'staff_number' => 'MAINT-100',
            'email' => 'maintenance100@eims.local',
            'role_id' => Role::where('slug', 'maintenance-officer')->value('id'),
            'department_id' => $admin->department_id,
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
        ])->assertSessionHasErrors('maintenance_categories');

        $this->actingAs($admin)->post(route('administration.users.store'), [
            'name' => 'Maintenance Specialist',
            'staff_number' => 'MAINT-100',
            'email' => 'maintenance100@eims.local',
            'role_id' => Role::where('slug', 'maintenance-officer')->value('id'),
            'department_id' => $admin->department_id,
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
            'maintenance_categories' => [$laptop->id],
        ])->assertRedirect(route('administration.users.index'));

        $officer = User::where('staff_number', 'MAINT-100')->firstOrFail();
        $this->assertDatabaseHas('maintenance_category_responsibilities', [
            'user_id' => $officer->id,
            'asset_category_id' => $laptop->id,
            'responsibility' => 'maintenance',
        ]);

        $this->actingAs($admin)->post(route('administration.users.store'), [
            'name' => 'Maintenance Reviewer',
            'staff_number' => 'MRC-100',
            'email' => 'review100@eims.local',
            'role_id' => Role::where('slug', 'maintenance-review-officer')->value('id'),
            'department_id' => $admin->department_id,
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
            'review_categories' => [$laptop->id],
        ])->assertRedirect(route('administration.users.index'));

        $this->assertDatabaseHas('maintenance_category_responsibilities', [
            'user_id' => User::where('staff_number', 'MRC-100')->value('id'),
            'asset_category_id' => $laptop->id,
            'responsibility' => 'review',
        ]);
    }

    public function test_system_administrator_cannot_access_procurement_assignment_actions(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();

        $this->actingAs($admin)->get(route('assignments.create'))->assertForbidden();
        $this->assertFalse($admin->hasPermission('assets.assign'));
    }
}
