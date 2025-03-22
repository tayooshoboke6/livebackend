<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample product data - add your products here when needed
        $products = [];
        
        foreach ($products as $productData) {
            // Create the product
            $product = Product::create($productData);
            
            // Create sample images for each product
            $imageCount = rand(1, 3);
            for ($i = 1; $i <= $imageCount; $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => "https://placehold.co/600x400?font=roboto&text=" . urlencode($product->name),
                    'is_primary' => $i === 1, // First image is primary
                    'sort_order' => $i,
                ]);
            }
        }
        
        $this->command->info('Products seeded successfully with ' . count($products) . ' products.');
    }
}
