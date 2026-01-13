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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('payment_number')->unique();
            $table->enum('payment_type', ['payment', 'refund']);
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'other']);
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('restrict');
            $table->foreign('invoice_id')->references('invoice_id')->on('purchase_invoices')->onDelete('set null');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
