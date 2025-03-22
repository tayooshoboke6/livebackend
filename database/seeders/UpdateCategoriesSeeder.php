<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder updates existing categories with random colors
     * and sets some categories as featured.
     */
    public function run(): void
    {
        // Get all categories
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->info('No categories found to update.');
            return;
        }
        
        // Array of vibrant colors for categories - add your colors here when needed
        $colors = [];
        
        // Update each category with a random color
        foreach ($categories as $index => $category) {
            // Assign a random color from our array
            $colorIndex = $index % count($colors);
            $category->color = $colors[$colorIndex];
            
            // Set some categories as featured (e.g., every 3rd category)
            $category->is_featured = ($index % 3 === 0);
            
            $category->save();
            
            $this->command->info("Updated category: {$category->name} - Color: {$category->color} - Featured: " . ($category->is_featured ? 'Yes' : 'No'));
        }
        
        $this->command->info('Categories updated successfully with colors and featured flags.');
    }
}
