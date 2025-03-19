<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\BottledWaterProductSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\ProductSectionSeeder;
use Database\Seeders\MessageCampaignSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        
        $this->call([
            LocationSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductSectionSeeder::class,
            BottledWaterProductSeeder::class,
            MessageCampaignSeeder::class,
        ]);
    }
}
