<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (!Schema::hasTable('departments')) {
      Schema::create('departments', function (Blueprint $t) {
          $t->id();
          $t->ulid('public_id')->unique();
          $t->string('name', 150)->unique();
          $t->string('code', 30)->unique();
          $t->text('description')->nullable();
          $t->boolean('is_active')->default(true)->index();
          $t->timestamps();
      });
  }
  if (Schema::hasTable('users') && !Schema::hasColumn('users', 'department_id')) {
      Schema::table('users', function (Blueprint $t) {
          $t->foreignId('department_id')->nullable()->after('organizational_unit_id')->constrained()->nullOnDelete();
      });
  }
  if (Schema::hasTable('assets') && !Schema::hasColumn('assets', 'custodian_department_id')) {
      Schema::table('assets', function (Blueprint $t) {
          $t->foreignId('custodian_department_id')->nullable()->after('custodian_user_id')->constrained('departments')->nullOnDelete();
      });
  }
  if (Schema::hasTable('asset_assignments')) {
      Schema::table('asset_assignments', function (Blueprint $t) {
          if (!Schema::hasColumn('asset_assignments', 'assignment_type')) {
              $t->string('assignment_type', 20)->default('individual')->after('asset_request_id')->index();
          }
          if (!Schema::hasColumn('asset_assignments', 'assigned_to_department_id')) {
              $t->foreignId('assigned_to_department_id')->nullable()->after('assigned_to_unit_id')->constrained('departments')->nullOnDelete();
          }
      });
  }
  if (!Schema::hasTable('asset_assignment_receivers')) {
      Schema::create('asset_assignment_receivers', function (Blueprint $t) {
          $t->id();
          $t->foreignId('asset_assignment_id')->constrained()->cascadeOnDelete();
          $t->foreignId('user_id')->constrained()->cascadeOnDelete();
          $t->string('status', 20)->default('pending')->index();
          $t->timestamp('confirmed_at')->nullable();
          $t->timestamps();
          $t->unique(['asset_assignment_id', 'user_id'], 'assignment_receiver_unique');
      });
  }
 }
 public function down(): void {Schema::dropIfExists('asset_assignment_receivers');Schema::table('asset_assignments',function(Blueprint $t){$t->dropConstrainedForeignId('assigned_to_department_id');$t->dropColumn('assignment_type');});Schema::table('assets',fn(Blueprint $t)=>$t->dropConstrainedForeignId('custodian_department_id'));Schema::table('users',fn(Blueprint $t)=>$t->dropConstrainedForeignId('department_id'));Schema::dropIfExists('departments');}
};
