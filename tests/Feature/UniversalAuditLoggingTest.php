<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class UniversalAuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_failure_success_and_logout_are_audited_without_passwords(): void
    {
        $this->seed();
        $user = $this->user('AUD-LOGIN', 'staff-member');
        $this->post(route('login.store'), ['identity' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('identity');
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed', 'actor_identity' => $user->email, 'outcome' => 'failure']);
        $this->post(route('login.store'), ['identity' => $user->email, 'password' => 'Password123'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_succeeded', 'actor_user_id' => $user->id]);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'logout', 'actor_user_id' => $user->id]);
        $this->assertStringNotContainsString('wrong-password', AuditLog::where('action', 'login_failed')->firstOrFail()->toJson());
    }

    public function test_model_changes_requests_and_permission_failures_are_audited(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $staff = $this->user('AUD-STAFF', 'staff-member');
        $staff->update(['phone' => '0711111111']);
        $log = AuditLog::where('event_type', 'model')->where('auditable_type', User::class)->where('auditable_id', $staff->id)->where('action', 'updated')->latest()->firstOrFail();
        $this->assertSame('0711111111', $log->new_values['phone']);
        $this->actingAs($staff)->get(route('reports.index'))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $staff->id, 'event_type' => 'request', 'outcome' => 'failure', 'http_status' => 403]);
        $this->actingAs($admin)->get(route('audit.index'))->assertOk()->assertSee('System audit logs');
        $this->actingAs($admin)->get(route('audit.show', $log))->assertOk()->assertSee('Previous values')->assertSee('Recorded values')->assertSee('0711111111');
        $this->actingAs($staff)->get(route('audit.show', $log))->assertForbidden();
    }

    public function test_audit_records_are_append_only(): void
    {
        $this->seed();
        app(AuditLogger::class)->write('security', 'append_only_test');
        $log = AuditLog::firstOrFail();
        $this->expectException(LogicException::class);
        $log->update(['action' => 'tampered']);
    }

    private function user(string $staff, string $role): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $u = User::create(['public_id' => (string) Str::ulid(), 'staff_number' => $staff, 'name' => $staff, 'email' => strtolower($staff).'@eims.local', 'status' => 'active', 'organizational_unit_id' => $admin->organizational_unit_id, 'department_id' => $admin->department_id, 'primary_location_id' => $admin->primary_location_id, 'password' => 'Password123']);
        $u->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $u;
    }
}
