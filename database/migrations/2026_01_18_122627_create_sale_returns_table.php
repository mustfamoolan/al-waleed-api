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
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id('return_id');
            $table->unsignedBigInteger('sale_invoice_id')->comment('الفاتورة المرتبطة');
            $table->unsignedBigInteger('customer_id')->comment('الزبون');
            $table->unsignedBigInteger('representative_id')->nullable()->comment('المندوب الذي أنشأ الفاتورة');
            
            // Returned by fields (polymorphic)
            $table->unsignedBigInteger('created_by')->nullable()->comment('من أنشأ الإرجاع');
            $table->enum('created_by_type', ['driver', 'representative', 'manager'])->default('driver')->comment('نوع منشئ الإرجاع');
            $table->unsignedBigInteger('returned_by')->nullable()->comment('السائق الذي أنشأ الإرجاع (إذا كان created_by_type = driver)');
            
            // Return details
            $table->enum('return_type', ['full', 'partial'])->comment('إرجاع كامل أم جزئي');
            $table->date('return_date')->comment('تاريخ الإرجاع');
            $table->text('return_reason')->nullable()->comment('سبب الإرجاع');
            $table->decimal('total_return_amount', 15, 2)->default(0)->comment('المبلغ المسترجع');
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending')->comment('حالة الإرجاع');
            
            // Approval
            $table->unsignedBigInteger('approved_by')->nullable()->comment('المدير الذي وافق');
            $table->timestamp('approved_at')->nullable()->comment('تاريخ الموافقة');
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('sale_invoice_id')->references('invoice_id')->on('sale_invoices')->onDelete('cascade');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('representative_id')->references('rep_id')->on('representatives')->onDelete('set null');
            $table->foreign('returned_by')->references('picker_id')->on('pickers')->onDelete('set null');
            $table->foreign('approved_by')->references('manager_id')->on('managers')->onDelete('set null');
            
            // Indexes
            $table->index('sale_invoice_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('created_by_type');
            $table->index('return_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
