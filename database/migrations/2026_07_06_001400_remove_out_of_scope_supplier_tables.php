<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('supplier_number_sequences');
    }

    public function down(): void
    {
        // Intentionally irreversible: purchasing/supplier management is outside EIMS scope.
    }
};
