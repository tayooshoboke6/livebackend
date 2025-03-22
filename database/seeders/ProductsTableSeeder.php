<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Product;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample products array - add your products here when needed
        $products = [];
        
        foreach ($products as $productData) {
            Product::create($productData);
        }
        
        $this->command->info('Products seeded successfully with ' . count($products) . ' products.');
    }

    /**
     * Add images for a product
     */
    private function addProductImages($productId, $productName)
    {
        $imageData = [];
        $placeholder = str_replace("'", "", str_replace(' ', '+', $productName));
        
        // Add 3 images for each product
        for ($i = 1; $i <= 3; $i++) {
            $imageData[] = [
                'product_id' => $productId,
                'image_path' => "https://placehold.co/600x800?text={$placeholder}+Image+{$i}",
                'is_primary' => $i === 1, // First image is primary
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('product_images')->insert($imageData);
    }
}
