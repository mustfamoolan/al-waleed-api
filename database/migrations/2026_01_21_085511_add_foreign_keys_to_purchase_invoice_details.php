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
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            // Add foreign key for unit_id
            if (Schema::hasColumn('purchase_invoice_details', 'unit_id') && Schema::hasTable('product_units')) {
                $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('set null');
            }

            // Add foreign key for product_id
            if (Schema::hasColumn('purchase_invoice_details', 'product_id') && Schema::hasTable('products')) {
                $table->foreign('product_id')->references('product_id')->on('products')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['product_id']);
        });
    }
};
