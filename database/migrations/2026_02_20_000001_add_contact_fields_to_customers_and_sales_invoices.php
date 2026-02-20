<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add city to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('address');
        });

        // Add optional customer contact snapshot fields to sales_invoices
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('customer_city', 100)->nullable()->after('notes');
            $table->string('customer_phone', 30)->nullable()->after('customer_city');
            $table->string('customer_address')->nullable()->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('city');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['customer_city', 'customer_phone', 'customer_address']);
        });
    }
};
