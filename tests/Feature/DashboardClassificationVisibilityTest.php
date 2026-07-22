<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardClassificationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_classification_cards_are_limited_to_operational_roles(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $staff = $this->user('DASH-STAFF', 'staff-member', $admin);
        $procurement = $this->user('DASH-PROC', 'procurement-officer', $admin);
        DB::table('asset_groups')->where('code', 'VEH')->update(['icon' => null]);

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Infrastructure groups')
            ->assertDontSee('Asset categories');

        $this->actingAs($procurement)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Infrastructure groups')
            ->assertSee('Asset categories')
            ->assertSee('Laptop')
            ->assertSee('VT')
            ->assertSee('LP');
    }

    private function user(string $staffNumber, string $role, User $admin): User
    {
        $user = User::create([
            'public_id' => (string) Str::ulid(),
            'staff_number' => $staffNumber,
            'name' => $staffNumber,
            'email' => strtolower($staffNumber).'@eims.local',
            'status' => 'active',
            'department_id' => $admin->department_id,
            'password' => 'Password123',
        ]);
        $user->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $user;
    }
}
