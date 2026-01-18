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
        Schema::create('customer_representatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('representative_id');
            $table->timestamp('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('representative_id')->references('rep_id')->on('representatives')->onDelete('cascade');
            $table->foreign('assigned_by')->references('manager_id')->on('managers')->onDelete('set null');
            $table->unique(['customer_id', 'representative_id']);
            $table->index('representative_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_representatives');
    }
};
