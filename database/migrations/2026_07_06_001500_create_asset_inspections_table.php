<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inspections', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->string('inspection_number', 40)->unique();
            $t->foreignId('asset_id')->constrained()->restrictOnDelete();
            $t->foreignId('inspector_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('scheduled_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('scheduled_at');
            $t->timestamp('performed_at')->nullable();
            $t->string('status', 30)->default('scheduled')->index();
            $t->string('physical_status', 30)->nullable();
            $t->boolean('location_verified')->nullable();
            $t->boolean('custody_verified')->nullable();
            $t->string('condition_assessed', 30)->nullable();
            $t->text('findings')->nullable();
            $t->string('recommendation', 50)->nullable();
            $t->text('recommendation_notes')->nullable();
            $t->string('followup_status', 30)->default('not_required');
            $t->timestamps();
            $t->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inspections');
    }
};
