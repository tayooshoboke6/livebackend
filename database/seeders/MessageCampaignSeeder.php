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
        // Create sample message campaigns
        MessageCampaign::create([
            'title' => 'Welcome to IDonTaya',
            'subject' => 'Welcome to our marketplace!',
            'content' => '<p>Hello and welcome to IDonTaya!</p><p>We\'re excited to have you join our community. Here are some tips to get you started:</p><ul><li>Browse our wide selection of products</li><li>Check out our special offers</li><li>Update your profile for personalized recommendations</li></ul><p>Happy shopping!</p>',
            'user_segment' => 'new_users',
            'send_to_email' => true,
            'send_to_inbox' => true,
            'status' => 'sent',
            'sent_at' => now()->subDays(7),
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(7)
        ]);

        MessageCampaign::create([
            'title' => 'Weekend Special Offers',
            'subject' => 'Don\'t miss our weekend deals!',
            'content' => '<p>Dear valued customer,</p><p>We\'re excited to announce our special weekend offers! For a limited time, enjoy:</p><ul><li>20% off on all electronics</li><li>Buy one, get one free on selected items</li><li>Free shipping on orders over $50</li></ul><p>Hurry, these offers end Sunday at midnight!</p>',
            'user_segment' => 'all',
            'send_to_email' => true,
            'send_to_inbox' => true,
            'status' => 'sent',
            'sent_at' => now()->subDays(3),
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(3)
        ]);

        MessageCampaign::create([
            'title' => 'Premium Customer Appreciation',
            'subject' => 'A special thank you to our premium customers',
            'content' => '<p>Dear Premium Customer,</p><p>We value your continued support and loyalty. As a token of our appreciation, we\'re offering you:</p><ul><li>Exclusive 25% discount on your next purchase</li><li>Early access to our new product line</li><li>Free priority shipping on all orders</li></ul><p>Thank you for being a valued premium customer!</p>',
            'user_segment' => 'premium',
            'send_to_email' => true,
            'send_to_inbox' => true,
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(2),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay()
        ]);

        MessageCampaign::create([
            'title' => 'We Miss You!',
            'subject' => 'Come back and see what\'s new',
            'content' => '<p>Hello there,</p><p>We\'ve noticed you haven\'t visited us in a while. We\'ve added many new products and features since your last visit!</p><p>To welcome you back, we\'re offering a special 15% discount on your next purchase. Use code <strong>WELCOME15</strong> at checkout.</p><p>We hope to see you soon!</p>',
            'user_segment' => 'inactive',
            'send_to_email' => true,
            'send_to_inbox' => true,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
