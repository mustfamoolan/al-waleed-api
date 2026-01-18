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
        Schema::table('product_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('product_sales', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('representative_id');
                $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('set null');
                $table->index('customer_id');
            }
        });

        // Add foreign key for sale_invoice_id if column exists but foreign key doesn't
        if (Schema::hasColumn('product_sales', 'sale_invoice_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                // Try to add foreign key - will fail silently if it already exists
                try {
                    $table->foreign('sale_invoice_id')->references('invoice_id')->on('sale_invoices')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist, ignore
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            if (Schema::hasColumn('product_sales', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropIndex(['customer_id']);
                $table->dropColumn('customer_id');
            }
        });
    }
};
