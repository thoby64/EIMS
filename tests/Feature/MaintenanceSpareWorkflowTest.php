<?php
namespace Tests\Feature;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\MaintenanceCase;
use App\Models\MaintenanceReport;
use App\Models\Role;
use App\Models\SpareRequisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaintenanceSpareWorkflowTest extends TestCase {
    use RefreshDatabase;
    public function test_approved_spare_can_repeat_until_repair_and_reporter_confirms_return(): void {
        [$reporter,$maintenance,$reviewer,$procurement,$asset]=$this->setupActors();
        $this->actingAs($reporter)->post(route('maintenance.store'),['asset_id'=>$asset->id,'problem_summary'=>'Device will not power on','problem_details'=>'No power after connecting the charger','severity'=>'high'])->assertSessionHasNoErrors();
        $case=MaintenanceCase::firstOrFail();
        $this->actingAs($maintenance)->post(route('maintenance.reports.store',$case),['technical_outcome'=>'awaiting_spare','findings'=>'Power module failed','work_performed'=>'Electrical inspection completed','spare_needed'=>1,'spare_description'=>'Power Module Model 500'])->assertSessionHasNoErrors();
        $report=MaintenanceReport::firstOrFail();
        $this->actingAs($reviewer)->post(route('maintenance.reviews.store',$report),['decision'=>'approved','comments'=>'Diagnosis and requested module verified'])->assertSessionHasNoErrors();
        $req=SpareRequisition::firstOrFail();
        $this->assertDatabaseHas('spare_requisitions',['maintenance_report_id'=>$report->id,'status'=>'pending_procurement']);
        $this->actingAs($procurement)->post(route('maintenance.spares.decision',$req),['decision'=>'approved','comments'=>'Spare available from approved supplier'])->assertSessionHasNoErrors();
        $this->actingAs($procurement)->post(route('maintenance.spares.issue',$req),['issued_items'=>'Power Module Model 500','issued_quantity'=>1])->assertSessionHasNoErrors();
        $this->actingAs($reviewer)->post(route('maintenance.spares.relay',$req))->assertSessionHasNoErrors();
        $this->actingAs($maintenance)->post(route('maintenance.spares.receive',$req),['receipt_remarks'=>'One correct sealed module received'])->assertSessionHasNoErrors();
        $this->assertSame('spare_received',$case->fresh()->status);
        $this->actingAs($maintenance)->post(route('maintenance.reports.store',$case),['technical_outcome'=>'repaired','findings'=>'Power restored after module replacement','work_performed'=>'Replaced module and completed load testing','spare_needed'=>0])->assertSessionHasNoErrors();
        $second=MaintenanceReport::where('cycle_number',2)->firstOrFail();
        $this->actingAs($reviewer)->post(route('maintenance.reviews.store',$second),['decision'=>'approved','comments'=>'Repair test results accepted'])->assertSessionHasNoErrors();
        $this->actingAs($maintenance)->post(route('maintenance.finalize',$case),['final_outcome'=>'repaired','final_reason'=>'Failed module replaced and device tested','collection_instructions'=>'Collect from the maintenance desk'])->assertSessionHasNoErrors();
        $this->assertSame('ready_for_collection',$case->fresh()->status);
        $this->actingAs($reporter)->post(route('maintenance.return',$case),['returned_by_user_id'=>$maintenance->id,'condition_received'=>'good','correct_asset'=>1,'comment'=>'Correct device received and working'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('maintenance_cases',['id'=>$case->id,'status'=>'closed','final_outcome'=>'repaired']);
        $this->assertDatabaseHas('maintenance_return_confirmations',['maintenance_case_id'=>$case->id,'confirmed_by_user_id'=>$reporter->id]);
        $this->assertSame('assigned',$asset->fresh()->lifecycle_status);
    }

    public function test_procurement_rejection_is_relayed_before_unrepaired_finalization(): void {
        [$reporter,$maintenance,$reviewer,$procurement,$asset]=$this->setupActors();
        $this->actingAs($reporter)->post(route('maintenance.store'),['asset_id'=>$asset->id,'problem_summary'=>'Broken control board','problem_details'=>'Main control board is burnt','severity'=>'critical']);$case=MaintenanceCase::firstOrFail();
        $this->actingAs($maintenance)->post(route('maintenance.reports.store',$case),['technical_outcome'=>'awaiting_spare','findings'=>'Board cannot be repaired','spare_needed'=>1,'spare_description'=>'Replacement Control Board']);$report=MaintenanceReport::firstOrFail();
        $this->actingAs($reviewer)->post(route('maintenance.reviews.store',$report),['decision'=>'approved','comments'=>'Replacement requirement verified']);$req=SpareRequisition::firstOrFail();
        $this->actingAs($procurement)->post(route('maintenance.spares.decision',$req),['decision'=>'rejected','comments'=>'Part is obsolete and unavailable']);
        $this->actingAs($maintenance)->post(route('maintenance.finalize',$case),['final_outcome'=>'not_repaired','final_reason'=>'Part unavailable'])->assertSessionHasErrors('case');
        $this->actingAs($reviewer)->post(route('maintenance.spares.relay',$req))->assertSessionHasNoErrors();
        $this->actingAs($maintenance)->post(route('maintenance.finalize',$case),['final_outcome'=>'not_repaired','final_reason'=>'Required control board is obsolete and Procurement rejected sourcing'])->assertSessionHasNoErrors();
        $this->assertSame('closed',$case->fresh()->status);
        $this->assertTrue($reporter->notifications()->where('data','like','%request a replacement%')->exists());
    }

    private function setupActors(): array {$this->seed();$admin=User::where('email','admin@eims.local')->firstOrFail();$category=AssetCategory::where('code','LAP')->firstOrFail();$reporter=$this->makeUser('REPORTER','staff-member',$admin);$maintenance=$this->makeUser('MAINTAINER','maintenance-officer',$admin);$reviewer=$this->makeUser('REVIEWER','maintenance-review-officer',$admin);$procurement=$this->makeUser('PROCUREMENT','procurement-officer',$admin);$maintenance->maintainableCategories()->attach($category->id,['responsibility'=>'maintenance','assigned_by_user_id'=>$admin->id,'is_active'=>1,'assigned_at'=>now()]);$reviewer->reviewableMaintenanceCategories()->attach($category->id,['responsibility'=>'review','assigned_by_user_id'=>$admin->id,'is_active'=>1,'assigned_at'=>now()]);$asset=Asset::create(['public_id'=>(string)Str::ulid(),'asset_category_id'=>$category->id,'registered_by'=>$admin->id,'custodian_user_id'=>$reporter->id,'location_id'=>$reporter->primary_location_id,'asset_tag'=>'EIMS-ICT-LAP-2026-900001','name'=>'Maintenance Test Laptop','condition'=>'damaged','lifecycle_status'=>'assigned','ownership_type'=>'purchased','currency'=>'TZS']);return[$reporter,$maintenance,$reviewer,$procurement,$asset];}
    private function makeUser(string $staff,string $role,User $admin): User {$u=User::create(['public_id'=>(string)Str::ulid(),'staff_number'=>$staff,'name'=>$staff,'email'=>strtolower($staff).'@eims.local','status'=>'active','organizational_unit_id'=>$admin->organizational_unit_id,'primary_location_id'=>$admin->primary_location_id,'password'=>'Password123']);$u->roles()->attach(Role::where('slug',$role)->value('id'),['assigned_by'=>$admin->id,'assigned_at'=>now()]);return $u;}
}
