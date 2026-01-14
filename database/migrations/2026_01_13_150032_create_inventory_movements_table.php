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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id('movement_id');
            $table->unsignedBigInteger('product_id');
            $table->enum('movement_type', ['purchase', 'return', 'sale', 'adjustment', 'transfer']);
            $table->string('reference_type')->nullable()->comment('purchase_invoice, purchase_return, sale_invoice, etc.');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 10, 2)->comment('Positive for in, negative for out');
            $table->decimal('stock_before', 10, 2);
            $table->decimal('stock_after', 10, 2);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
            $table->index('product_id');
            $table->index('movement_type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
