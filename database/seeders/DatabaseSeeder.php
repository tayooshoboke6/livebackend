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
use Database\Seeders\OrderSeeder;
use Database\Seeders\AdminUserSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
