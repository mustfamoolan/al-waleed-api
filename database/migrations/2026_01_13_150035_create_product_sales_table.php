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
        Schema::create('product_sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sale_invoice_id')->nullable()->comment('For future sales invoices');
            $table->date('sale_date');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2)->comment('Sale price');
            $table->decimal('total_price', 15, 2);
            $table->decimal('purchase_price_at_sale', 15, 2)->comment('Purchase price at time of sale');
            $table->decimal('profit_amount', 15, 2)->comment('Profit or loss');
            $table->decimal('profit_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
            $table->index('product_id');
            $table->index('sale_date');
            $table->index('sale_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
