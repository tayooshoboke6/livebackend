<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@martplus.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Welcome1@'),
                'role' => 'admin',
            ]
        );

        // Create customer user
        User::updateOrCreate(
            ['email' => 'customer@martplus.com'],
            [
                'name' => 'Test Customer',
                'password' => Hash::make('Welcome1@'),
                'role' => 'customer',
            ]
        );

        // Keep existing users for backward compatibility
        User::updateOrCreate(
            ['email' => 'admin@mmart.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@mmart.com'],
            [
                'name' => 'Test Customer',
                'password' => Hash::make('password123'),
                'role' => 'customer',
            ]
        );
    }
}
