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
        Schema::create('purchase_return_invoices', function (Blueprint $table) {
            $table->id('return_invoice_id');
            $table->unsignedBigInteger('original_invoice_id')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->string('return_invoice_number')->unique();
            $table->date('return_date');
            $table->decimal('total_amount', 15, 2);
            $table->text('reason')->nullable();
            $table->enum('status', ['draft', 'pending', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('original_invoice_id')->references('invoice_id')->on('purchase_invoices')->onDelete('set null');
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('restrict');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_invoices');
    }
};
