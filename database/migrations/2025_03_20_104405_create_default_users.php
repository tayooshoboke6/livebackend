<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateDefaultUsers extends Migration
{
    /**
     * Run the migration.
     */
    public function up()
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@mmartplus.com',
            'password' => Hash::make('Welcome1@'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        // Create customer user
        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@mmartplus.com',
            'password' => Hash::make('Welcome1@'),
            'email_verified_at' => now(),
            'role' => 'customer',
        ]);
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        User::whereIn('email', [
            'admin@mmartplus.com',
            'customer@mmartplus.com'
        ])->delete();
    }
}
