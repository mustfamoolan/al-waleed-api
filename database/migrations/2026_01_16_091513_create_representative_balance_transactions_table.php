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
        if (Schema::hasTable('representative_balance_transactions')) {
            return;
        }

        Schema::create('representative_balance_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('rep_id');
            $table->enum('transaction_type', ['salary_payment', 'bonus', 'withdrawal', 'payment', 'adjustment'])->comment('نوع المعاملة');
            $table->decimal('amount', 15, 2)->comment('positive للزيادة، negative للنقص');
            $table->string('related_type')->nullable()->comment('representative_salary/representative_target/null');
            $table->unsignedBigInteger('related_id')->nullable()->comment('salary_id/target_id/null');
            $table->text('description')->nullable();
            $table->decimal('balance_before', 15, 2)->comment('الرصيد قبل المعاملة');
            $table->decimal('balance_after', 15, 2)->comment('الرصيد بعد المعاملة');
            $table->unsignedBigInteger('created_by')->nullable()->comment('manager_id');
            $table->timestamps();

            $table->foreign('rep_id')->references('rep_id')->on('representatives')->onDelete('cascade');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('set null');
            $table->index(['rep_id', 'transaction_type'], 'rep_balance_trans_rep_type_idx');
            $table->index(['related_type', 'related_id'], 'rep_balance_trans_related_idx');
            $table->index('created_at', 'rep_balance_trans_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representative_balance_transactions');
    }
};
