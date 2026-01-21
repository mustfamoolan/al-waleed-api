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
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('batch_number');
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->index();
            $table->decimal('cost_price', 15, 2)->comment('Cost per base unit');
            $table->decimal('quantity_initial', 15, 3)->comment('Original qty received');
            $table->decimal('quantity_current', 15, 3)->comment('Remaining qty');
            $table->unsignedBigInteger('purchase_invoice_detail_id')->nullable();
            $table->enum('status', ['active', 'expired', 'consumed'])->default('active');
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('status');
            
            // Foreign key for purchase_invoice_detail_id will be added later after table is created
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
