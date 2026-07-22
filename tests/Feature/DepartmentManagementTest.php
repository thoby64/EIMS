<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_and_update_departments(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();

        $this->actingAs($admin)->post(route('administration.departments.store'), [
            'name' => 'Finance and Accounts',
            'code' => 'fin',
            'description' => 'Financial operations.',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $department = Department::where('code', 'FIN')->firstOrFail();
        $this->assertTrue($department->is_active);

        $this->actingAs($admin)->patch(route('administration.departments.update', $department), [
            'name' => 'Finance',
            'code' => 'FIN',
            'description' => 'Finance operations.',
            'is_active' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Finance', 'is_active' => false]);
    }

    public function test_regular_staff_cannot_manage_departments(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $staff = User::create([
            'public_id' => (string) Str::ulid(),
            'staff_number' => 'DEPT-STAFF',
            'name' => 'Regular Staff',
            'email' => 'department.staff@eims.local',
            'status' => 'active',
            'department_id' => $admin->department_id,
            'password' => 'Password123',
        ]);
        $staff->roles()->attach(Role::where('slug', 'staff-member')->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        $this->actingAs($staff)->get(route('administration.departments.index'))->assertForbidden();
    }
}
