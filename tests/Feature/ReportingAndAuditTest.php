<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportingAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_filters_and_exports_same_asset_register(): void
    {
        $this->seed();
        $proc = $this->user('REP-P', 'procurement-officer');
        $laptop = $this->asset($proc, 'Filtered Laptop', 'good', 'assigned');
        $this->asset($proc, 'Excluded Chair', 'fair', 'in_stock', AssetCategory::where('code', 'CHR')->value('id'));
        $this->actingAs($proc)->get(route('reports.assets', ['status' => 'assigned', 'category' => $laptop->asset_category_id]))->assertOk()->assertSee('Filtered Laptop')->assertDontSee('Excluded Chair');
        $csv = $this->actingAs($proc)->get(route('reports.assets.export', ['status' => 'assigned', 'category' => $laptop->asset_category_id]));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Filtered Laptop', $content);
        $this->assertStringNotContainsString('Excluded Chair', $content);
    }

    public function test_auditor_views_events_but_staff_cannot_view_reports_or_audit(): void
    {
        $this->seed();
        $proc = $this->user('REP-P2', 'procurement-officer');
        $auditor = $this->user('REP-A', 'auditor');
        $staff = $this->user('REP-S', 'staff-member');
        $asset = $this->asset($proc, 'Audited Asset', 'good', 'in_stock');
        $asset->events()->create(['actor_user_id' => $proc->id, 'event_type' => 'test_event', 'summary' => 'Traceable lifecycle event', 'occurred_at' => now()]);
        $audit = AuditLog::where('new_values->summary', 'Traceable lifecycle event')->firstOrFail();
        $this->actingAs($auditor)->get(route('audit.index'))->assertOk()->assertSee('View details');
        $this->actingAs($auditor)->get(route('audit.show', $audit))->assertOk()->assertSee('Traceable lifecycle event');
        $this->actingAs($staff)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('audit.index'))->assertForbidden();
    }

    private function asset(User $u, string $name, string $condition, string $status, ?int $category = null): Asset
    {
        return Asset::create(['public_id' => (string) Str::ulid(), 'asset_tag' => 'EIMS-REP-'.Str::upper(Str::random(6)), 'verification_token' => Str::random(48), 'asset_category_id' => $category ?: AssetCategory::where('code', 'LAP')->value('id'), 'name' => $name, 'condition' => $condition, 'lifecycle_status' => $status, 'ownership_type' => 'purchased', 'currency' => 'TZS', 'registered_by' => $u->id]);
    }

    private function user(string $staff, string $role): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $u = User::create(['public_id' => (string) Str::ulid(), 'staff_number' => $staff, 'name' => $staff, 'email' => strtolower($staff).'@eims.local', 'status' => 'active', 'organizational_unit_id' => $admin->organizational_unit_id, 'department_id' => $admin->department_id, 'primary_location_id' => $admin->primary_location_id, 'password' => 'Password123']);
        $u->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $u;
    }
}
