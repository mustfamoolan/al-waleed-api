<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old tables that are replaced by new system
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('inventory_movements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot recreate these tables without their original structure
        // This is a one-way migration as per "fresh start" requirement
    }
};
