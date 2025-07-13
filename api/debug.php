<?php
/**
 * Debug Script
 * Test backend functionality and database state
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
require_once 'classes/Task.php';

$response = [
    'status' => 'success',
    'message' => 'Debug information',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => []
];

try {
    // Test database connection
    $db = new Database();
    $response['data']['database'] = 'Connected successfully';
    
    // Test user management
    $userManager = new User($db);
    $response['data']['user_management'] = 'User class loaded successfully';
    
    // Test task management
    $taskManager = new Task($db);
    $response['data']['task_management'] = 'Task class loaded successfully';
    
    // Get user statistics
    $userStats = $userManager->getStats();
    $response['data']['user_stats'] = $userStats;
    
    // Get task statistics
    $taskStats = $taskManager->getStats();
    $response['data']['task_stats'] = $taskStats;
    
    // Get all users
    $allUsers = $userManager->getAll();
    $response['data']['all_users'] = $allUsers;
    
    // Get all tasks
    $allTasks = $taskManager->getAll();
    $response['data']['all_tasks'] = $allTasks;
    
    // Test admin user
    $adminUser = $userManager->findByEmail('admin@taskmanager.com');
    if ($adminUser) {
        $response['data']['admin_user'] = [
            'id' => $adminUser['id'],
            'username' => $adminUser['username'],
            'email' => $adminUser['email'],
            'role' => $adminUser['role'],
            'is_admin' => $userManager->isAdmin($adminUser['id'])
        ];
    } else {
        $response['data']['admin_user'] = 'Not found';
    }
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Debug failed: ' . $e->getMessage();
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT); 