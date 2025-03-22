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
        // Sample users array - add your users here when needed
        $users = [];
        
        foreach ($users as $userData) {
            // Try to find existing user by email
            $user = User::where('email', $userData['email'])->first();
            
            if ($user) {
                // Update existing user
                $user->update($userData);
                $this->command->info("Updated user: {$userData['email']}");
            } else {
                // Create new user
                User::create($userData);
                $this->command->info("Created user: {$userData['email']}");
            }
        }
    }
}
