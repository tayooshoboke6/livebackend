<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateProductPricesSeeder extends Seeder
{
    /**
     * Run the database seeds to update product prices to Naira.
     */
    public function run(): void
    {
        // Update iPhone price (from $999.99 to ₦750,000)
        DB::table('products')
            ->where('slug', 'iphone-15-pro')
            ->update([
                'base_price' => 750000.00,
                'sale_price' => 699999.00,
            ]);

        // Update Men's Shirt price (from $49.99 to ₦15,000)
        DB::table('products')
            ->where('slug', 'mens-casual-shirt')
            ->update([
                'base_price' => 15000.00,
                'sale_price' => 12500.00,
            ]);

        // Update Women's Dress price (from $79.99 to ₦25,000)
        DB::table('products')
            ->where('slug', 'womens-summer-dress')
            ->update([
                'base_price' => 25000.00,
                'sale_price' => 19999.00,
            ]);
    }
}
