<?php

// Simple script to test Brevo API directly
require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get Brevo API key from environment
$apiKey = $_ENV['BREVO_API_KEY'];
$fromEmail = $_ENV['MAIL_FROM_ADDRESS'];
$fromName = $_ENV['MAIL_FROM_NAME'];

// Set recipient details
$toEmail = 'mmartplus1@gmail.com'; // The email you want to test with
$toName = 'M-Mart+ User';

// Create HTML content
$htmlContent = '
<html>
<body>
    <h1>Test Email from M-Mart+</h1>
    <p>This is a test email sent directly through the Brevo API.</p>
    <p>If you receive this email, it confirms that your Brevo API integration is working correctly.</p>
    <div style="margin: 30px auto; text-align: center;">
        <a href="http://localhost:8000" style="background-color: #3490dc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-family: Arial, sans-serif;">
            Test Button
        </a>
    </div>
    <p>Thank you for testing!</p>
</body>
</html>
';

// Prepare the request
$url = 'https://api.brevo.com/v3/smtp/email';
$data = [
    'sender' => [
        'name' => $fromName,
        'email' => $fromEmail,
    ],
    'to' => [
        [
            'email' => $toEmail,
            'name' => $toName,
        ]
    ],
    'subject' => 'M-Mart+ Test Email',
    'htmlContent' => $htmlContent,
];

// Send the request using cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json',
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Output the results
echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "Email sent successfully!\n";
} else {
    echo "Failed to send email.\n";
}
