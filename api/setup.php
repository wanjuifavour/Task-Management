<?php
/**
 * Setup Script
 * Helps with initial configuration and testing
 */

echo "=== Task Management System Setup ===\n\n";

// Check PHP version
echo "1. Checking PHP version...\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "✓ PHP " . PHP_VERSION . " is compatible\n";
} else {
    echo "✗ PHP " . PHP_VERSION . " is too old. Please upgrade to 7.4 or higher\n";
    exit(1);
}

// Check required extensions
echo "\n2. Checking required extensions...\n";
$required_extensions = ['pdo', 'pdo_pgsql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ $ext extension is loaded\n";
    } else {
        echo "✗ $ext extension is missing\n";
        exit(1);
    }
}

// Check if composer is available
echo "\n3. Checking Composer...\n";
if (file_exists('vendor/autoload.php')) {
    echo "✓ Composer dependencies are installed\n";
} else {
    echo "! Composer dependencies not found. Run 'composer install'\n";
}

// Check environment file
echo "\n4. Checking environment configuration...\n";
if (file_exists('.env')) {
    echo "✓ .env file exists\n";
} else {
    echo "! .env file not found. Please copy config.env.example to .env and configure it\n";
}

// Test database connection
echo "\n5. Testing database connection...\n";
try {
    require_once 'config/loader.php';
    require_once 'classes/Database.php';
    
    $db = new Database();
    echo "✓ Database connection successful\n";
    
    // Test basic queries
    $result = $db->fetch("SELECT COUNT(*) as count FROM users");
    echo "✓ Database queries working (found " . $result['count'] . " users)\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in .env file\n";
}

// Test email configuration
echo "\n6. Testing email configuration...\n";
try {
    require_once 'classes/EmailService.php';
    $emailService = new EmailService();
    echo "✓ Email service loaded successfully\n";
    
    if (!empty($_ENV['SMTP_USERNAME']) && !empty($_ENV['SMTP_PASSWORD'])) {
        echo "✓ SMTP configuration found\n";
    } else {
        echo "! SMTP configuration not found. Email notifications will use PHP mail()\n";
    }
    
} catch (Exception $e) {
    echo "✗ Email service error: " . $e->getMessage() . "\n";
}

echo "\n=== Setup Complete ===\n";
echo "\nNext steps:\n";
echo "1. Configure your .env file with database and email settings\n";
echo "2. Run 'composer install' to install dependencies\n";
echo "3. Import the database schema: psql -U postgres -d task_management -f database/task-management.sql\n";
echo "4. Test the API: curl http://localhost/api/test.php\n";
echo "5. Start your web server and access the application\n";
echo "\nDefault admin login: admin@taskmanager.com / password\n"; 