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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('tax_number')->nullable()->after('email');
            $table->decimal('opening_balance', 15, 2)->default(0)->after('address')->comment('Debt before system start');
            $table->decimal('current_balance', 15, 2)->default(0)->after('opening_balance')->comment('Real-time calculated debt');
            
            // Drop profile_image if it exists
            if (Schema::hasColumn('suppliers', 'profile_image')) {
                $table->dropColumn('profile_image');
            }
        });

        // Rename columns using raw SQL
        if (Schema::hasColumn('suppliers', 'company_name')) {
            DB::statement('ALTER TABLE suppliers CHANGE COLUMN company_name name VARCHAR(255)');
        }
        if (Schema::hasColumn('suppliers', 'contact_person_name')) {
            DB::statement('ALTER TABLE suppliers CHANGE COLUMN contact_person_name contact_person VARCHAR(255)');
        }
        if (Schema::hasColumn('suppliers', 'phone_number')) {
            DB::statement('ALTER TABLE suppliers CHANGE COLUMN phone_number phone VARCHAR(255)');
        }

        // Add index after renaming
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'name')) {
                $table->index('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename columns back
        DB::statement('ALTER TABLE suppliers CHANGE COLUMN name company_name VARCHAR(255)');
        DB::statement('ALTER TABLE suppliers CHANGE COLUMN contact_person contact_person_name VARCHAR(255)');
        DB::statement('ALTER TABLE suppliers CHANGE COLUMN phone phone_number VARCHAR(255)');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('tax_number');
            $table->dropColumn('opening_balance');
            $table->dropColumn('current_balance');
            $table->string('profile_image')->nullable();
            if (Schema::hasColumn('suppliers', 'name')) {
                $table->dropIndex(['name']);
            }
        });
    }
};
