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
        // First, drop all foreign key constraints that reference the tables we want to drop
        
        // Drop foreign keys from purchase_return_details that reference inventory_movements
        if (Schema::hasTable('purchase_return_details')) {
            try {
                $result = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'purchase_return_details' 
                    AND COLUMN_NAME = 'inventory_movement_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    LIMIT 1
                ");
                
                if (!empty($result) && isset($result[0]->CONSTRAINT_NAME)) {
                    $fkName = $result[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE purchase_return_details DROP FOREIGN KEY `{$fkName}`");
                }
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }
        }

        // Drop foreign keys from purchase_invoice_details that reference inventory_movements
        if (Schema::hasTable('purchase_invoice_details')) {
            try {
                $result = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'purchase_invoice_details' 
                    AND COLUMN_NAME = 'inventory_movement_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    LIMIT 1
                ");
                
                if (!empty($result) && isset($result[0]->CONSTRAINT_NAME)) {
                    $fkName = $result[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE purchase_invoice_details DROP FOREIGN KEY `{$fkName}`");
                }
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }
        }

        // Drop foreign keys from products that reference inventory_movements (if any)
        if (Schema::hasTable('products')) {
            try {
                $result = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'products' 
                    AND REFERENCED_TABLE_NAME = 'inventory_movements'
                    LIMIT 1
                ");
                
                if (!empty($result) && isset($result[0]->CONSTRAINT_NAME)) {
                    $fkName = $result[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE products DROP FOREIGN KEY `{$fkName}`");
                }
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }
        }

        // Now drop the columns that reference inventory_movements
        if (Schema::hasTable('purchase_return_details') && Schema::hasColumn('purchase_return_details', 'inventory_movement_id')) {
            Schema::table('purchase_return_details', function (Blueprint $table) {
                $table->dropColumn('inventory_movement_id');
            });
        }

        if (Schema::hasTable('purchase_invoice_details') && Schema::hasColumn('purchase_invoice_details', 'inventory_movement_id')) {
            Schema::table('purchase_invoice_details', function (Blueprint $table) {
                $table->dropColumn('inventory_movement_id');
            });
        }

        // Now we can safely drop the old tables
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('inventory_movements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot fully reverse this migration as we don't have the exact structure
        // This is a one-way migration
    }
};
