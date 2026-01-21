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
        Schema::table('inventory_batches', function (Blueprint $table) {
            // Add foreign key for purchase_invoice_detail_id
            if (Schema::hasColumn('inventory_batches', 'purchase_invoice_detail_id') && Schema::hasTable('purchase_invoice_details')) {
                $table->foreign('purchase_invoice_detail_id')->references('id')->on('purchase_invoice_details')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropForeign(['purchase_invoice_detail_id']);
        });
    }
};
