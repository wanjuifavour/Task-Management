<?php
/**
 * Test Endpoint
 * Used to verify backend functionality
 */

// Load configuration
require_once 'config/loader.php';

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

// Include required files
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Task.php';
require_once 'classes/EmailService.php';

$response = [
    'status' => 'success',
    'message' => 'Backend is working correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

try {
    // Test database connection
    $db = new Database();
    $response['tests']['database'] = 'Connected successfully';
    
    // Test user management
    $userManager = new User($db);
    $response['tests']['user_management'] = 'User class loaded successfully';
    
    // Test task management
    $taskManager = new Task($db);
    $response['tests']['task_management'] = 'Task class loaded successfully';
    
    // Test email service
    $emailService = new EmailService();
    $response['tests']['email_service'] = 'Email service loaded successfully';
    
    // Test environment variables
    $response['tests']['environment'] = [
        'db_host' => $_ENV['DB_HOST'] ?? 'not set',
        'db_database' => $_ENV['DB_DATABASE'] ?? 'not set',
        'smtp_host' => $_ENV['SMTP_HOST'] ?? 'not set'
    ];
    
    // Test database queries
    $userStats = $userManager->getStats();
    $response['tests']['database_queries'] = 'Database queries working';
    $response['user_stats'] = $userStats;
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Backend test failed: ' . $e->getMessage();
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT); 