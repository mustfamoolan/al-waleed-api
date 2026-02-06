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
        // 1. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('currency')->default('IQD'); // IQD, USD
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 2. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('sales_type', ['cash', 'credit'])->default('cash');
            $table->decimal('credit_limit', 15, 2)->default(0.00);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->timestamps();
        });

        // 3. Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->decimal('salary', 15, 2)->default(0.00);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->timestamps();
        });

        // 4. Sales Agents
        Schema::create('sales_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->decimal('salary', 15, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 2)->default(0.00); // Percentage
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->timestamps();
        });

        // 5. Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('sales_agents');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
    }
};
