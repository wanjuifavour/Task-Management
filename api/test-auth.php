<?php
/**
 * Test Authentication
 * Test login and session management
 */

// Load configuration
require_once 'config/loader.php';

// Enable CORS
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Include required files
require_once 'classes/Database.php';
require_once 'classes/User.php';

$response = [
    'status' => 'success',
    'message' => 'Authentication test',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => []
];

try {
    // Test database connection
    $db = new Database();
    $userManager = new User($db);
    
    // Test admin login
    $adminEmail = 'admin@taskmanager.com';
    $adminPassword = 'password';
    
    $response['data']['admin_login_test'] = 'Testing admin login...';
    
    try {
        $adminUser = $userManager->authenticate($adminEmail, $adminPassword);
        $response['data']['admin_login_success'] = [
            'id' => $adminUser['id'],
            'username' => $adminUser['username'],
            'email' => $adminUser['email'],
            'role' => $adminUser['role'],
            'is_admin' => $userManager->isAdmin($adminUser['id'])
        ];
    } catch (Exception $e) {
        $response['data']['admin_login_error'] = $e->getMessage();
    }
    
    // Test session management
    session_start();
    $response['data']['session_info'] = [
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'user_role' => $_SESSION['user_role'] ?? 'not set'
    ];
    
    // Test setting session
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $response['data']['session_set'] = 'Session variables set';
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Auth test failed: ' . $e->getMessage();
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT); 