<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'قطعة', 'is_base' => true],
            ['name' => 'كارتون', 'is_base' => false],
            ['name' => 'كغم', 'is_base' => false],
            ['name' => 'لتر', 'is_base' => false],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['name' => $unit['name']], $unit);
        }
    }
}
