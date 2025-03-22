<?php

namespace Database\Seeders;

use App\Models\MessageCampaign;
use Illuminate\Database\Seeder;

class MessageCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample campaigns array - add your campaigns here when needed
        $campaigns = [];
        
        foreach ($campaigns as $campaignData) {
            MessageCampaign::create($campaignData);
        }
        
        $this->command->info('Message campaigns seeded successfully with ' . count($campaigns) . ' campaigns.');
    }
}
