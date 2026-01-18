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
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id('return_item_id');
            $table->unsignedBigInteger('return_id')->comment('الإرجاع المرتبط');
            $table->unsignedBigInteger('sale_invoice_item_id')->comment('عنصر الفاتورة المسترجع');
            $table->unsignedBigInteger('product_id')->comment('المنتج');
            
            // Return details
            $table->decimal('quantity_returned', 10, 2)->comment('الكمية المسترجعة');
            $table->decimal('unit_price', 15, 2)->comment('السعر وقت الإرجاع');
            $table->decimal('total_return_price', 15, 2)->comment('إجمالي المبلغ المسترجع');
            
            // Reason
            $table->text('reason')->nullable()->comment('سبب إرجاع هذا العنصر');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('return_id')->references('return_id')->on('sale_returns')->onDelete('cascade');
            $table->foreign('sale_invoice_item_id')->references('item_id')->on('sale_invoice_items')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            
            // Indexes
            $table->index('return_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
