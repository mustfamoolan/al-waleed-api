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
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->enum('unit_type', ['piece', 'carton'])->default('carton')->comment('نوع الوحدة - قطعة أم كارتون')->after('quantity');
            $table->decimal('carton_count', 10, 2)->nullable()->comment('عدد الكارتونات (إذا unit_type = carton)')->after('unit_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'carton_count']);
        });
    }
};
