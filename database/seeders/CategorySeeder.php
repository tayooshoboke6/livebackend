<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safely delete existing categories without truncate to avoid foreign key constraint issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Define parent categories - add your categories here when needed
        $parentCategories = [];

        // Create categories
        foreach ($parentCategories as $parentData) {
            $this->createCategory($parentData);
        }
    }

    /**
     * Create categories recursively.
     *
     * @param array $categories
     * @param int|null $parentId
     * @return void
     */
    private function createCategories(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $categoryData) {
            $slug = Str::slug($categoryData['name']);
            
            // Check if slug exists
            $count = 1;
            $originalSlug = $slug;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => $slug,
                'description' => $categoryData['description'] ?? null,
                'parent_id' => $parentId,
                'is_active' => true,
            ]);
            
            // Create subcategories if they exist
            if (isset($categoryData['subcategories']) && is_array($categoryData['subcategories'])) {
                $this->createCategories($categoryData['subcategories'], $category->id);
            }
        }
    }
}
