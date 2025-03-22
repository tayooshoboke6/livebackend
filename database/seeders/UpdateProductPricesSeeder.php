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
        // Sample product price updates - add your price updates here when needed
        $priceUpdates = [];
        
        foreach ($priceUpdates as $update) {
            DB::table('products')
                ->where('slug', $update['slug'])
                ->update([
                    'base_price' => $update['base_price'],
                    'sale_price' => $update['sale_price'],
                ]);
        }
    }
}
