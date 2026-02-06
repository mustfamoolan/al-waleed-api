<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Cash Accounts
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['cash', 'bank'])->default('cash')->after('name');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete(); // Linked GL Account
            $table->string('currency')->default('IQD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Receipts
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->restrictOnDelete();

            // Payer
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->enum('receipt_type', ['customer_payment', 'general_income']);
            $table->decimal('amount_iqd', 15, 2);

            $table->enum('status', ['draft', 'posted', 'canceled'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Receipt Allocations (Linking to Sales Invoices)
        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->decimal('allocated_iqd', 15, 2);
            $table->timestamps();
        });

        // 4. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->restrictOnDelete();

            // Payee
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();

            $table->enum('payment_type', ['supplier_payment', 'expense', 'salary_payment', 'advance']);

            // For Expenses
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->decimal('amount_iqd', 15, 2);

            $table->enum('status', ['draft', 'posted', 'canceled'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Payment Allocations (Linking to Purchase Invoices)
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->restrictOnDelete();
            $table->decimal('allocated_iqd', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('cash_accounts');
    }
};
