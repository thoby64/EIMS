<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('organizational_units')) {
            Schema::create('organizational_units', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('parent_id')->nullable()->constrained('organizational_units')->nullOnDelete();
                $table->string('name', 150);
                $table->string('code', 30)->unique();
                $table->string('type', 40)->index();
                $table->string('email')->nullable();
                $table->string('phone', 30)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->ulid('public_id')->unique();
                $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->foreignId('organizational_unit_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 150);
                $table->string('code', 40)->unique();
                $table->string('type', 40)->index();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Try adding columns without position specifiers for compatibility
                try {
                    $table->foreignId('organizational_unit_id')->nullable()->constrained()->nullOnDelete();
                } catch (\Exception $e) {
                    // Column already exists, continue
                }
                
                try {
                    $table->foreignId('primary_location_id')->nullable()->constrained('locations')->nullOnDelete();
                } catch (\Exception $e) {
                    // Column already exists, continue
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_location_id');
            $table->dropConstrainedForeignId('organizational_unit_id');
        });
        }
        Schema::dropIfExists('locations');
        Schema::dropIfExists('organizational_units');
    }
};
