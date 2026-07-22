<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_assignments')) {
            Schema::table('asset_assignments', function (Blueprint $table) {
                try {
                    $table->foreignId('active_asset_id')->nullable()->unique()->constrained('assets')->nullOnDelete();
                } catch (\Exception $e) {
                    // Column already exists, continue
                }
                try {
                    $table->string('purpose', 500)->nullable();
                } catch (\Exception $e) {
                    // Column already exists, continue
                }
                try {
                    $table->json('accessories')->nullable();
                } catch (\Exception $e) {
                    // Column already exists, continue
                }
            });
        }

        if (!Schema::hasTable('handover_receipts')) {
            Schema::create('handover_receipts', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('asset_assignment_id')->unique()->constrained()->restrictOnDelete();
                $table->foreignId('handed_over_by_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('confirmed_by_user_id')->constrained('users')->restrictOnDelete();
                $table->string('decision', 30)->index();
                $table->boolean('matches_expected_asset')->nullable();
                $table->string('condition_received', 30);
                $table->text('remarks')->nullable();
                $table->timestamp('received_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_receipts');

        if (Schema::hasTable('asset_assignments')) {
            Schema::table('asset_assignments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('active_asset_id');
                $table->dropColumn(['purpose', 'accessories']);
            });
        }
    }
};
