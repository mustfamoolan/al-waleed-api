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
        Schema::create('driver_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('sale_invoice_id')->nullable()->comment('الفاتورة المرتبطة');
            $table->unsignedBigInteger('customer_id')->comment('الزبون');
            $table->unsignedBigInteger('driver_id')->comment('السائق الذي استلم الدفعة');
            
            // Payment details
            $table->date('payment_date')->comment('تاريخ الدفعة');
            $table->decimal('amount', 15, 2)->comment('المبلغ');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'other'])->default('cash')->comment('طريقة الدفع');
            $table->string('reference_number', 255)->nullable()->comment('رقم المرجع (رقم الشيك/الحوالة)');
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('حالة الدفعة - تحتاج موافقة المدير');
            
            // Approval
            $table->unsignedBigInteger('approved_by')->nullable()->comment('المدير الذي وافق');
            $table->timestamp('approved_at')->nullable()->comment('تاريخ الموافقة');
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('sale_invoice_id')->references('invoice_id')->on('sale_invoices')->onDelete('set null');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('driver_id')->references('picker_id')->on('pickers')->onDelete('cascade');
            $table->foreign('approved_by')->references('manager_id')->on('managers')->onDelete('set null');
            
            // Indexes
            $table->index('sale_invoice_id');
            $table->index('customer_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_payments');
    }
};
