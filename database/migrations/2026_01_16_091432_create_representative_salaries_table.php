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
        Schema::create('representative_salaries', function (Blueprint $table) {
            $table->id('salary_id');
            $table->unsignedBigInteger('rep_id');
            $table->string('month', 7)->comment('Format: Y-m (2026-01)');
            $table->decimal('base_salary', 15, 2)->comment('الراتب الثابت');
            $table->decimal('total_bonuses', 15, 2)->default(0)->comment('إجمالي المكافآت');
            $table->decimal('total_amount', 15, 2)->comment('base_salary + total_bonuses');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable()->comment('manager_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('rep_id')->references('rep_id')->on('representatives')->onDelete('cascade');
            $table->foreign('paid_by')->references('manager_id')->on('managers')->onDelete('set null');
            $table->unique(['rep_id', 'month']);
            $table->index('month');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representative_salaries');
    }
};
