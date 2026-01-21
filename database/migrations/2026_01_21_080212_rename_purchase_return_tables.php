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
        // Rename tables
        Schema::rename('purchase_return_invoices', 'purchase_returns');
        Schema::rename('purchase_return_items', 'purchase_return_details');

        // Rename columns in purchase_returns
        Schema::table('purchase_returns', function (Blueprint $table) {
            DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN return_invoice_id id BIGINT UNSIGNED AUTO_INCREMENT');
            DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN original_invoice_id reference_invoice_id BIGINT UNSIGNED');
            DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN return_invoice_number return_number VARCHAR(255)');
            DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN return_date return_date DATE');
        });

        // Rename columns in purchase_return_details
        Schema::table('purchase_return_details', function (Blueprint $table) {
            DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN return_item_id id BIGINT UNSIGNED AUTO_INCREMENT');
            DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN return_invoice_id purchase_return_id BIGINT UNSIGNED');
        });

        // Update foreign key references
        Schema::table('purchase_return_details', function (Blueprint $table) {
            // Try to drop old foreign key if it exists
            try {
                $table->dropForeign(['return_invoice_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert foreign key
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->foreign('return_invoice_id')->references('return_invoice_id')->on('purchase_return_invoices')->onDelete('cascade');
        });

        // Rename columns back
        Schema::table('purchase_return_details', function (Blueprint $table) {
            DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN id return_item_id BIGINT UNSIGNED AUTO_INCREMENT');
            DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN purchase_return_id return_invoice_id BIGINT UNSIGNED');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN id return_invoice_id BIGINT UNSIGNED AUTO_INCREMENT');
            DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN reference_invoice_id original_invoice_id BIGINT UNSIGNED');
            DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN return_number return_invoice_number VARCHAR(255)');
        });

        // Rename tables back
        Schema::rename('purchase_returns', 'purchase_return_invoices');
        Schema::rename('purchase_return_details', 'purchase_return_items');
    }
};
