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
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('driver_cost', 15, 2)->nullable()->after('total_amount')->comment('Driver rental cost');
            $table->decimal('worker_cost', 15, 2)->nullable()->after('driver_cost')->comment('Worker rental cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['driver_cost', 'worker_cost']);
        });
    }
};
