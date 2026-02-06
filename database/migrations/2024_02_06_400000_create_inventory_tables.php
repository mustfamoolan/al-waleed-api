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
        // 1. Categories
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Units
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_base')->default(false); // e.g. Piece is base
            $table->timestamps();
        });

        // 3. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();

            // Prices
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('sale_price_retail', 15, 2)->default(0);
            $table->decimal('sale_price_wholesale', 15, 2)->default(0);

            // Units
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->boolean('has_pack')->default(false);
            $table->foreignId('pack_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->integer('units_per_pack')->default(1);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Product Suppliers (Many-to-Many with extra fields)
        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('last_purchase_price', 15, 2)->default(0);
            $table->string('currency')->default('IQD');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 5. Inventory Balances
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('qty_on_hand', 15, 2)->default(0);
            $table->decimal('avg_cost_iqd', 15, 2)->default(0); // Moving Average Cost

            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });

        // 6. Inventory Transactions (Header)
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('trans_date');
            $table->enum('trans_type', [
                'purchase',
                'sale',
                'sale_return',
                'purchase_return',
                'transfer_in',
                'transfer_out',
                'adjustment',
                'agent_request'
            ]);
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');

            // Polymorphic relation to Invoice, Adjustment, etc.
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });

        // 7. Inventory Transaction Lines (Details)
        Schema::create('inventory_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');

            $table->decimal('qty', 15, 2); // Can be negative for internal logic, but usually positive here + type determines sign
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_factor', 10, 4)->default(1); // Conversion to base unit

            $table->decimal('cost_iqd', 15, 2)->default(0); // Cost per unit at that moment
            $table->decimal('sale_price_iqd', 15, 2)->nullable(); // Only for sales

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transaction_lines');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('product_suppliers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units');
        Schema::dropIfExists('product_categories');
    }
};
