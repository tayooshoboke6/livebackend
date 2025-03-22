<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@mmart.com',
            'password' => Hash::make('admin123'),
            'phone' => '1234567890',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
} 