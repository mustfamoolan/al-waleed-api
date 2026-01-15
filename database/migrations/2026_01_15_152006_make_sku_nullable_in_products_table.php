<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL/MariaDB - make sku nullable
        DB::statement('ALTER TABLE products MODIFY sku VARCHAR(255) NULL');
        
        // Re-add unique constraint (allows NULL values)
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove unique constraint first
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
        });
        
        // Make sku NOT NULL again
        DB::statement('ALTER TABLE products MODIFY sku VARCHAR(255) NOT NULL');
        
        // Re-add unique constraint
        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku');
        });
    }
};
