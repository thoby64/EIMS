<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('custodian_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->string('asset_tag', 80)->unique();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('manufacturer', 120)->nullable();
            $table->string('brand', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('serial_number', 180)->nullable()->index();
            $table->string('condition', 30)->default('good')->index();
            $table->string('lifecycle_status', 40)->default('in_stock')->index();
            $table->string('ownership_type', 30)->default('purchased')->index();
            $table->date('acquired_on')->nullable();
            $table->decimal('acquisition_cost', 18, 2)->nullable();
            $table->char('currency', 3)->default('TZS');
            $table->date('warranty_expires_on')->nullable()->index();
            $table->date('commissioned_on')->nullable();
            $table->date('retired_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['asset_category_id', 'lifecycle_status']);
            $table->index(['organizational_unit_id', 'location_id']);
        });

        Schema::create('asset_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('value', 255);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['type', 'value']);
            $table->index(['asset_id', 'type']);
        });

        Schema::create('asset_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained()->restrictOnDelete();
            $table->text('text_value')->nullable();
            $table->decimal('number_value', 20, 4)->nullable();
            $table->dateTime('date_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->json('json_value')->nullable();
            $table->timestamps();
            $table->unique(['asset_id', 'attribute_definition_id'], 'asset_attribute_unique');
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('assigned_at');
            $table->dateTime('expected_return_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->string('condition_at_issue', 30);
            $table->string('condition_at_return', 30)->nullable();
            $table->text('issue_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->string('status', 30)->default('pending_receipt')->index();
            $table->timestamps();
            $table->index(['asset_id', 'status']);
        });

        Schema::create('asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('summary', 255);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_events');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('asset_attribute_values');
        Schema::dropIfExists('asset_identifiers');
        Schema::dropIfExists('assets');
    }
};
