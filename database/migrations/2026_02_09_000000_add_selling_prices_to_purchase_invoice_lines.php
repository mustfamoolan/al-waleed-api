<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 15, 2)->default(0);
            $table->decimal('price_after_cost', 15, 2)->default(0);
            $table->decimal('sale_price_retail', 15, 2)->default(0);
            $table->decimal('sale_price_wholesale', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['cost_per_unit', 'price_after_cost', 'sale_price_retail', 'sale_price_wholesale']);
        });
    }
};
