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
        // 1. Purchase Invoices
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique(); // Internal Serial
            $table->string('supplier_invoice_no')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->date('invoice_date');

            // Currency
            $table->enum('currency', ['IQD', 'USD'])->default('IQD');
            $table->decimal('exchange_rate', 15, 6)->default(1);

            // Totals
            $table->decimal('subtotal_foreign', 15, 2)->default(0);
            $table->decimal('discount_foreign', 15, 2)->default(0);
            $table->decimal('total_foreign', 15, 2)->default(0);

            $table->decimal('total_iqd', 15, 2)->default(0); // Calculated and fixed
            $table->decimal('paid_iqd', 15, 2)->default(0);
            $table->decimal('remaining_iqd', 15, 2)->default(0);

            $table->enum('status', ['draft', 'approved', 'posted', 'canceled'])->default('draft');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();
        });

        // 2. Purchase Invoice Lines
        Schema::create('purchase_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');

            $table->decimal('qty', 15, 2);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_factor', 10, 4)->default(1);

            $table->decimal('price_foreign', 15, 2)->default(0);
            $table->decimal('line_total_foreign', 15, 2)->default(0);
            $table->decimal('line_total_iqd', 15, 2)->default(0);

            $table->boolean('is_free')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // 3. Purchase Returns
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->date('return_date');

            $table->enum('currency', ['IQD', 'USD'])->default('IQD');
            $table->decimal('exchange_rate', 15, 6)->default(1);

            $table->decimal('total_foreign', 15, 2)->default(0);
            $table->decimal('total_iqd', 15, 2)->default(0);

            $table->enum('status', ['draft', 'approved', 'posted', 'canceled'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();
        });

        // 4. Purchase Return Lines
        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');

            $table->decimal('qty', 15, 2);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_factor', 10, 4)->default(1);

            $table->decimal('price_foreign', 15, 2)->default(0);
            $table->decimal('line_total_iqd', 15, 2)->default(0); // Return value in IQD at moment of return (or original invoice rate)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
    }
};
