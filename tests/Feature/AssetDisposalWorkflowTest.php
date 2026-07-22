<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetDisposalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewed_disposal_can_be_approved_and_finalized_by_same_procurement_officer(): void
    {
        $this->seed();
        $proc = $this->user('DSP-P', 'procurement-officer');
        $reviewer = $this->user('DSP-R', 'maintenance-review-officer');
        $asset = $this->asset($proc);
        $this->actingAs($proc)->post(route('disposals.store', $asset), ['reason' => 'obsolete', 'justification' => 'The equipment is unsupported and no longer fit for university operations.'])->assertSessionHasNoErrors();
        $d = AssetDisposal::firstOrFail();
        $this->actingAs($reviewer)->post(route('disposals.review', $d), ['decision' => 'verified', 'comments' => 'Technical inspection confirms the equipment is obsolete.'])->assertSessionHasNoErrors();
        $this->actingAs($proc)->post(route('disposals.approve', $d), ['decision' => 'approved', 'comments' => 'Disposal is authorized following independent verification.'])->assertSessionHasNoErrors();
        $this->actingAs($proc)->post(route('disposals.finalize', $d), ['disposal_method' => 'scrapped', 'disposed_on' => today()->toDateString(), 'witness_name' => 'Independent Witness', 'finalization_comments' => 'Asset physically destroyed and recorded in the disposal register.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asset_disposals', ['id' => $d->id, 'status' => 'completed', 'approved_by_user_id' => $proc->id, 'finalized_by_user_id' => $proc->id, 'disposal_method' => 'scrapped']);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'lifecycle_status' => 'disposed']);
    }

    public function test_disposal_cannot_skip_review_or_use_returned_to_owner(): void
    {
        $this->seed();
        $proc = $this->user('DSP-P2', 'procurement-officer');
        $asset = $this->asset($proc);
        $this->actingAs($proc)->post(route('disposals.store', $asset), ['reason' => 'unsafe', 'justification' => 'Unsafe electrical condition confirmed during assessment.']);
        $d = AssetDisposal::firstOrFail();
        $this->actingAs($proc)->post(route('disposals.approve', $d), ['decision' => 'approved', 'comments' => 'Attempt to skip review'])->assertSessionHasErrors('decision');
        $d->update(['status' => 'ready_for_finalization']);
        $this->actingAs($proc)->post(route('disposals.finalize', $d), ['disposal_method' => 'returned_to_owner', 'disposed_on' => today()->toDateString(), 'witness_name' => 'Witness', 'finalization_comments' => 'Invalid method attempt'])->assertSessionHasErrors('disposal_method');
    }

    private function asset(User $u): Asset
    {
        return Asset::create(['public_id' => (string) Str::ulid(), 'asset_tag' => 'EIMS-DSP-'.Str::upper(Str::random(6)), 'verification_token' => Str::random(48), 'asset_category_id' => AssetCategory::where('code', 'LAP')->value('id'), 'name' => 'Disposal Test Asset', 'condition' => 'damaged', 'lifecycle_status' => 'in_stock', 'ownership_type' => 'purchased', 'currency' => 'TZS', 'registered_by' => $u->id]);
    }

    private function user(string $staff, string $role): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $u = User::create(['public_id' => (string) Str::ulid(), 'staff_number' => $staff, 'name' => $staff, 'email' => strtolower($staff).'@eims.local', 'status' => 'active', 'organizational_unit_id' => $admin->organizational_unit_id, 'department_id' => $admin->department_id, 'primary_location_id' => $admin->primary_location_id, 'password' => 'Password123']);
        $u->roles()->attach(Role::where('slug', $role)->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $u;
    }
}
