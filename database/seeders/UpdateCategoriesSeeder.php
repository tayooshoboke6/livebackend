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
        
        // Array of vibrant colors for categories
        $colors = [
            '#4CAF50', // Green
            '#2196F3', // Blue
            '#F44336', // Red
            '#FF9800', // Orange
            '#9C27B0', // Purple
            '#00BCD4', // Cyan
            '#FFEB3B', // Yellow
            '#795548', // Brown
            '#607D8B', // Blue Grey
            '#E91E63', // Pink
            '#673AB7', // Deep Purple
            '#3F51B5', // Indigo
            '#009688', // Teal
            '#8BC34A', // Light Green
            '#CDDC39', // Lime
            '#FFC107', // Amber
            '#FF5722', // Deep Orange
        ];
        
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
