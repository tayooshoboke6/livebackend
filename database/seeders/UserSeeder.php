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
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@mmart.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Create test customer
        User::create([
            'name' => 'Test Customer',
            'email' => 'customer@mmart.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);
    }
}
