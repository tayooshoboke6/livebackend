<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Instead of truncating, we'll delete existing locations if they don't have dependencies
        // and update existing ones if they do
        
        // Create sample locations
        $locations = [
            [
                'name' => 'M-Mart Downtown',
                'address' => '123 Main Street',
                'city' => 'Lagos',
                'state' => 'Lagos State',
                'zip_code' => '100001',
                'phone' => '+234 123 456 7890',
                'email' => 'downtown@mmart.com',
                'latitude' => 6.4550,
                'longitude' => 3.3841,
                'is_active' => true,
                'opening_hours' => json_encode([
                    'monday' => '08:00-20:00',
                    'tuesday' => '08:00-20:00',
                    'wednesday' => '08:00-20:00',
                    'thursday' => '08:00-20:00',
                    'friday' => '08:00-22:00',
                    'saturday' => '09:00-22:00',
                    'sunday' => '10:00-18:00'
                ])
            ],
            [
                'name' => 'M-Mart Lekki',
                'address' => '456 Lekki Road',
                'city' => 'Lagos',
                'state' => 'Lagos State',
                'zip_code' => '100002',
                'phone' => '+234 123 456 7891',
                'email' => 'lekki@mmart.com',
                'latitude' => 6.4698,
                'longitude' => 3.5852,
                'is_active' => true,
                'opening_hours' => json_encode([
                    'monday' => '08:00-20:00',
                    'tuesday' => '08:00-20:00',
                    'wednesday' => '08:00-20:00',
                    'thursday' => '08:00-20:00',
                    'friday' => '08:00-22:00',
                    'saturday' => '09:00-22:00',
                    'sunday' => '10:00-18:00'
                ])
            ],
            [
                'name' => 'M-Mart Ikeja',
                'address' => '789 Ikeja Avenue',
                'city' => 'Lagos',
                'state' => 'Lagos State',
                'zip_code' => '100003',
                'phone' => '+234 123 456 7892',
                'email' => 'ikeja@mmart.com',
                'latitude' => 6.6018,
                'longitude' => 3.3515,
                'is_active' => true,
                'opening_hours' => json_encode([
                    'monday' => '08:00-20:00',
                    'tuesday' => '08:00-20:00',
                    'wednesday' => '08:00-20:00',
                    'thursday' => '08:00-20:00',
                    'friday' => '08:00-22:00',
                    'saturday' => '09:00-22:00',
                    'sunday' => 'closed'
                ])
            ],
            [
                'name' => 'M-Mart Abuja',
                'address' => '101 Abuja Boulevard',
                'city' => 'Abuja',
                'state' => 'FCT',
                'zip_code' => '900001',
                'phone' => '+234 123 456 7893',
                'email' => 'abuja@mmart.com',
                'latitude' => 9.0765,
                'longitude' => 7.3986,
                'is_active' => true,
                'opening_hours' => json_encode([
                    'monday' => '08:00-20:00',
                    'tuesday' => '08:00-20:00',
                    'wednesday' => '08:00-20:00',
                    'thursday' => '08:00-20:00',
                    'friday' => '08:00-20:00',
                    'saturday' => '09:00-20:00',
                    'sunday' => '10:00-16:00'
                ])
            ],
            [
                'name' => 'M-Mart Port Harcourt',
                'address' => '202 Port Harcourt Road',
                'city' => 'Port Harcourt',
                'state' => 'Rivers State',
                'zip_code' => '500001',
                'phone' => '+234 123 456 7894',
                'email' => 'portharcourt@mmart.com',
                'latitude' => 4.8156,
                'longitude' => 7.0498,
                'is_active' => true,
                'opening_hours' => json_encode([
                    'monday' => '08:00-20:00',
                    'tuesday' => '08:00-20:00',
                    'wednesday' => '08:00-20:00',
                    'thursday' => '08:00-20:00',
                    'friday' => '08:00-22:00',
                    'saturday' => '09:00-22:00',
                    'sunday' => '10:00-18:00'
                ])
            ],
            [
                'name' => 'M-Mart Ibadan',
                'address' => '303 Ibadan Street',
                'city' => 'Ibadan',
                'state' => 'Oyo State',
                'zip_code' => '200001',
                'phone' => '+234 123 456 7895',
                'email' => 'ibadan@mmart.com',
                'latitude' => 7.3775,
                'longitude' => 3.9470,
                'is_active' => false, // Inactive location
                'opening_hours' => json_encode([
                    'monday' => '08:00-20:00',
                    'tuesday' => '08:00-20:00',
                    'wednesday' => '08:00-20:00',
                    'thursday' => '08:00-20:00',
                    'friday' => '08:00-22:00',
                    'saturday' => '09:00-22:00',
                    'sunday' => '10:00-18:00'
                ])
            ],
        ];
        
        foreach ($locations as $index => $locationData) {
            // Try to find existing location by name
            $location = Location::where('name', $locationData['name'])->first();
            
            if ($location) {
                // Update existing location
                $location->update($locationData);
                $this->command->info("Updated location: {$locationData['name']}");
            } else {
                // Create new location
                Location::create($locationData);
                $this->command->info("Created location: {$locationData['name']}");
            }
        }
    }
}
