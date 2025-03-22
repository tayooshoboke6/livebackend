<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Sample coupons array - add your coupons here when needed
        $coupons = [];
        
        foreach ($coupons as $couponData) {
            Coupon::create($couponData);
        }
        
        $this->command->info('Coupons seeded successfully with ' . count($coupons) . ' coupons.');
    }
}
