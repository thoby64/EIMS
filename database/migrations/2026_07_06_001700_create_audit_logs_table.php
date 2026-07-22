<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('actor_identity', 255)->nullable()->index();
            $t->string('event_type', 30)->index();
            $t->string('action', 100)->index();
            $t->string('module', 80)->nullable()->index();
            $t->string('auditable_type', 180)->nullable();
            $t->unsignedBigInteger('auditable_id')->nullable();
            $t->string('auditable_public_id', 80)->nullable();
            $t->string('route_name', 180)->nullable()->index();
            $t->string('http_method', 10)->nullable();
            $t->string('path', 500)->nullable();
            $t->string('ip_address', 45)->nullable()->index();
            $t->string('user_agent', 500)->nullable();
            $t->string('outcome', 20)->default('success')->index();
            $t->unsignedSmallInteger('http_status')->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->json('context')->nullable();
            $t->timestamp('occurred_at')->index();
            $t->timestamps();
            $t->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
