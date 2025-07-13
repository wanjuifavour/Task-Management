<?php
/**
 * User Management Class
 * Handles user operations and authentication
 */
class User {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    /**
     * Create a new user
     */
    public function create($username, $email, $password, $role = 'user') {
        // Check if user already exists
        if ($this->findByEmail($email) || $this->findByUsername($username)) {
            throw new Exception("User already exists");
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $userId = $this->db->insert($sql, [$username, $email, $hashedPassword, $role]);
        
        return $this->findById($userId);
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            throw new Exception("Invalid credentials");
        }
        
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Invalid credentials");
        }
        
        // Remove password from returned data
        unset($user['password']);
        return $user;
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $user = $this->db->fetch($sql, [$id]);
        
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        return $this->db->fetch($sql, [$username]);
    }
    
    /**
     * Get all users
     */
    public function getAll() {
        $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get users by role
     */
    public function getByRole($role) {
        $sql = "SELECT id, username, email, role, created_at FROM users WHERE role = ? ORDER BY username";
        return $this->db->fetchAll($sql, [$role]);
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        $allowedFields = ['username', 'email', 'role'];
        $updateFields = [];
        $params = [];
        
        // Handle password update separately
        if (isset($data['password']) && !empty($data['password'])) {
            // Check if user is admin - prevent password changes for admins
            $user = $this->findById($id);
            if ($user && $user['role'] === 'admin') {
                throw new Exception("Cannot change password for administrator accounts");
            }
            
            // Update password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $affected = $this->db->execute($sql, [$hashedPassword, $id]);
            
            if ($affected === 0) {
                throw new Exception("User not found");
            }
        }
        
        // Update other fields
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($updateFields)) {
            $params[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $affected = $this->db->execute($sql, $params);
            
            if ($affected === 0) {
                throw new Exception("User not found or no changes made");
            }
        }
        
        return $this->findById($id);
    }
    
    /**
     * Update user password
     */
    public function updatePassword($id, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        $affected = $this->db->execute($sql, [$hashedPassword, $id]);
        
        if ($affected === 0) {
            throw new Exception("User not found");
        }
        
        return true;
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        // Check if user exists
        if (!$this->findById($id)) {
            throw new Exception("User not found");
        }
        
        $sql = "DELETE FROM users WHERE id = ?";
        $affected = $this->db->execute($sql, [$id]);
        
        return $affected > 0;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin($userId) {
        $user = $this->findById($userId);
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Get user statistics
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count
                FROM users";
        
        return $this->db->fetch($sql);
    }
}