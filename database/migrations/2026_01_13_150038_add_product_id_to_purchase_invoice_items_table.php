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
            $table->unsignedBigInteger('product_id')->nullable()->after('invoice_id');
            $table->unsignedBigInteger('inventory_movement_id')->nullable()->after('product_id');

            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('set null');
            $table->foreign('inventory_movement_id')->references('movement_id')->on('inventory_movements')->onDelete('set null');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['inventory_movement_id']);
            $table->dropIndex(['product_id']);
            $table->dropColumn(['product_id', 'inventory_movement_id']);
        });
    }
};
