<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Manager
        // Seed Manager
        User::create([
            'name' => 'M
            anager User',
            'phone' => '07700000000',
            'password' => '123456', // Will be hashed by model cast
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->call([
            AccountSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
        ]);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
