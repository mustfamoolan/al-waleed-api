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
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id('return_item_id');
            $table->unsignedBigInteger('return_invoice_id');
            $table->unsignedBigInteger('original_item_id')->nullable();
            $table->string('product_name');
            $table->string('product_code')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('return_invoice_id')->references('return_invoice_id')->on('purchase_return_invoices')->onDelete('cascade');
            $table->foreign('original_item_id')->references('item_id')->on('purchase_invoice_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
