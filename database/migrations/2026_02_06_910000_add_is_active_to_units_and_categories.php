<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_base');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
