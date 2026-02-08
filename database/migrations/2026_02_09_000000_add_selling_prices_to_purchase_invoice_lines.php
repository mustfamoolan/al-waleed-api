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
            $table->decimal('sale_price_retail', 15, 2)->default(0)->after('price_after_cost');
            $table->decimal('sale_price_wholesale', 15, 2)->default(0)->after('sale_price_retail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['sale_price_retail', 'sale_price_wholesale']);
        });
    }
};
