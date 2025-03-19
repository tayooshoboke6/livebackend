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
    public function run()
    {
        // Create WELCOME25 coupon - 15% off, expires in 48 hours, one-time use per user
        Coupon::create([
            'code' => 'WELCOME25',
            'type' => 'percentage',
            'value' => 15.00,
            'min_order_amount' => 0.00,
            'max_discount_amount' => null,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addHours(48),
            'usage_limit' => 1, // One-time use per coupon code
            'used_count' => 0,
            'is_active' => true,
            'description' => 'Welcome discount - 15% off your first order',
        ]);

        // Create WELCOME26 coupon - 15% off, expires in 48 hours, one-time use per user
        Coupon::create([
            'code' => 'WELCOME26',
            'type' => 'percentage',
            'value' => 15.00,
            'min_order_amount' => 0.00,
            'max_discount_amount' => null,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addHours(48),
            'usage_limit' => 1, // One-time use per coupon code
            'used_count' => 0,
            'is_active' => true,
            'description' => 'Welcome discount - 15% off your first order',
        ]);
    }
}
