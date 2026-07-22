<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maintenance_cases')) {
            Schema::create('maintenance_cases', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('asset_id')->constrained()->restrictOnDelete();
                $table->foreignId('reported_by_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('maintenance_officer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('problem_summary', 500);
                $table->text('problem_details');
                $table->string('severity', 20)->default('normal')->index();
                $table->string('status', 40)->default('reported')->index();
                $table->string('final_outcome', 30)->nullable()->index();
                $table->text('final_reason')->nullable();
                $table->text('collection_instructions')->nullable();
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('maintenance_reports')) {
            Schema::create('maintenance_reports', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('maintenance_case_id')->constrained()->cascadeOnDelete();
                $table->foreignId('maintenance_officer_id')->constrained('users')->restrictOnDelete();
                $table->unsignedSmallInteger('cycle_number');
                $table->string('technical_outcome', 30)->index();
                $table->text('findings');
                $table->text('work_performed')->nullable();
                $table->boolean('spare_needed')->default(false)->index();
                $table->text('spare_description')->nullable();
                $table->string('status', 40)->default('awaiting_review')->index();
                $table->timestamp('submitted_at');
                $table->timestamps();
                $table->unique(['maintenance_case_id', 'cycle_number']);
            });
        }

        if (!Schema::hasTable('maintenance_reviews')) {
            Schema::create('maintenance_reviews', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('maintenance_report_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('reviewed_by_user_id')->constrained('users')->restrictOnDelete();
                $table->string('decision', 20)->index();
                $table->text('comments');
                $table->timestamp('reviewed_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('spare_requisitions')) {
            Schema::create('spare_requisitions', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->string('requisition_number', 40)->unique();
                $table->foreignId('maintenance_report_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('status', 40)->default('pending_procurement')->index();
                $table->text('requested_items');
                $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('procurement_comments')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->text('issued_items')->nullable();
                $table->unsignedInteger('issued_quantity')->nullable();
                $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('issued_at')->nullable();
                $table->foreignId('relayed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('relayed_at')->nullable();
                $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('receipt_remarks')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('maintenance_return_confirmations')) {
            Schema::create('maintenance_return_confirmations', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('maintenance_case_id')->unique()->constrained()->restrictOnDelete();
                $table->foreignId('returned_by_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('confirmed_by_user_id')->constrained('users')->restrictOnDelete();
                $table->string('condition_received', 30);
                $table->boolean('correct_asset');
                $table->text('comment')->nullable();
                $table->timestamp('confirmed_at');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('maintenance_return_confirmations');
        Schema::dropIfExists('spare_requisitions');
        Schema::dropIfExists('maintenance_reviews');
        Schema::dropIfExists('maintenance_reports');
        Schema::dropIfExists('maintenance_cases');
    }
};
