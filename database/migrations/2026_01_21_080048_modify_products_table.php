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
        Schema::table('products', function (Blueprint $table) {
            // Add new columns
            $table->string('name_en')->nullable()->after('product_name');
            $table->string('barcode')->nullable()->after('sku');
            $table->text('description')->nullable()->after('category_id');
            $table->integer('min_stock_alert')->default(0)->after('product_image');

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

            // Add index for barcode
            $table->index('barcode');
        });

        // Rename existing columns using raw SQL
        DB::statement('ALTER TABLE products CHANGE COLUMN product_name name_ar VARCHAR(255)');
        DB::statement('ALTER TABLE products CHANGE COLUMN product_image image_path VARCHAR(255)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename columns back
        DB::statement('ALTER TABLE products CHANGE COLUMN name_ar product_name VARCHAR(255)');
        DB::statement('ALTER TABLE products CHANGE COLUMN image_path product_image VARCHAR(255)');

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
