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
        // 1. Fix purchase_invoices table
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('invoice_date')->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_invoices', 'driver_fee')) {
                $table->decimal('driver_fee', 15, 2)->default(0)->after('exchange_rate');
            }
            if (!Schema::hasColumn('purchase_invoices', 'worker_fee')) {
                $table->decimal('worker_fee', 15, 2)->default(0)->after('driver_fee');
            }
            if (!Schema::hasColumn('purchase_invoices', 'total_fees')) {
                $table->decimal('total_fees', 15, 2)->default(0)->after('worker_fee');
            }
        });

        // 2. Fix purchase_invoice_lines table
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_lines', 'cost_per_unit')) {
                $table->decimal('cost_per_unit', 15, 2)->default(0)->after('line_total_iqd');
            }
            if (!Schema::hasColumn('purchase_invoice_lines', 'price_after_cost')) {
                $table->decimal('price_after_cost', 15, 2)->default(0)->after('cost_per_unit');
            }
            if (!Schema::hasColumn('purchase_invoice_lines', 'sale_price_retail')) {
                $table->decimal('sale_price_retail', 15, 2)->default(0)->after('price_after_cost');
            }
            if (!Schema::hasColumn('purchase_invoice_lines', 'sale_price_wholesale')) {
                $table->decimal('sale_price_wholesale', 15, 2)->default(0)->after('sale_price_retail');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['warehouse_id', 'driver_fee', 'worker_fee', 'total_fees']);
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['cost_per_unit', 'price_after_cost', 'sale_price_retail', 'sale_price_wholesale']);
        });
    }
};
