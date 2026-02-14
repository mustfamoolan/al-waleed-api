<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('total_debt', 15, 2)->default(0.00)->after('credit_limit');
            $table->decimal('total_paid', 15, 2)->default(0.00)->after('total_debt');
            $table->date('last_payment_date')->nullable()->after('total_paid');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('total_debt', 15, 2)->default(0.00)->after('opening_balance'); // Actually total_purchased or total_due? Let's use total_debt for consistency or total_due. 
            // Suppliers: Debt IS what we owe them. So total_debt is fine (Accounts Payable).
            $table->decimal('total_paid', 15, 2)->default(0.00)->after('total_debt');
            $table->date('last_payment_date')->nullable()->after('total_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['total_debt', 'total_paid', 'last_payment_date']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['total_debt', 'total_paid', 'last_payment_date']);
        });
    }
};
