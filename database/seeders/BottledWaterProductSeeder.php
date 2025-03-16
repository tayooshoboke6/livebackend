<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductMeasurement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BottledWaterProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the "Still Water" category under "Bottled Water"
        $category = Category::where('name', 'Still Water')->first();
        
        if (!$category) {
            // If the specific category doesn't exist, find the parent "Bottled Water" category
            $category = Category::where('name', 'Bottled Water')->first();
            
            if (!$category) {
                // If "Bottled Water" doesn't exist, use the "Beverages" category
                $category = Category::where('name', 'Beverages')->first();
                
                if (!$category) {
                    // If all else fails, create a new category
                    $parentCategory = Category::create([
                        'name' => 'Beverages',
                        'slug' => 'beverages',
                        'description' => 'Drinks including soft drinks, juices, water, energy drinks, and traditional drinks',
                    ]);
                    
                    $category = Category::create([
                        'name' => 'Bottled Water',
                        'slug' => 'bottled-water',
                        'description' => 'Various types of bottled water',
                        'parent_id' => $parentCategory->id,
                    ]);
                }
            }
        }
        
        // Check if product already exists
        $existingProduct = Product::where('name', 'Premium Bottled Water')->first();
        
        if ($existingProduct) {
            $this->command->info('Bottled Water product already exists. Updating measurements...');
            $product = $existingProduct;
        } else {
            // Create a unique slug with a random suffix
            $slug = 'premium-bottled-water-' . Str::random(6);
            
            // Create the bottled water product
            $product = Product::create([
                'name' => 'Premium Bottled Water',
                'slug' => $slug,
                'description' => 'Pure, refreshing bottled water, perfect for hydration on the go',
                'base_price' => 500.00,
                'sale_price' => 450.00,
                'is_featured' => true,
                'is_active' => true,
                'stock_quantity' => 200,
                'sku' => 'WATER-001',
                'barcode' => 'WATER001BAR',
                'category_id' => $category->id,
                'brand' => 'M-Mart Select',
                'meta_data' => json_encode([
                    'weight' => '75cl',
                    'origin' => 'Nigeria',
                ]),
            ]);
        }
        
        // Delete existing measurements for this product
        ProductMeasurement::where('product_id', $product->id)->delete();
        
        // Create measurements for the product
        
        // Pack of 6 bottles
        ProductMeasurement::create([
            'product_id' => $product->id,
            'unit' => 'Pack',
            'value' => 6,
            'price' => 2700.00, // 6 bottles at 450 each
            'sale_price' => 2500.00, // with a small discount
            'stock_quantity' => 30,
            'sku' => 'WATER-PACK-6-' . Str::random(4),
            'is_default' => false,
            'is_active' => true,
        ]);
        
        // Pack of 12 bottles
        ProductMeasurement::create([
            'product_id' => $product->id,
            'unit' => 'Pack',
            'value' => 12,
            'price' => 5400.00, // 12 bottles at 450 each
            'sale_price' => 5000.00, // with a discount
            'stock_quantity' => 15,
            'sku' => 'WATER-PACK-12-' . Str::random(4),
            'is_default' => false,
            'is_active' => true,
        ]);
        
        // Single bottle (piece)
        ProductMeasurement::create([
            'product_id' => $product->id,
            'unit' => 'Piece',
            'value' => 1,
            'price' => 500.00, // Regular price
            'sale_price' => 450.00, // Sale price
            'stock_quantity' => 80,
            'sku' => 'WATER-PIECE-' . Str::random(4),
            'is_default' => true,
            'is_active' => true,
        ]);
        
        $this->command->info('Bottled Water product seeded successfully!');
    }
}
