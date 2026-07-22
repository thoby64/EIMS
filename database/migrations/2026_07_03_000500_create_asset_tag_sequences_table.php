<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_tag_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['asset_category_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_tag_sequences');
    }
};
