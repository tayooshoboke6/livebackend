<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add main categories
        $mainCategories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'All electronic devices and accessories',
                'image' => 'https://placehold.co/600x400?text=Electronics',
                'is_active' => true,
                'is_featured' => true,
                'color' => '#3498db', // Blue
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'Clothing, shoes, and accessories',
                'image' => 'https://placehold.co/600x400?text=Fashion',
                'is_active' => true,
                'is_featured' => true,
                'color' => '#e74c3c', // Red
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert main categories
        DB::table('categories')->insert($mainCategories);

        // Get the IDs of the inserted main categories
        $electronics = DB::table('categories')->where('slug', 'electronics')->first();
        $fashion = DB::table('categories')->where('slug', 'fashion')->first();

        // Add subcategories
        $subCategories = [
            [
                'name' => 'Smartphones',
                'slug' => 'smartphones',
                'description' => 'Mobile phones and accessories',
                'image' => 'https://placehold.co/600x400?text=Smartphones',
                'is_active' => true,
                'parent_id' => $electronics->id,
                'is_featured' => false,
                'color' => '#2980b9', // Darker blue
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Men\'s Clothing',
                'slug' => 'mens-clothing',
                'description' => 'Clothing for men',
                'image' => 'https://placehold.co/600x400?text=Men\'s+Clothing',
                'is_active' => true,
                'parent_id' => $fashion->id,
                'is_featured' => false,
                'color' => '#c0392b', // Darker red
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Women\'s Clothing',
                'slug' => 'womens-clothing',
                'description' => 'Clothing for women',
                'image' => 'https://placehold.co/600x400?text=Women\'s+Clothing',
                'is_active' => true,
                'parent_id' => $fashion->id,
                'is_featured' => false,
                'color' => '#d35400', // Orange
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert subcategories
        DB::table('categories')->insert($subCategories);
    }
}
