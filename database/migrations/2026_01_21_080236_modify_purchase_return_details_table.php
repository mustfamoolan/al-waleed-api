<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_return_details', function (Blueprint $table) {
            // Add product_id column if it doesn't exist
            if (!Schema::hasColumn('purchase_return_details', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('purchase_return_id');
            }

            // Add batch_id column
            $table->unsignedBigInteger('batch_id')->nullable()->after('product_id');
        });

        // Add foreign keys separately (will be added after inventory_batches table is created)
        // Note: Foreign keys should be added in a later migration if needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_details', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_return_details', 'batch_id')) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            }
        });
    }
};
