<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Add new columns only if they don't exist
            if (!Schema::hasColumn('purchase_invoices', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('supplier_id');
            }
            if (!Schema::hasColumn('purchase_invoices', 'payment_status')) {
                $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid')->after('due_date');
            }
            if (!Schema::hasColumn('purchase_invoices', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank', 'deferred'])->default('deferred')->after('payment_status');
            }

            // Drop columns that are no longer needed (only if they exist)
            $columnsToDrop = [];
            if (Schema::hasColumn('purchase_invoices', 'paid_amount')) $columnsToDrop[] = 'paid_amount';
            if (Schema::hasColumn('purchase_invoices', 'remaining_amount')) $columnsToDrop[] = 'remaining_amount';
            if (Schema::hasColumn('purchase_invoices', 'driver_cost')) $columnsToDrop[] = 'driver_cost';
            if (Schema::hasColumn('purchase_invoices', 'worker_cost')) $columnsToDrop[] = 'worker_cost';
            if (Schema::hasColumn('purchase_invoices', 'status')) $columnsToDrop[] = 'status';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Add foreign key for warehouse (only if warehouses table exists and warehouse_id column exists)
        if (Schema::hasTable('warehouses') && Schema::hasColumn('purchase_invoices', 'warehouse_id')) {
            try {
                Schema::table('purchase_invoices', function (Blueprint $table) {
                    $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist
            }

            // Get default warehouse ID (will be created in seeder)
            $defaultWarehouseId = DB::table('warehouses')->where('name', 'المستودع الرئيسي')->value('id') ?? 1;

            // Set default warehouse for existing records
            if (Schema::hasTable('purchase_invoices') && $defaultWarehouseId) {
                try {
                    DB::table('purchase_invoices')->whereNull('warehouse_id')->update(['warehouse_id' => $defaultWarehouseId]);
                } catch (\Exception $e) {
                    // Ignore if update fails
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'warehouse_id')) {
                try {
                    $table->dropForeign(['warehouse_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist
                }
            }
            $table->dropColumn(['warehouse_id', 'payment_status', 'payment_method']);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->decimal('driver_cost', 15, 2)->default(0);
            $table->decimal('worker_cost', 15, 2)->default(0);
            $table->enum('status', ['draft', 'pending', 'paid', 'partial', 'returned', 'cancelled'])->default('draft');
        });
    }
};
