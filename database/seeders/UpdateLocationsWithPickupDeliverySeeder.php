<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;

class UpdateLocationsWithPickupDeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::all();

        foreach ($locations as $location) {
            // Set default values for pickup and delivery fields
            $location->update([
                // Basic availability fields - enable by default
                'is_pickup_available' => true,
                'is_delivery_available' => true,
                
                // Delivery configuration fields
                'delivery_radius_km' => 10.0, // 10 km radius
                'delivery_zone_polygon' => null, // Will be set by admin later
                'delivery_base_fee' => 500.0, // ₦5.00 base fee
                'delivery_fee_per_km' => 100.0, // ₦1.00 per km
                'delivery_free_threshold' => 10000.0, // Free delivery for orders over ₦100.00
                'delivery_min_order' => 2000.0, // Minimum order of ₦20.00 for delivery
                
                // Additional delivery settings
                'max_delivery_distance_km' => 15.0, // 15 km max distance
                'outside_geofence_fee' => 300.0, // ₦3.00 additional fee outside geofence
                'order_value_adjustments' => [
                    [
                        'orderValueThreshold' => 5000, // ₦50.00
                        'adjustmentType' => 'percentage',
                        'adjustmentValue' => 50 // 50% off delivery fee
                    ],
                    [
                        'orderValueThreshold' => 10000, // ₦100.00
                        'adjustmentType' => 'fixed',
                        'adjustmentValue' => 0 // Free delivery
                    ]
                ]
            ]);
            
            $this->command->info("Updated location: {$location->name}");
        }
        
        $this->command->info('All locations have been updated with pickup and delivery settings!');
    }
}
