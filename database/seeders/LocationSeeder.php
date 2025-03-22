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
        // Sample locations array - add your locations here when needed
        $locations = [];
        
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
