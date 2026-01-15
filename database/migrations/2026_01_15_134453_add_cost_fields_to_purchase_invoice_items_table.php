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
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->decimal('cost_after_purchase', 15, 2)->nullable()->after('total_price')->comment('Cost after adding transport share');
            $table->decimal('transport_cost_share', 15, 2)->nullable()->after('cost_after_purchase')->comment('Transport cost share for this item');
            $table->decimal('retail_price', 15, 2)->nullable()->after('transport_cost_share')->comment('Retail price at purchase time');
            $table->decimal('wholesale_price', 15, 2)->nullable()->after('retail_price')->comment('Wholesale price at purchase time');
            $table->string('category_name')->nullable()->after('wholesale_price')->comment('Category name at purchase time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['cost_after_purchase', 'transport_cost_share', 'retail_price', 'wholesale_price', 'category_name']);
        });
    }
};
