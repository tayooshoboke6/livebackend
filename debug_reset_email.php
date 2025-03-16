<?php

// Debug script for password reset notification
require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Import necessary classes
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

// Function to debug the notification process
function debugResetPasswordNotification($email)
{
    // Find the user
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        echo "User with email {$email} not found!\n";
        return;
    }
    
    echo "Found user: {$user->name} ({$user->email})\n";
    
    // Generate a token manually
    $token = Password::createToken($user);
    echo "Generated token: {$token}\n";
    
    // Create the notification
    $notification = new ResetPasswordNotification($token);
    
    // Get the mail representation
    $mailMessage = $notification->toMail($user);
    
    echo "Mail message details:\n";
    echo "Subject: {$mailMessage->subject}\n";
    
    if (isset($mailMessage->actionText) && isset($mailMessage->actionUrl)) {
        echo "Action: {$mailMessage->actionText} -> {$mailMessage->actionUrl}\n";
    } else {
        echo "No action button defined!\n";
    }
    
    // Get the reset URL
    $resetUrl = null;
    $reflectionMethod = new ReflectionMethod(ResetPasswordNotification::class, 'resetUrl');
    $reflectionMethod->setAccessible(true);
    $resetUrl = $reflectionMethod->invoke($notification, $user);
    
    echo "Reset URL: {$resetUrl}\n";
    
    // Check frontend URL configuration
    $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
    echo "Frontend URL config: {$frontendUrl}\n";
    
    // Check notification channels
    $channels = $notification->via($user);
    echo "Notification channels: " . implode(', ', $channels) . "\n";
    
    // Try to send the notification directly
    try {
        echo "Attempting to send notification directly...\n";
        $user->notify($notification);
        echo "Notification sent successfully!\n";
    } catch (\Exception $e) {
        echo "Error sending notification: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

// Run the debug function with the email address
$email = 'mmartplus1@gmail.com'; // Replace with your actual email
echo "Starting debug for email: {$email}\n";
echo "----------------------------------------\n";
debugResetPasswordNotification($email);
echo "----------------------------------------\n";
echo "Debug complete!\n";
