<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductSection;
use App\Models\Product;

class ProductSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some product IDs to use in our sections
        $productIds = Product::where('is_active', true)->limit(20)->pluck('id')->toArray();
        
        if (empty($productIds)) {
            $this->command->info('No products found. Please seed products first.');
            return;
        }
        
        // Create Featured Products Section
        ProductSection::create([
            'title' => 'Featured Products',
            'description' => 'Our handpicked selection of featured products',
            'type' => 'featured',
            'background_color' => '#f8f9fa',
            'text_color' => '#212529',
            'product_ids' => array_slice($productIds, 0, 6),
            'display_order' => 1,
            'is_active' => true,
        ]);
        
        // Create Hot Deals Section
        ProductSection::create([
            'title' => 'Hot Deals',
            'description' => 'Limited time offers with amazing discounts',
            'type' => 'hot_deals',
            'background_color' => '#fff3cd',
            'text_color' => '#856404',
            'product_ids' => array_slice($productIds, 6, 6),
            'display_order' => 2,
            'is_active' => true,
        ]);
        
        // Create New Arrivals Section
        ProductSection::create([
            'title' => 'New Arrivals',
            'description' => 'Check out our latest products',
            'type' => 'new_arrivals',
            'background_color' => '#d1e7dd',
            'text_color' => '#0f5132',
            'product_ids' => array_slice($productIds, 12, 6),
            'display_order' => 3,
            'is_active' => true,
        ]);
        
        $this->command->info('Product sections seeded successfully.');
    }
}
