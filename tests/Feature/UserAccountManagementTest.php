<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_edits_user_role_status_and_category_responsibilities(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $user = $this->user('EDIT-1', 'staff-member');
        $role = Role::where('slug', 'maintenance-officer')->firstOrFail();
        $category = AssetCategory::where('code', 'LAP')->firstOrFail();
        $department = Department::where('code', 'MAINT')->firstOrFail();
        $this->actingAs($admin)->patch(route('administration.users.update', $user), ['name' => 'Updated Officer', 'staff_number' => $user->staff_number, 'email' => $user->email, 'phone' => '0712345678', 'role_id' => $role->id, 'organizational_unit_id' => $user->organizational_unit_id, 'department_id' => $department->id, 'primary_location_id' => $user->primary_location_id, 'status' => 'active', 'maintenance_categories' => [$category->id]])->assertSessionHasNoErrors();
        $this->assertSame('Updated Officer', $user->fresh()->name);
        $this->assertTrue($user->fresh()->roles()->where('slug', 'maintenance-officer')->exists());
        $this->assertDatabaseHas('maintenance_category_responsibilities', ['user_id' => $user->id, 'asset_category_id' => $category->id, 'responsibility' => 'maintenance']);
    }

    public function test_admin_resets_password_and_password_is_redacted_from_audit(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $user = $this->user('EDIT-2', 'staff-member');
        $this->actingAs($admin)->patch(route('administration.users.password', $user), ['password' => 'NewSecurePass2026', 'password_confirmation' => 'NewSecurePass2026'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewSecurePass2026', $user->fresh()->password));
        $audit = AuditLog::where('auditable_type', User::class)->where('auditable_id', $user->id)->where('action', 'updated')->latest()->firstOrFail();
        $this->assertSame('[REDACTED]', $audit->new_values['password']);
        $this->assertStringNotContainsString('NewSecurePass2026', $audit->toJson());
    }

    public function test_user_updates_allowed_profile_fields_but_cannot_change_institutional_identity(): void
    {
        $this->seed();
        $user = $this->user('EDIT-3', 'staff-member');
        $original = ['email' => $user->email, 'staff_number' => $user->staff_number, 'organizational_unit_id' => $user->organizational_unit_id, 'department_id' => $user->department_id, 'primary_location_id' => $user->primary_location_id];
        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Self Updated Name', 'phone' => '0788000000', 'primary_location_id' => null, 'email' => 'hacker@example.test', 'staff_number' => 'CHANGED', 'organizational_unit_id' => null, 'department_id' => Department::where('code', 'PROC')->value('id')])->assertSessionHasNoErrors();
        $fresh = $user->fresh();
        $this->assertSame('Self Updated Name', $fresh->name);
        $this->assertSame('0788000000', $fresh->phone);
        foreach ($original as $field => $value) {
            $this->assertSame($value, $fresh->$field);
        }
    }

    public function test_user_must_supply_current_password_to_change_it(): void
    {
        $this->seed();
        $user = $this->user('EDIT-4', 'staff-member');
        $this->actingAs($user)->patch(route('profile.password'), ['current_password' => 'WrongPassword', 'password' => 'PersonalSecure2026', 'password_confirmation' => 'PersonalSecure2026'])->assertSessionHasErrors('current_password');
        $this->actingAs($user)->patch(route('profile.password'), ['current_password' => 'Password123', 'password' => 'PersonalSecure2026', 'password_confirmation' => 'PersonalSecure2026'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('PersonalSecure2026', $user->fresh()->password));
    }

    private function user(string $staff, string $role): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $u = User::create(['public_id' => (string) Str::ulid(), 'staff_number' => $staff, 'name' => $staff, 'email' => strtolower($staff).'@eims.local', 'status' => 'active', 'organizational_unit_id' => $admin->organizational_unit_id, 'department_id' => $admin->department_id, 'primary_location_id' => $admin->primary_location_id, 'password' => 'Password123']);
        $u->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $u;
    }
}
