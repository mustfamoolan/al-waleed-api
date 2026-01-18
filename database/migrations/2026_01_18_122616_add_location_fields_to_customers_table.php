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
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 8)->nullable()->after('address')->comment('خط العرض');
            $table->decimal('location_lng', 11, 8)->nullable()->after('location_lat')->comment('خط الطول');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['location_lat', 'location_lng']);
        });
    }
};
