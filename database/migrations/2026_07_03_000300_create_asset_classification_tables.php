<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_groups', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 150)->unique();
            $table->string('code', 12)->unique();
            $table->string('icon', 80)->nullable();
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('asset_group_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 20)->unique();
            $table->string('tracking_mode', 30)->default('individual')->index();
            $table->string('icon', 80)->nullable();
            $table->text('description')->nullable();
            $table->boolean('requires_asset_tag')->default(true);
            $table->boolean('is_maintainable')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['asset_group_id', 'name']);
        });

        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('data_type', 30)->index();
            $table->string('unit', 40)->nullable();
            $table->json('options')->nullable();
            $table->string('validation_rules', 500)->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('asset_category_attribute', function (Blueprint $table) {
            $table->foreignId('asset_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('overrides')->nullable();
            $table->primary(['asset_category_id', 'attribute_definition_id'], 'category_attribute_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_category_attribute');
        Schema::dropIfExists('attribute_definitions');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('asset_groups');
    }
};
