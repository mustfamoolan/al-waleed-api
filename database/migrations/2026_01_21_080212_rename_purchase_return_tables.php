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
        // Rename tables
        if (Schema::hasTable('purchase_return_invoices')) {
            Schema::rename('purchase_return_invoices', 'purchase_returns');
        }
        if (Schema::hasTable('purchase_return_items')) {
            Schema::rename('purchase_return_items', 'purchase_return_details');
        }

        // Rename columns in purchase_returns
        if (Schema::hasTable('purchase_returns')) {
            Schema::table('purchase_returns', function (Blueprint $table) {
                // Check if columns exist before renaming
                if (Schema::hasColumn('purchase_returns', 'return_invoice_id')) {
                    DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN return_invoice_id id BIGINT UNSIGNED AUTO_INCREMENT');
                }
                if (Schema::hasColumn('purchase_returns', 'original_invoice_id')) {
                    DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN original_invoice_id reference_invoice_id BIGINT UNSIGNED');
                }
                if (Schema::hasColumn('purchase_returns', 'return_invoice_number')) {
                    DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN return_invoice_number return_number VARCHAR(255)');
                }
            });
        }

        // Rename columns in purchase_return_details
        if (Schema::hasTable('purchase_return_details')) {
            // First, drop foreign key if it exists
            try {
                $result = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'purchase_return_details' 
                    AND COLUMN_NAME = 'return_invoice_id' 
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

            Schema::table('purchase_return_details', function (Blueprint $table) {
                // Rename columns
                if (Schema::hasColumn('purchase_return_details', 'return_item_id')) {
                    DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN return_item_id id BIGINT UNSIGNED AUTO_INCREMENT');
                }
                if (Schema::hasColumn('purchase_return_details', 'return_invoice_id')) {
                    DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN return_invoice_id purchase_return_id BIGINT UNSIGNED');
                }
            });

            // Add foreign key back with new column name
            if (Schema::hasColumn('purchase_return_details', 'purchase_return_id')) {
                Schema::table('purchase_return_details', function (Blueprint $table) {
                    try {
                        $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Foreign key might already exist
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert foreign key
        if (Schema::hasTable('purchase_return_details')) {
            Schema::table('purchase_return_details', function (Blueprint $table) {
                try {
                    $table->dropForeign(['purchase_return_id']);
                } catch (\Exception $e) {
                    // Ignore if doesn't exist
                }
            });
        }

        // Rename columns back
        if (Schema::hasTable('purchase_return_details')) {
            Schema::table('purchase_return_details', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_return_details', 'id')) {
                    DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN id return_item_id BIGINT UNSIGNED AUTO_INCREMENT');
                }
                if (Schema::hasColumn('purchase_return_details', 'purchase_return_id')) {
                    DB::statement('ALTER TABLE purchase_return_details CHANGE COLUMN purchase_return_id return_invoice_id BIGINT UNSIGNED');
                }
            });

            // Add foreign key back with old name
            if (Schema::hasColumn('purchase_return_details', 'return_invoice_id')) {
                Schema::table('purchase_return_details', function (Blueprint $table) {
                    try {
                        $table->foreign('return_invoice_id')->references('return_invoice_id')->on('purchase_return_invoices')->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Ignore
                    }
                });
            }
        }

        if (Schema::hasTable('purchase_returns')) {
            Schema::table('purchase_returns', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_returns', 'id')) {
                    DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN id return_invoice_id BIGINT UNSIGNED AUTO_INCREMENT');
                }
                if (Schema::hasColumn('purchase_returns', 'reference_invoice_id')) {
                    DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN reference_invoice_id original_invoice_id BIGINT UNSIGNED');
                }
                if (Schema::hasColumn('purchase_returns', 'return_number')) {
                    DB::statement('ALTER TABLE purchase_returns CHANGE COLUMN return_number return_invoice_number VARCHAR(255)');
                }
            });
        }

        // Rename tables back
        if (Schema::hasTable('purchase_returns')) {
            Schema::rename('purchase_returns', 'purchase_return_invoices');
        }
        if (Schema::hasTable('purchase_return_details')) {
            Schema::rename('purchase_return_details', 'purchase_return_items');
        }
    }
};
