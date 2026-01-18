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
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('الزبون - null للدفعات غير المرتبطة');
            $table->unsignedBigInteger('invoice_id')->nullable()->comment('الفاتورة المرتبطة');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'other'])->default('cash');
            $table->string('reference_number', 255)->nullable()->comment('رقم المرجع مثل رقم الشيك');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('set null');
            $table->foreign('invoice_id')->references('invoice_id')->on('sale_invoices')->onDelete('set null');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
            $table->index('customer_id');
            $table->index('invoice_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
