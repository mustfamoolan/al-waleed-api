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
        Schema::table('purchase_return_details', function (Blueprint $table) {
            // Add foreign key for batch_id
            if (Schema::hasColumn('purchase_return_details', 'batch_id') && Schema::hasTable('inventory_batches')) {
                $table->foreign('batch_id')->references('id')->on('inventory_batches')->onDelete('restrict');
            }

            // Add foreign key for product_id
            if (Schema::hasColumn('purchase_return_details', 'product_id') && Schema::hasTable('products')) {
                $table->foreign('product_id')->references('product_id')->on('products')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['product_id']);
        });
    }
};
