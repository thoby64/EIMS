<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_category_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('asset_category_id');
            $table->string('responsibility', 30)->index();
            $table->foreignId('assigned_by_user_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'asset_category_id', 'responsibility'], 'maintenance_responsibility_unique');
            $table->foreign('user_id', 'mcr_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('asset_category_id', 'mcr_category_fk')->references('id')->on('asset_categories')->cascadeOnDelete();
            $table->foreign('assigned_by_user_id', 'mcr_assigned_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_category_responsibilities');
    }
};
