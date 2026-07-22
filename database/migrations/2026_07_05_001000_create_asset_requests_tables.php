<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  if (!Schema::hasTable('asset_requests')) {
      Schema::create('asset_requests', function (Blueprint $t) {
          $t->id();
          $t->ulid('public_id')->unique();
          $t->string('request_number', 40)->unique();
          $t->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
          $t->foreignId('asset_category_id')->constrained()->restrictOnDelete();
          $t->foreignId('preferred_location_id')->nullable()->constrained('locations')->nullOnDelete();
          $t->string('purpose', 500);
          $t->text('justification');
          $t->string('status', 30)->default('submitted')->index();
          $t->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
          $t->text('decision_comments')->nullable();
          $t->timestamp('submitted_at');
          $t->timestamp('decided_at')->nullable();
          $t->timestamps();
      });
  }
  if (!Schema::hasTable('asset_request_properties')) {
      Schema::create('asset_request_properties', function (Blueprint $t) {
          $t->id();
          $t->foreignId('asset_request_id')->constrained()->cascadeOnDelete();
          $t->string('name', 100);
          $t->string('key', 110);
          $t->string('value', 255);
          $t->timestamps();
          $t->unique(['asset_request_id', 'key']);
      });
  }
  if (Schema::hasTable('asset_assignments')) {
      Schema::table('asset_assignments', function (Blueprint $t) {
          try {
              $t->foreignId('asset_request_id')->nullable()->after('active_asset_id')->unique()->constrained()->nullOnDelete();
          } catch (\Exception $e) {
              // Column already exists, continue
          }
      });
  }
 }
 public function down(): void {Schema::table('asset_assignments',fn(Blueprint $t)=>$t->dropConstrainedForeignId('asset_request_id'));Schema::dropIfExists('asset_request_properties');Schema::dropIfExists('asset_requests');}
};
