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
        Schema::create('sale_invoice_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name', 255)->comment('اسم المنتج وقت البيع');
            $table->string('product_code', 255)->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2)->comment('سعر البيع');
            $table->decimal('purchase_price_at_sale', 15, 2)->comment('سعر الشراء وقت البيع');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('total_price', 15, 2);
            $table->decimal('profit_amount', 15, 2);
            $table->decimal('profit_percentage', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('invoice_id')->on('sale_invoices')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->index('invoice_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_invoice_items');
    }
};
