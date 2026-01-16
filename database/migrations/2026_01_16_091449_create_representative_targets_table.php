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
        Schema::create('representative_targets', function (Blueprint $table) {
            $table->id('target_id');
            $table->unsignedBigInteger('rep_id');
            $table->enum('target_type', ['category', 'supplier', 'product', 'mixed'])->comment('نوع الهدف');
            $table->string('target_month', 7)->comment('Format: Y-m (2026-01)');
            $table->string('target_name')->comment('اسم الهدف للعرض');
            
            // For single target (category/supplier/product)
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            
            $table->decimal('target_quantity', 10, 2)->comment('عدد المنتجات المطلوب');
            $table->decimal('bonus_per_unit', 15, 2)->comment('المكافأة لكل قطعة');
            $table->decimal('completion_percentage_required', 5, 2)->default(100)->comment('نسبة الإنجاز المطلوبة %');
            
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            
            // Calculated fields
            $table->decimal('achieved_quantity', 10, 2)->default(0)->comment('محسوب تلقائياً');
            $table->decimal('achievement_percentage', 5, 2)->default(0)->comment('محسوب تلقائياً');
            $table->decimal('total_bonus_earned', 15, 2)->default(0)->comment('محسوب تلقائياً');
            
            $table->unsignedBigInteger('created_by')->comment('manager_id');
            $table->timestamps();

            $table->foreign('rep_id')->references('rep_id')->on('representatives')->onDelete('cascade');
            $table->foreign('category_id')->references('category_id')->on('categories')->onDelete('set null');
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('set null');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('set null');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
            $table->index(['rep_id', 'target_month']);
            $table->index('target_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representative_targets');
    }
};
