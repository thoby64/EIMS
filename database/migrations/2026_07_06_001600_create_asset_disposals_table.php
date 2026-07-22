<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asset_disposals')) {
            Schema::create('asset_disposals', function (Blueprint $t) {
                $t->id();
                $t->ulid('public_id')->unique();
                $t->string('disposal_number', 40)->unique();
                $t->foreignId('asset_id')->constrained()->restrictOnDelete();
                $t->foreignId('proposed_by_user_id')->constrained('users')->restrictOnDelete();
                $t->string('reason', 40);
                $t->text('justification');
                $t->string('status', 40)->default('pending_review')->index();
                $t->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('review_decision', 20)->nullable();
                $t->text('review_comments')->nullable();
                $t->timestamp('reviewed_at')->nullable();
                $t->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('approval_decision', 20)->nullable();
                $t->text('approval_comments')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->foreignId('surrendered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->text('surrender_comments')->nullable();
                $t->timestamp('surrendered_at')->nullable();
                $t->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('disposal_method', 30)->nullable();
                $t->date('disposed_on')->nullable();
                $t->string('witness_name', 180)->nullable();
                $t->text('finalization_comments')->nullable();
                $t->timestamp('finalized_at')->nullable();
                $t->timestamps();
                $t->index(['asset_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
