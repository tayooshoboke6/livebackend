<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the subcategory IDs
        $smartphones = DB::table('categories')->where('slug', 'smartphones')->first();
        $mensClothing = DB::table('categories')->where('slug', 'mens-clothing')->first();
        $womensClothing = DB::table('categories')->where('slug', 'womens-clothing')->first();

        // Products data
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'The latest iPhone with advanced features and powerful performance.',
                'short_description' => 'Latest Apple flagship smartphone',
                'base_price' => 999.99,
                'sale_price' => 949.99,
                'is_featured' => true,
                'is_active' => true,
                'is_new_arrival' => true,
                'stock_quantity' => 50,
                'total_sold' => 25,
                'sku' => 'IPHONE15PRO-256GB',
                'barcode' => '123456789012',
                'brand' => 'Apple',
                'category_id' => $smartphones->id,
                'meta_data' => json_encode([
                    'color' => 'Titanium Blue',
                    'storage' => '256GB',
                    'screen_size' => '6.1 inches',
                    'camera' => '48MP + 12MP + 12MP'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Men\'s Casual Shirt',
                'slug' => 'mens-casual-shirt',
                'description' => 'Comfortable and stylish casual shirt for men, perfect for everyday wear.',
                'short_description' => 'Comfortable cotton casual shirt',
                'base_price' => 49.99,
                'sale_price' => 39.99,
                'is_featured' => true,
                'is_active' => true,
                'is_new_arrival' => true,
                'stock_quantity' => 100,
                'total_sold' => 35,
                'sku' => 'MCASUALSHIRT-L-BLUE',
                'barcode' => '234567890123',
                'brand' => 'Fashion Plus',
                'category_id' => $mensClothing->id,
                'meta_data' => json_encode([
                    'color' => 'Blue',
                    'size' => 'Large',
                    'material' => '100% Cotton',
                    'fit' => 'Regular'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Women\'s Summer Dress',
                'slug' => 'womens-summer-dress',
                'description' => 'Beautiful and elegant summer dress for women, perfect for hot days and special occasions.',
                'short_description' => 'Elegant floral summer dress',
                'base_price' => 79.99,
                'sale_price' => 59.99,
                'is_featured' => true,
                'is_active' => true,
                'is_new_arrival' => true,
                'stock_quantity' => 75,
                'total_sold' => 40,
                'sku' => 'WSUMMERDRESS-M-FLORAL',
                'barcode' => '345678901234',
                'brand' => 'Elegance',
                'category_id' => $womensClothing->id,
                'meta_data' => json_encode([
                    'color' => 'Floral Print',
                    'size' => 'Medium',
                    'material' => '95% Cotton, 5% Elastane',
                    'length' => 'Knee-length'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert products
        foreach ($products as $product) {
            $productId = DB::table('products')->insertGetId($product);
            
            // Add product images
            $this->addProductImages($productId, $product['name']);
        }
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
