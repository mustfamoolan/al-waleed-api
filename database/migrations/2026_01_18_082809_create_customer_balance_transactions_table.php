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
        Schema::create('customer_balance_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('customer_id');
            $table->enum('transaction_type', ['invoice', 'payment', 'adjustment', 'refund']);
            $table->decimal('amount', 15, 2)->comment('إيجابي للدين، سالب للمدفوع');
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('related_type', 255)->nullable()->comment('sale_invoice, customer_payment');
            $table->unsignedBigInteger('related_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('set null');
            $table->index('customer_id');
            $table->index('transaction_type');
            $table->index(['related_type', 'related_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_balance_transactions');
    }
};
