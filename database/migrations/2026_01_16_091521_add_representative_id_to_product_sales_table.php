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
        Schema::table('product_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('product_sales', 'representative_id')) {
                $table->unsignedBigInteger('representative_id')->nullable()->after('created_by');
                $table->foreign('representative_id')->references('rep_id')->on('representatives')->onDelete('set null');
                $table->index('representative_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropForeign(['representative_id']);
            $table->dropIndex(['representative_id']);
            $table->dropColumn('representative_id');
        });
    }
};
