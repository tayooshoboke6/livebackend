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
        // Sample sections array - add your sections here when needed
        $sections = [];
        
        foreach ($sections as $sectionData) {
            ProductSection::create($sectionData);
        }
        
        $this->command->info('Product sections seeded successfully with ' . count($sections) . ' sections.');
    }
}
