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
        Schema::create('representative_balances', function (Blueprint $table) {
            $table->id('balance_id');
            $table->unsignedBigInteger('rep_id')->unique();
            $table->decimal('current_balance', 15, 2)->default(0)->comment('الرصيد الحالي');
            $table->decimal('total_earned', 15, 2)->default(0)->comment('إجمالي المكتسب');
            $table->decimal('total_withdrawn', 15, 2)->default(0)->comment('إجمالي المسحوب');
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();

            $table->foreign('rep_id')->references('rep_id')->on('representatives')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representative_balances');
    }
};
