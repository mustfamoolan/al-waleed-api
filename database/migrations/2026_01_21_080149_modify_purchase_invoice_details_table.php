<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists before renaming
        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::rename('purchase_invoice_items', 'purchase_invoice_details');
        }

        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            // Add product_id column if it doesn't exist
            if (!Schema::hasColumn('purchase_invoice_details', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('invoice_id');
            }

            // Add new columns
            $table->unsignedBigInteger('unit_id')->nullable()->after('product_id');
            $table->date('expiry_date')->nullable()->after('quantity');
            $table->string('batch_number')->nullable()->after('expiry_date');
            $table->decimal('total_row', 15, 2)->nullable()->after('unit_price');

            // Rename item_id to id if it exists
            if (Schema::hasColumn('purchase_invoice_details', 'item_id')) {
                DB::statement('ALTER TABLE purchase_invoice_details CHANGE COLUMN item_id id BIGINT UNSIGNED AUTO_INCREMENT');
            }
        });

        // Add foreign keys separately (will be added after product_units table is created)
        // Note: Foreign keys should be added in a later migration if needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'expiry_date', 'batch_number', 'total_row']);
            DB::statement('ALTER TABLE purchase_invoice_details CHANGE COLUMN id item_id BIGINT UNSIGNED AUTO_INCREMENT');
        });

        Schema::rename('purchase_invoice_details', 'purchase_invoice_items');
    }
};
