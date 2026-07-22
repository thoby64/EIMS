<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->foreignId('active_asset_id')->nullable()->after('asset_id')->unique()->constrained('assets')->nullOnDelete();
            $table->string('purpose', 500)->nullable()->after('condition_at_return');
            $table->json('accessories')->nullable()->after('purpose');
        });

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

    public function down(): void
    {
        Schema::dropIfExists('handover_receipts');
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_asset_id');
            $table->dropColumn(['purpose', 'accessories']);
        });
    }
};
