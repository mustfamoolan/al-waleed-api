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
        Schema::table('products', function (Blueprint $table) {
            $table->enum('weight_unit', ['kg', 'gram', 'liter', 'ml', 'piece'])->nullable()->after('piece_weight')->comment('Unit of measurement for weight/volume');
            $table->date('last_sale_date')->nullable()->after('last_purchase_date')->comment('Date of last sale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight_unit', 'last_sale_date']);
        });
    }
};
