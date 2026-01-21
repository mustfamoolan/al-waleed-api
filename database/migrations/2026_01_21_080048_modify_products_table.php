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
        // First, drop foreign key constraint if it exists
        Schema::table('products', function (Blueprint $table) {
            // Check if foreign key exists and drop it
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'products' 
                AND COLUMN_NAME = 'supplier_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (!empty($foreignKeys)) {
                foreach ($foreignKeys as $fk) {
                    try {
                        $table->dropForeign([$fk->CONSTRAINT_NAME]);
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            // Add new columns only if they don't exist
            if (!Schema::hasColumn('products', 'name_en')) {
                $table->string('name_en')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('products', 'min_stock_alert')) {
                $table->integer('min_stock_alert')->default(0)->after('product_image');
            }

            // Drop columns that are no longer needed (only if they exist)
            $columnsToDrop = [];
            if (Schema::hasColumn('products', 'current_stock')) $columnsToDrop[] = 'current_stock';
            if (Schema::hasColumn('products', 'purchase_price')) $columnsToDrop[] = 'purchase_price';
            if (Schema::hasColumn('products', 'wholesale_price')) $columnsToDrop[] = 'wholesale_price';
            if (Schema::hasColumn('products', 'retail_price')) $columnsToDrop[] = 'retail_price';
            if (Schema::hasColumn('products', 'supplier_id')) $columnsToDrop[] = 'supplier_id';
            if (Schema::hasColumn('products', 'unit_type')) $columnsToDrop[] = 'unit_type';
            if (Schema::hasColumn('products', 'pieces_per_carton')) $columnsToDrop[] = 'pieces_per_carton';
            if (Schema::hasColumn('products', 'piece_weight')) $columnsToDrop[] = 'piece_weight';
            if (Schema::hasColumn('products', 'carton_weight')) $columnsToDrop[] = 'carton_weight';
            if (Schema::hasColumn('products', 'last_purchase_date')) $columnsToDrop[] = 'last_purchase_date';
            if (Schema::hasColumn('products', 'last_sale_date')) $columnsToDrop[] = 'last_sale_date';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Add index for barcode only if column exists and index doesn't
            if (Schema::hasColumn('products', 'barcode')) {
                $indexes = DB::select("SHOW INDEX FROM products WHERE Column_name = 'barcode'");
                if (empty($indexes)) {
                    $table->index('barcode');
                }
            }
        });

        // Rename existing columns using raw SQL (only if they exist and haven't been renamed)
        if (Schema::hasColumn('products', 'product_name') && !Schema::hasColumn('products', 'name_ar')) {
            DB::statement('ALTER TABLE products CHANGE COLUMN product_name name_ar VARCHAR(255)');
        }
        if (Schema::hasColumn('products', 'product_image') && !Schema::hasColumn('products', 'image_path')) {
            DB::statement('ALTER TABLE products CHANGE COLUMN product_image image_path VARCHAR(255)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename columns back
        if (Schema::hasColumn('products', 'name_ar')) {
            DB::statement('ALTER TABLE products CHANGE COLUMN name_ar product_name VARCHAR(255)');
        }
        if (Schema::hasColumn('products', 'image_path')) {
            DB::statement('ALTER TABLE products CHANGE COLUMN image_path product_image VARCHAR(255)');
        }

        Schema::table('products', function (Blueprint $table) {
            // Restore dropped columns
            $table->enum('unit_type', ['piece', 'carton'])->default('piece');
            $table->integer('pieces_per_carton')->nullable();
            $table->decimal('piece_weight', 10, 3)->nullable();
            $table->decimal('carton_weight', 10, 3)->nullable();
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('wholesale_price', 15, 2)->nullable();
            $table->decimal('retail_price', 15, 2)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('last_purchase_date')->nullable();
            $table->date('last_sale_date')->nullable();

            // Drop new columns
            $table->dropColumn(['name_en', 'barcode', 'description', 'min_stock_alert']);
            $table->dropIndex(['barcode']);

            // Add foreign key back
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('set null');
        });
    }
};
