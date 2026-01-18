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
        Schema::create('customers', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('customer_name', 255);
            $table->string('phone_number', 20)->nullable()->unique();
            $table->text('address')->nullable();
            $table->decimal('total_debt', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->date('last_payment_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
            $table->index('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
