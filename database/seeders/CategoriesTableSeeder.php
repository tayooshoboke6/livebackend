<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Category;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample categories array - add your categories here when needed
        $categories = [];
        
        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
        
        $this->command->info('Categories seeded successfully with ' . count($categories) . ' categories.');
    }
}
