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

        // Define parent categories
        $parentCategories = [
            [
                'name' => 'Food Staples & Grains',
                'description' => 'Essential food items including rice, beans, yam products, flour, and cereals',
                'subcategories' => [
                    [
                        'name' => 'Rice',
                        'description' => 'Various types of rice including parboiled, white, and local varieties',
                        'subcategories' => [
                            ['name' => 'Parboiled Rice', 'description' => 'Partially boiled rice varieties'],
                            ['name' => 'White Rice', 'description' => 'Polished white rice varieties'],
                            ['name' => 'Local Rice Varieties', 'description' => 'Indigenous rice varieties'],
                        ]
                    ],
                    [
                        'name' => 'Beans',
                        'description' => 'Various types of beans including brown beans and honey beans',
                        'subcategories' => [
                            ['name' => 'Brown Beans', 'description' => 'Brown colored bean varieties'],
                            ['name' => 'Honey Beans', 'description' => 'Sweet honey-colored bean varieties'],
                            ['name' => 'Other Bean Varieties', 'description' => 'Other bean varieties and types'],
                        ]
                    ],
                    [
                        'name' => 'Yam & Cassava Products',
                        'description' => 'Products derived from yam and cassava including flours and processed items',
                        'subcategories' => [
                            ['name' => 'Yam Flour', 'description' => 'Flour made from dried yam'],
                            ['name' => 'Cassava Flour', 'description' => 'Flour made from dried cassava'],
                            ['name' => 'Garri', 'description' => 'Processed cassava product'],
                            ['name' => 'Fufu Flour', 'description' => 'Flour used to make fufu'],
                        ]
                    ],
                    [
                        'name' => 'Flour & Semolina',
                        'description' => 'Various types of flour including wheat and plantain flour',
                        'subcategories' => [
                            ['name' => 'Wheat Flour', 'description' => 'Flour made from wheat'],
                            ['name' => 'Plantain Flour', 'description' => 'Flour made from dried plantain'],
                            ['name' => 'Semolina', 'description' => 'Coarse flour made from durum wheat'],
                        ]
                    ],
                    [
                        'name' => 'Cereals & Porridges',
                        'description' => 'Breakfast cereals and porridge mixes',
                        'subcategories' => [
                            ['name' => 'Oats', 'description' => 'Various types of oats and oatmeal'],
                            ['name' => 'Cornflakes', 'description' => 'Corn-based breakfast cereals'],
                            ['name' => 'Local Porridge Mixes', 'description' => 'Traditional porridge mixes'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Beverages',
                'description' => 'Drinks including soft drinks, juices, water, energy drinks, and traditional drinks',
                'subcategories' => [
                    [
                        'name' => 'Soft Drinks',
                        'description' => 'Carbonated beverages including cola and lemonade',
                        'subcategories' => [
                            ['name' => 'Cola', 'description' => 'Cola-flavored carbonated drinks'],
                            ['name' => 'Lemonade', 'description' => 'Lemon-flavored carbonated drinks'],
                            ['name' => 'Other Soft Drinks', 'description' => 'Other carbonated beverages'],
                        ]
                    ],
                    [
                        'name' => 'Juices & Nectars',
                        'description' => 'Fruit juices and blended drinks',
                        'subcategories' => [
                            ['name' => 'Packaged Fruit Juices', 'description' => 'Ready-to-drink fruit juices'],
                            ['name' => 'Blended Drinks', 'description' => 'Mixed fruit and vegetable drinks'],
                        ]
                    ],
                    [
                        'name' => 'Bottled Water',
                        'description' => 'Various types of bottled water',
                        'subcategories' => [
                            ['name' => 'Still Water', 'description' => 'Non-carbonated bottled water'],
                            ['name' => 'Sparkling Water', 'description' => 'Carbonated bottled water'],
                            ['name' => 'Enhanced Mineral Water', 'description' => 'Water with added minerals'],
                        ]
                    ],
                    [
                        'name' => 'Energy & Sports Drinks',
                        'description' => 'Beverages designed for energy boost and sports performance',
                    ],
                    [
                        'name' => 'Traditional Drinks',
                        'description' => 'Local traditional beverages',
                        'subcategories' => [
                            ['name' => 'Zobo', 'description' => 'Drink made from hibiscus flowers'],
                            ['name' => 'Kunu', 'description' => 'Drink made from millet or sorghum'],
                            ['name' => 'Fura de Nunu', 'description' => 'Drink made from fermented milk and millet'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories',
                'subcategories' => [
                    [
                        'name' => 'Smartphones & Accessories',
                        'description' => 'Mobile phones and related accessories',
                        'subcategories' => [
                            ['name' => 'Smartphones', 'description' => 'Mobile phones with advanced features'],
                            ['name' => 'Phone Cases', 'description' => 'Protective cases for phones'],
                            ['name' => 'Chargers & Cables', 'description' => 'Power adapters and connection cables'],
                            ['name' => 'Screen Protectors', 'description' => 'Protective films for phone screens'],
                        ]
                    ],
                    [
                        'name' => 'Computers & Laptops',
                        'description' => 'Desktop computers and portable laptops',
                        'subcategories' => [
                            ['name' => 'Laptops', 'description' => 'Portable computers'],
                            ['name' => 'Desktop Computers', 'description' => 'Non-portable computer systems'],
                            ['name' => 'Computer Accessories', 'description' => 'Peripherals and add-ons for computers'],
                        ]
                    ],
                    [
                        'name' => 'Audio & Headphones',
                        'description' => 'Sound equipment and listening devices',
                        'subcategories' => [
                            ['name' => 'Headphones', 'description' => 'Personal audio listening devices'],
                            ['name' => 'Speakers', 'description' => 'Audio output devices'],
                            ['name' => 'Microphones', 'description' => 'Audio input devices'],
                        ]
                    ],
                    [
                        'name' => 'Televisions',
                        'description' => 'TV sets and accessories',
                    ],
                ]
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, footwear, and accessories',
                'subcategories' => [
                    [
                        'name' => 'Men\'s Clothing',
                        'description' => 'Apparel for men',
                        'subcategories' => [
                            ['name' => 'Shirts', 'description' => 'Upper body garments for men'],
                            ['name' => 'Trousers', 'description' => 'Lower body garments for men'],
                            ['name' => 'Suits & Blazers', 'description' => 'Formal wear for men'],
                            ['name' => 'Traditional Wear', 'description' => 'Cultural and traditional clothing for men'],
                        ]
                    ],
                    [
                        'name' => 'Women\'s Clothing',
                        'description' => 'Apparel for women',
                        'subcategories' => [
                            ['name' => 'Dresses', 'description' => 'One-piece garments for women'],
                            ['name' => 'Tops', 'description' => 'Upper body garments for women'],
                            ['name' => 'Skirts & Trousers', 'description' => 'Lower body garments for women'],
                            ['name' => 'Traditional Wear', 'description' => 'Cultural and traditional clothing for women'],
                        ]
                    ],
                    [
                        'name' => 'Footwear',
                        'description' => 'Shoes and other foot coverings',
                        'subcategories' => [
                            ['name' => 'Men\'s Shoes', 'description' => 'Footwear for men'],
                            ['name' => 'Women\'s Shoes', 'description' => 'Footwear for women'],
                            ['name' => 'Children\'s Shoes', 'description' => 'Footwear for children'],
                        ]
                    ],
                    [
                        'name' => 'Accessories',
                        'description' => 'Fashion accessories',
                        'subcategories' => [
                            ['name' => 'Bags & Wallets', 'description' => 'Carrying accessories and money holders'],
                            ['name' => 'Jewelry', 'description' => 'Decorative accessories'],
                            ['name' => 'Watches', 'description' => 'Timepieces for wrists'],
                            ['name' => 'Belts & Ties', 'description' => 'Waist belts and neck ties'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Products for home and kitchen use',
                'subcategories' => [
                    [
                        'name' => 'Cookware & Utensils',
                        'description' => 'Items for cooking',
                        'subcategories' => [
                            ['name' => 'Pots', 'description' => 'Cooking pots'],
                            ['name' => 'Pans', 'description' => 'Cooking pans'],
                            ['name' => 'Cutlery', 'description' => 'Knives and other cutting tools'],
                        ]
                    ],
                    [
                        'name' => 'Kitchen Appliances & Gadgets',
                        'description' => 'Electronic and manual kitchen tools',
                        'subcategories' => [
                            ['name' => 'Blenders', 'description' => 'Food blending machines'],
                            ['name' => 'Mixers', 'description' => 'Food mixing machines'],
                            ['name' => 'Rice Cookers', 'description' => 'Appliances for cooking rice'],
                        ]
                    ],
                    [
                        'name' => 'Storage & Organization',
                        'description' => 'Items for storing and organizing',
                        'subcategories' => [
                            ['name' => 'Containers', 'description' => 'Food storage containers'],
                            ['name' => 'Bags', 'description' => 'Storage bags'],
                            ['name' => 'Wraps', 'description' => 'Food wrapping materials'],
                        ]
                    ],
                    [
                        'name' => 'Tableware & Serveware',
                        'description' => 'Items for serving and eating',
                        'subcategories' => [
                            ['name' => 'Plates', 'description' => 'Dishes for serving food'],
                            ['name' => 'Bowls', 'description' => 'Containers for serving food'],
                            ['name' => 'Serving Utensils', 'description' => 'Tools for serving food'],
                        ]
                    ],
                    [
                        'name' => 'General Home Essentials',
                        'description' => 'Miscellaneous home items',
                        'subcategories' => [
                            ['name' => 'Batteries', 'description' => 'Power cells for devices'],
                            ['name' => 'Light Bulbs', 'description' => 'Illumination products'],
                            ['name' => 'Small Hardware', 'description' => 'Minor hardware items'],
                        ]
                    ],
                ]
            ],
            [
                'name' => 'Groceries',
                'description' => 'Food and household essentials',
                'subcategories' => [
                    [
                        'name' => 'Fresh Produce',
                        'description' => 'Fresh fruits, vegetables, and herbs',
                        'subcategories' => [
                            ['name' => 'Fruits', 'description' => 'Fresh and seasonal fruits'],
                            ['name' => 'Vegetables', 'description' => 'Fresh vegetables'],
                            ['name' => 'Herbs', 'description' => 'Fresh cooking herbs'],
                        ]
                    ],
                    [
                        'name' => 'Dairy & Eggs',
                        'description' => 'Dairy products and eggs',
                        'subcategories' => [
                            ['name' => 'Milk', 'description' => 'Fresh and long-life milk'],
                            ['name' => 'Cheese', 'description' => 'Various cheese types'],
                            ['name' => 'Yogurt', 'description' => 'Fermented milk products'],
                            ['name' => 'Eggs', 'description' => 'Chicken and other eggs'],
                        ]
                    ],
                    [
                        'name' => 'Meat & Seafood',
                        'description' => 'Fresh and frozen meat and seafood',
                        'subcategories' => [
                            ['name' => 'Beef', 'description' => 'Cow meat products'],
                            ['name' => 'Poultry', 'description' => 'Chicken and other bird meat'],
                            ['name' => 'Fish', 'description' => 'Fresh and frozen fish'],
                            ['name' => 'Seafood', 'description' => 'Other seafood items'],
                        ]
                    ],
                    [
                        'name' => 'Bakery',
                        'description' => 'Bread and baked goods',
                        'subcategories' => [
                            ['name' => 'Bread', 'description' => 'Various types of bread'],
                            ['name' => 'Pastries', 'description' => 'Sweet baked goods'],
                            ['name' => 'Cakes', 'description' => 'Celebration and everyday cakes'],
                        ]
                    ],
                ]
            ],
        ];

        // Create categories
        $this->createCategories($parentCategories);
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
