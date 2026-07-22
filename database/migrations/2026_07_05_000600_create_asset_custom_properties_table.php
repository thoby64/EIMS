<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_custom_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('key', 110);
            $table->string('value', 255);
            $table->timestamps();
            $table->unique(['asset_id', 'key']);
            $table->index(['key', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_custom_properties');
    }
};
