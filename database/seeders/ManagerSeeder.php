<?php

namespace Database\Seeders;

use App\Models\Manager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if manager already exists
        $phoneNumber = '07742209251';
        $existingManager = Manager::where('phone_number', $phoneNumber)->first();

        if (!$existingManager) {
            Manager::create([
                'full_name' => 'مصطفى سعدي',
                'phone_number' => $phoneNumber,
                'password_hash' => Hash::make('12345678'),
                'profile_image' => null,
            ]);

            $this->command->info('✅ Manager created successfully: مصطفى سعدي');
        } else {
            $this->command->info('ℹ️  Manager already exists: مصطفى سعدي');
        }
    }
}
