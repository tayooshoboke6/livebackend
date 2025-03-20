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
        // Get or create a default category
        $category = Category::first();
        if (!$category) {
            $category = Category::create([
                'name' => 'General',
                'slug' => 'general',
                'description' => 'General category for products',
                'is_active' => true,
            ]);
        }
        
        // Sample product data
        $products = [
            [
                'name' => 'Premium Coffee Beans',
                'slug' => 'premium-coffee-beans',
                'description' => 'Freshly roasted premium coffee beans from the highlands of Ethiopia. These beans are carefully selected and roasted to perfection to give you the ultimate coffee experience.',
                'short_description' => 'Freshly roasted premium coffee beans from Ethiopia.',
                'base_price' => 19.99,
                'sale_price' => 17.99,
                'is_featured' => true,
                'is_new_arrival' => true,
                'is_active' => true,
                'stock_quantity' => 100,
                'total_sold' => 25,
                'sku' => 'COFFEE-001',
                'barcode' => '8901234567890',
                'category_id' => $category->id,
                'brand' => 'Bean Masters',
                'expiry_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'meta_data' => json_encode([
                    'origin' => 'Ethiopia',
                    'roast_level' => 'Medium',
                    'flavor_notes' => ['Fruity', 'Chocolatey', 'Nutty'],
                    'weight' => '250g'
                ]),
            ],
            [
                'name' => 'Organic Green Tea',
                'slug' => 'organic-green-tea',
                'description' => 'Pure organic green tea leaves harvested from the mountains of Japan. Rich in antioxidants and known for its health benefits.',
                'short_description' => 'Pure organic green tea from Japan.',
                'base_price' => 12.99,
                'sale_price' => null,
                'is_featured' => false,
                'is_new_arrival' => true,
                'is_active' => true,
                'stock_quantity' => 150,
                'total_sold' => 10,
                'sku' => 'TEA-001',
                'barcode' => '8901234567891',
                'category_id' => $category->id,
                'brand' => 'Tea Haven',
                'expiry_date' => Carbon::now()->addYears(1)->format('Y-m-d'),
                'meta_data' => json_encode([
                    'origin' => 'Japan',
                    'type' => 'Green Tea',
                    'organic' => true,
                    'weight' => '100g'
                ]),
            ],
            [
                'name' => 'Artisanal Chocolate Bar',
                'slug' => 'artisanal-chocolate-bar',
                'description' => 'Handcrafted dark chocolate made with premium cocoa beans. This chocolate has a rich, complex flavor profile with notes of berries and nuts.',
                'short_description' => 'Handcrafted dark chocolate with premium cocoa.',
                'base_price' => 8.99,
                'sale_price' => 7.49,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_active' => true,
                'stock_quantity' => 75,
                'total_sold' => 50,
                'sku' => 'CHOC-001',
                'barcode' => '8901234567892',
                'category_id' => $category->id,
                'brand' => 'Cocoa Artisans',
                'expiry_date' => Carbon::now()->addMonths(3)->format('Y-m-d'),
                'meta_data' => json_encode([
                    'cocoa_percentage' => '72%',
                    'ingredients' => ['Cocoa beans', 'Cocoa butter', 'Sugar', 'Vanilla'],
                    'allergens' => ['May contain traces of nuts'],
                    'weight' => '100g'
                ]),
            ],
            [
                'name' => 'Gourmet Honey',
                'slug' => 'gourmet-honey',
                'description' => 'Raw, unfiltered honey collected from wildflower fields. This honey is pure, natural, and packed with flavor and nutrients.',
                'short_description' => 'Raw, unfiltered honey from wildflower fields.',
                'base_price' => 15.99,
                'sale_price' => 13.99,
                'is_featured' => false,
                'is_new_arrival' => false,
                'is_active' => true,
                'stock_quantity' => 50,
                'total_sold' => 15,
                'sku' => 'HONEY-001',
                'barcode' => '8901234567893',
                'category_id' => $category->id,
                'brand' => 'Nature\'s Gold',
                'expiry_date' => Carbon::now()->addYears(2)->format('Y-m-d'),
                'meta_data' => json_encode([
                    'type' => 'Wildflower',
                    'raw' => true,
                    'region' => 'Local Farms',
                    'weight' => '500g'
                ]),
            ],
            [
                'name' => 'Premium Olive Oil',
                'slug' => 'premium-olive-oil',
                'description' => 'Extra virgin olive oil cold-pressed from the finest olives. This oil has a fruity, peppery flavor that enhances any dish.',
                'short_description' => 'Extra virgin olive oil from the finest olives.',
                'base_price' => 22.99,
                'sale_price' => null,
                'is_featured' => true,
                'is_new_arrival' => false,
                'is_active' => true,
                'stock_quantity' => 60,
                'total_sold' => 20,
                'sku' => 'OIL-001',
                'barcode' => '8901234567894',
                'category_id' => $category->id,
                'brand' => 'Olive Grove',
                'expiry_date' => Carbon::now()->addYears(1)->format('Y-m-d'),
                'meta_data' => json_encode([
                    'origin' => 'Greece',
                    'type' => 'Extra Virgin',
                    'acidity' => '0.3%',
                    'volume' => '500ml'
                ]),
            ],
        ];
        
        foreach ($products as $productData) {
            // Create the product
            $product = Product::create($productData);
            
            // Create sample images for each product
            $imageCount = rand(1, 3);
            for ($i = 1; $i <= $imageCount; $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => "https://placehold.co/600x600/png?text=" . Str::slug($product->name) . "-$i",
                    'is_primary' => $i === 1, // First image is primary
                    'sort_order' => $i,
                ]);
            }
        }
        
        $this->command->info('Products seeded successfully with ' . count($products) . ' products.');
    }
}
