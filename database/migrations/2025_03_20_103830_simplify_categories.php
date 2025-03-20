<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class SimplifyCategories extends Migration
{
    /**
     * Run the migration.
     */
    public function up()
    {
        // First, clean up related tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Clean up category_product table
        DB::table('category_product')->truncate();
        
        // Clean up category_coupon table if it exists
        if (Schema::hasTable('category_coupon')) {
            DB::table('category_coupon')->truncate();
        }
        
        // Delete all existing categories
        DB::table('categories')->truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Create 2 main categories
        $electronics = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and accessories',
            'is_active' => true,
            'is_featured' => true,
            'color' => '#000000',
            'order' => 1,
        ]);

        $fashion = Category::create([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'description' => 'Clothing and accessories',
            'is_active' => true,
            'is_featured' => true,
            'color' => '#000000',
            'order' => 2,
        ]);

        // Create subcategories for Electronics
        Category::create([
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'description' => 'Mobile phones and accessories',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'color' => '#000000',
            'order' => 1,
        ]);

        Category::create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'description' => 'Portable computers',
            'parent_id' => $electronics->id,
            'is_active' => true,
            'color' => '#000000',
            'order' => 2,
        ]);

        // Create subcategories for Fashion
        Category::create([
            'name' => "Men's Clothing",
            'slug' => 'mens-clothing',
            'description' => 'Clothing for men',
            'parent_id' => $fashion->id,
            'is_active' => true,
            'color' => '#000000',
            'order' => 1,
        ]);

        Category::create([
            'name' => "Women's Clothing",
            'slug' => 'womens-clothing',
            'description' => 'Clothing for women',
            'parent_id' => $fashion->id,
            'is_active' => true,
            'color' => '#000000',
            'order' => 2,
        ]);
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        // We don't want to restore the old data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
