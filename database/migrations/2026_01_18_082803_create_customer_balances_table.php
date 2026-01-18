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
        Schema::create('customer_balances', function (Blueprint $table) {
            $table->id('balance_id');
            $table->unsignedBigInteger('customer_id')->unique();
            $table->decimal('current_balance', 15, 2)->default(0)->comment('الرصيد الحالي (دين إيجابي)');
            $table->decimal('total_debt', 15, 2)->default(0)->comment('إجمالي الدين');
            $table->decimal('total_paid', 15, 2)->default(0)->comment('إجمالي المدفوع');
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_balances');
    }
};
