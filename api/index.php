<?php
/**
 * API Router
 * Main entry point for all API requests
 */

// Load configuration
require_once 'config/loader.php';

// Load Composer autoloader for PHPMailer
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// Enable CORS
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:3000', 
    'https://task-management-cytonn.vercel.app',
    'https://www.task-management-cytonn.vercel.app'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Include required files
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Task.php';
require_once 'classes/EmailService.php';

// Initialize database
try {
    $db = new Database();
    $userManager = new User($db);
    $taskManager = new Task($db);
    $emailService = new EmailService();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get request information
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($requestPath, '/'));

// Remove 'api' from path parts if present
if (isset($pathParts[0]) && $pathParts[0] === 'api') {
    array_shift($pathParts);
}

$endpoint = $pathParts[0] ?? '';
$resource = $pathParts[1] ?? '';

// Helper function to get JSON input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Helper function to check authentication
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(['error' => 'Unauthorized'], 401);
    }
    return $_SESSION['user_id'];
}

// Helper function to check admin privileges
function checkAdmin($userManager) {
    $userId = checkAuth();
    if (!$userManager->isAdmin($userId)) {
        sendResponse(['error' => 'Admin access required'], 403);
    }
    return $userId;
}

// Route handling
try {
    switch ($endpoint) {
        case 'auth':
            handleAuth($requestMethod, $userManager);
            break;
            
        case 'users':
            handleUsers($requestMethod, $resource, $userManager);
            break;
            
        case 'tasks':
            handleTasks($requestMethod, $resource, $taskManager, $userManager, $emailService);
            break;
            
        case 'dashboard':
            handleDashboard($requestMethod, $userManager, $taskManager);
            break;
            
        default:
            sendResponse(['error' => 'Endpoint not found'], 404);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse(['error' => 'Internal server error'], 500);
}

/**
 * Handle authentication endpoints
 */
function handleAuth($method, $userManager) {
    switch ($method) {
        case 'POST':
            $data = getJsonInput();
            
            if (isset($data['action']) && $data['action'] === 'login') {
                // Login
                if (!isset($data['email']) || !isset($data['password'])) {
                    sendResponse(['error' => 'Email and password required'], 400);
                }
                
                try {
                    $user = $userManager->authenticate($data['email'], $data['password']);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    sendResponse([
                        'message' => 'Login successful',
                        'user' => $user
                    ]);
                } catch (Exception $e) {
                    sendResponse(['error' => $e->getMessage()], 401);
                }
            } elseif (isset($data['action']) && $data['action'] === 'register') {
                // Registration
                if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                    sendResponse(['error' => 'Username, email, and password required'], 400);
                }
                
                // Validate email format
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    sendResponse(['error' => 'Invalid email format'], 400);
                }
                
                // Validate password strength (minimum 6 characters)
                if (strlen($data['password']) < 6) {
                    sendResponse(['error' => 'Password must be at least 6 characters long'], 400);
                }
                
                // Validate username (alphanumeric and underscore only, 3-20 characters)
                if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
                    sendResponse(['error' => 'Username must be 3-20 characters long and contain only letters, numbers, and underscores'], 400);
                }
                
                try {
                    $user = $userManager->create(
                        $data['username'],
                        $data['email'],
                        $data['password'],
                        'user' // Default role for public registration
                    );
                    
                    // Auto-login after registration
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    sendResponse([
                        'message' => 'Registration successful',
                        'user' => $user
                    ], 201);
                } catch (Exception $e) {
                    sendResponse(['error' => $e->getMessage()], 400);
                }
            } elseif (isset($data['action']) && $data['action'] === 'logout') {
                // Logout
                session_destroy();
                sendResponse(['message' => 'Logout successful']);
            } else {
                sendResponse(['error' => 'Invalid action'], 400);
            }
            break;
            
        case 'GET':
            // Check current authentication status
            if (isset($_SESSION['user_id'])) {
                $user = $userManager->findById($_SESSION['user_id']);
                sendResponse(['authenticated' => true, 'user' => $user]);
            } else {
                sendResponse(['authenticated' => false]);
            }
            break;
            
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
}

/**
 * Handle user management endpoints
 */
function handleUsers($method, $resource, $userManager) {
    switch ($method) {
        case 'GET':
            if ($resource === 'me') {
                // Get current user info
                $userId = checkAuth();
                $user = $userManager->findById($userId);
                sendResponse(['user' => $user]);
            } elseif ($resource) {
                // Get specific user (admin only)
                checkAdmin($userManager);
                $user = $userManager->findById($resource);
                if (!$user) {
                    sendResponse(['error' => 'User not found'], 404);
                }
                sendResponse(['user' => $user]);
            } else {
                // Get all users (admin only)
                checkAdmin($userManager);
                $users = $userManager->getAll();
                sendResponse(['users' => $users]);
            }
            break;
            
        case 'POST':
            // Create new user (admin only)
            checkAdmin($userManager);
            $data = getJsonInput();
            
            if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                sendResponse(['error' => 'Username, email, and password required'], 400);
            }
            
            try {
                $user = $userManager->create(
                    $data['username'],
                    $data['email'],
                    $data['password'],
                    $data['role'] ?? 'user'
                );
                sendResponse(['message' => 'User created successfully', 'user' => $user], 201);
            } catch (Exception $e) {
                sendResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        case 'PUT':
            if (!$resource) {
                sendResponse(['error' => 'User ID required'], 400);
            }
            
            $userId = checkAuth();
            $data = getJsonInput();
            
            // Check if user can edit this profile
            if ($resource != $userId && !$userManager->isAdmin($userId)) {
                sendResponse(['error' => 'Permission denied'], 403);
            }
            
            try {
                $user = $userManager->update($resource, $data);
                sendResponse(['message' => 'User updated successfully', 'user' => $user]);
            } catch (Exception $e) {
                sendResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        case 'DELETE':
            if (!$resource) {
                sendResponse(['error' => 'User ID required'], 400);
            }
            
            checkAdmin($userManager);
            
            try {
                $userManager->delete($resource);
                sendResponse(['message' => 'User deleted successfully']);
            } catch (Exception $e) {
                sendResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
}

/**
 * Handle task management endpoints
 */
function handleTasks($method, $resource, $taskManager, $userManager, $emailService) {
    switch ($method) {
        case 'GET':
            $userId = checkAuth();
            $isAdmin = $userManager->isAdmin($userId);
            
            if ($resource === 'stats') {
                // Get task statistics
                $stats = $taskManager->getStats();
                sendResponse(['stats' => $stats]);
            } elseif ($resource === 'overdue') {
                // Get overdue tasks
                $tasks = $taskManager->getOverdueTasks();
                sendResponse(['tasks' => $tasks]);
            } elseif ($resource === 'upcoming') {
                // Get upcoming tasks
                $tasks = $taskManager->getUpcomingTasks();
                sendResponse(['tasks' => $tasks]);
            } elseif ($resource) {
                // Get specific task
                $task = $taskManager->findById($resource);
                if (!$task) {
                    sendResponse(['error' => 'Task not found'], 404);
                }
                
                // Check if user can view this task
                if (!$isAdmin && $task['assigned_to'] != $userId) {
                    sendResponse(['error' => 'Permission denied'], 403);
                }
                
                sendResponse(['task' => $task]);
            } else {
                // Get tasks based on user role
                if ($isAdmin) {
                    $tasks = $taskManager->getAll();
                } else {
                    $tasks = $taskManager->getByAssignedUser($userId);
                }
                sendResponse(['tasks' => $tasks]);
            }
            break;
            
        case 'POST':
            // Create new task (admin only)
            checkAdmin($userManager);
            $data = getJsonInput();
            
            if (!isset($data['title']) || !isset($data['assigned_to'])) {
                sendResponse(['error' => 'Title and assigned_to required'], 400);
            }
            
            // Check if assigned user is not an admin
            $assignedUser = $userManager->findById($data['assigned_to']);
            if (!$assignedUser) {
                sendResponse(['error' => 'Assigned user not found'], 400);
            }
            
            if ($assignedUser['role'] === 'admin') {
                sendResponse(['error' => 'Cannot assign tasks to administrators'], 400);
            }
            
            try {
                $task = $taskManager->create(
                    $data['title'],
                    $data['description'] ?? '',
                    $data['assigned_to'],
                    $_SESSION['user_id'],
                    $data['deadline'] ?? null,
                    $data['priority'] ?? 'Medium'
                );
                
                // Send email notification
                $assignedUser = $userManager->findById($data['assigned_to']);
                $adminUser = $userManager->findById($_SESSION['user_id']);
                
                if ($assignedUser && $adminUser) {
                    $emailService->sendTaskAssignmentNotification(
                        $assignedUser['email'],
                        $assignedUser['username'],
                        $task['title'],
                        $task['description'],
                        $task['deadline'],
                        $adminUser['username']
                    );
                }
                
                sendResponse(['message' => 'Task created successfully', 'task' => $task], 201);
            } catch (Exception $e) {
                sendResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        case 'PUT':
            if (!$resource) {
                sendResponse(['error' => 'Task ID required'], 400);
            }
            
            $userId = checkAuth();
            $data = getJsonInput();
            
            // Check if updating status only (users can update their own task status)
            if (isset($data['status']) && count($data) === 1) {
                $task = $taskManager->findById($resource);
                if (!$task) {
                    sendResponse(['error' => 'Task not found'], 404);
                }
                
                $isAdmin = $userManager->isAdmin($userId);
                if (!$isAdmin && $task['assigned_to'] != $userId) {
                    sendResponse(['error' => 'Permission denied'], 403);
                }
                
                try {
                    $updatedTask = $taskManager->updateStatus($resource, $data['status'], $userId);
                    sendResponse(['message' => 'Task status updated successfully', 'task' => $updatedTask]);
                } catch (Exception $e) {
                    sendResponse(['error' => $e->getMessage()], 400);
                }
            } else {
                // Full task update (admin only)
                checkAdmin($userManager);
                
                // Check if assigned user is not an admin (if assigned_to is being updated)
                if (isset($data['assigned_to'])) {
                    $assignedUser = $userManager->findById($data['assigned_to']);
                    if (!$assignedUser) {
                        sendResponse(['error' => 'Assigned user not found'], 400);
                    }
                    
                    if ($assignedUser['role'] === 'admin') {
                        sendResponse(['error' => 'Cannot assign tasks to administrators'], 400);
                    }
                }
                
                try {
                    $task = $taskManager->update($resource, $data, $userId);
                    sendResponse(['message' => 'Task updated successfully', 'task' => $task]);
                } catch (Exception $e) {
                    sendResponse(['error' => $e->getMessage()], 400);
                }
            }
            break;
            
        case 'DELETE':
            if (!$resource) {
                sendResponse(['error' => 'Task ID required'], 400);
            }
            
            checkAdmin($userManager);
            
            try {
                $taskManager->delete($resource);
                sendResponse(['message' => 'Task deleted successfully']);
            } catch (Exception $e) {
                sendResponse(['error' => $e->getMessage()], 400);
            }
            break;
            
        default:
            sendResponse(['error' => 'Method not allowed'], 405);
    }
}

/**
 * Handle dashboard endpoints
 */
function handleDashboard($method, $userManager, $taskManager) {
    if ($method !== 'GET') {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
    
    $userId = checkAuth();
    $isAdmin = $userManager->isAdmin($userId);
    
    if ($isAdmin) {
        // Admin dashboard
        $userStats = $userManager->getStats();
        $taskStats = $taskManager->getStats();
        $overdueTasks = $taskManager->getOverdueTasks();
        $upcomingTasks = $taskManager->getUpcomingTasks();
        
        sendResponse([
            'user_stats' => $userStats,
            'task_stats' => $taskStats,
            'overdue_tasks' => $overdueTasks,
            'upcoming_tasks' => $upcomingTasks
        ]);
    } else {
        // User dashboard
        $userTasks = $taskManager->getByAssignedUser($userId);
        $taskStats = [
            'total_tasks' => count($userTasks),
            'pending_count' => count(array_filter($userTasks, fn($t) => $t['status'] === 'Pending')),
            'in_progress_count' => count(array_filter($userTasks, fn($t) => $t['status'] === 'In Progress')),
            'completed_count' => count(array_filter($userTasks, fn($t) => $t['status'] === 'Completed')),
            'overdue_count' => count(array_filter($userTasks, fn($t) => $t['deadline'] && $t['deadline'] < date('Y-m-d') && $t['status'] !== 'Completed'))
        ];
        
        sendResponse([
            'task_stats' => $taskStats,
            'recent_tasks' => array_slice($userTasks, 0, 5)
        ]);
    }
}