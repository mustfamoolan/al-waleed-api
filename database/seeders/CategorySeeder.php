<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'مواد غذائية',
            'منظفات',
            'مشروبات',
            'أدوات منزلية',
        ];

        foreach ($categories as $cat) {
            ProductCategory::updateOrCreate(['name' => $cat]);
        }
    }
}
