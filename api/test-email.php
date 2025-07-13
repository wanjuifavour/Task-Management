<?php
/**
 * Test Email Configuration
 * Debug email sending issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'config/loader.php';

// Include required files
require_once 'classes/EmailService.php';

// Enable CORS
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'status' => 'success',
    'message' => 'Email configuration test',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => []
];

try {
    // Check environment variables
    $response['data']['environment'] = [
        'MAIL_FROM' => $_ENV['MAIL_FROM'] ?? 'not set',
        'MAIL_FROM_NAME' => $_ENV['MAIL_FROM_NAME'] ?? 'not set',
        'SMTP_HOST' => $_ENV['SMTP_HOST'] ?? 'not set',
        'SMTP_PORT' => $_ENV['SMTP_PORT'] ?? 'not set',
        'SMTP_USERNAME' => $_ENV['SMTP_USERNAME'] ?? 'not set',
        'SMTP_PASSWORD' => $_ENV['SMTP_PASSWORD'] ? 'set (hidden)' : 'not set'
    ];
    
    // Check if PHPMailer is available
    $response['data']['phpmailer'] = class_exists('PHPMailer\PHPMailer\PHPMailer') ? 'Available' : 'Not available';
    
    // Test email service
    $emailService = new EmailService();
    
    // Check email service configuration
    $reflection = new ReflectionClass($emailService);
    $useSMTPProperty = $reflection->getProperty('useSMTP');
    $useSMTPProperty->setAccessible(true);
    $useSMTP = $useSMTPProperty->getValue($emailService);
    
    $response['data']['email_service'] = [
        'use_smtp' => $useSMTP ? 'Yes' : 'No',
        'from_email' => $reflection->getProperty('fromEmail')->getValue($emailService),
        'from_name' => $reflection->getProperty('fromName')->getValue($emailService),
        'smtp_host' => $reflection->getProperty('smtpHost')->getValue($emailService),
        'smtp_port' => $reflection->getProperty('smtpPort')->getValue($emailService),
        'smtp_username' => $reflection->getProperty('smtpUsername')->getValue($emailService),
        'smtp_password' => $reflection->getProperty('smtpPassword')->getValue($emailService) ? 'set (hidden)' : 'not set'
    ];
    
    // Test sending a real email
    if (isset($_GET['test_email'])) {
        $testEmail = $_GET['test_email'];
        $result = $emailService->testEmailConfiguration($testEmail);
        $response['data']['test_result'] = $result ? 'Email sent successfully' : 'Failed to send email';
    }
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['error'] = $e->getMessage();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT); 