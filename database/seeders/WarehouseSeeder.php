<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default warehouse if it doesn't exist
        Warehouse::firstOrCreate(
            ['name' => 'المستودع الرئيسي'],
            [
                'location' => 'الموقع الرئيسي',
                'is_active' => true,
            ]
        );
    }
}
