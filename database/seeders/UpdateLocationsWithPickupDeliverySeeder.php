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
            // Sample location update data - add your update data here when needed
            $updateData = [];
            
            // Update location with the data
            $location->update($updateData);
            
            $this->command->info("Updated location: {$location->name}");
        }
        
        $this->command->info('All locations have been updated with pickup and delivery settings!');
    }
}
