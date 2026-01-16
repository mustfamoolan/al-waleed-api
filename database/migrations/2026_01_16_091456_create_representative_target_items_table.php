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
        Schema::create('representative_target_items', function (Blueprint $table) {
            $table->id('target_item_id');
            $table->unsignedBigInteger('target_id');
            $table->enum('item_type', ['product', 'category', 'supplier'])->comment('نوع العنصر');
            $table->unsignedBigInteger('item_id')->comment('product_id/category_id/supplier_id');
            $table->decimal('target_quantity', 10, 2)->comment('الكمية المطلوبة لهذا العنصر');
            $table->decimal('bonus_per_unit', 15, 2)->comment('المكافأة لكل قطعة لهذا العنصر');
            $table->decimal('achieved_quantity', 10, 2)->default(0)->comment('محسوب تلقائياً');
            $table->timestamps();

            $table->foreign('target_id')->references('target_id')->on('representative_targets')->onDelete('cascade');
            $table->index(['target_id', 'item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representative_target_items');
    }
};
