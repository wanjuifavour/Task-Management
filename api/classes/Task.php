<?php
/**
 * Task Management Class
 * Handles task operations and status management
 */
class Task {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    /**
     * Create a new task
     */
    public function create($title, $description, $assignedTo, $assignedBy, $deadline = null, $priority = 'Medium') {
        $sql = "INSERT INTO tasks (title, description, assigned_to, assigned_by, deadline, priority) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $taskId = $this->db->insert($sql, [
            $title, $description, $assignedTo, $assignedBy, $deadline, $priority
        ]);
        
        // Log task creation
        $this->logStatusChange($taskId, $assignedBy, null, 'Pending');
        
        return $this->findById($taskId);
    }
    
    /**
     * Find task by ID
     */
    public function findById($id) {
        $sql = "SELECT t.*, 
                       u1.username as assigned_to_username,
                       u1.email as assigned_to_email,
                       u2.username as assigned_by_username
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                WHERE t.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }
    
    /**
     * Get all tasks
     */
    public function getAll() {
        $sql = "SELECT t.*, 
                       u1.username as assigned_to_username,
                       u1.email as assigned_to_email,
                       u2.username as assigned_by_username
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                ORDER BY t.created_at DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get tasks assigned to specific user
     */
    public function getByAssignedUser($userId) {
        $sql = "SELECT t.*, 
                       u1.username as assigned_to_username,
                       u1.email as assigned_to_email,
                       u2.username as assigned_by_username
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                WHERE t.assigned_to = ?
                ORDER BY t.deadline ASC, t.created_at DESC";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    /**
     * Get tasks by status
     */
    public function getByStatus($status) {
        $sql = "SELECT t.*, 
                       u1.username as assigned_to_username,
                       u1.email as assigned_to_email,
                       u2.username as assigned_by_username
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                WHERE t.status = ?
                ORDER BY t.deadline ASC, t.created_at DESC";
        
        return $this->db->fetchAll($sql, [$status]);
    }
    
    /**
     * Update task
     */
    public function update($id, $data, $userId) {
        $allowedFields = ['title', 'description', 'assigned_to', 'deadline', 'priority'];
        $updateFields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception("No valid fields to update");
        }
        
        $params[] = $id;
        $sql = "UPDATE tasks SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $affected = $this->db->execute($sql, $params);
        
        if ($affected === 0) {
            throw new Exception("Task not found or no changes made");
        }
        
        return $this->findById($id);
    }
    
    /**
     * Update task status
     */
    public function updateStatus($id, $newStatus, $userId) {
        $validStatuses = ['Pending', 'In Progress', 'Completed'];
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception("Invalid status");
        }
        
        // Get current status
        $currentTask = $this->findById($id);
        if (!$currentTask) {
            throw new Exception("Task not found");
        }
        
        $oldStatus = $currentTask['status'];
        
        // Update status
        $sql = "UPDATE tasks SET status = ? WHERE id = ?";
        $affected = $this->db->execute($sql, [$newStatus, $id]);
        
        if ($affected === 0) {
            throw new Exception("Failed to update task status");
        }
        
        // Log status change
        $this->logStatusChange($id, $userId, $oldStatus, $newStatus);
        
        return $this->findById($id);
    }
    
    /**
     * Delete task
     */
    public function delete($id) {
        if (!$this->findById($id)) {
            throw new Exception("Task not found");
        }
        
        $sql = "DELETE FROM tasks WHERE id = ?";
        $affected = $this->db->execute($sql, [$id]);
        
        return $affected > 0;
    }
    
    /**
     * Get task statistics
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN deadline < CURRENT_DATE AND status != 'Completed' THEN 1 ELSE 0 END) as overdue_count
                FROM tasks";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Get overdue tasks
     */
    public function getOverdueTasks() {
        $sql = "SELECT t.*, 
                       u1.username as assigned_to_username,
                       u1.email as assigned_to_email,
                       u2.username as assigned_by_username
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                WHERE t.deadline < CURRENT_DATE AND t.status != 'Completed'
                ORDER BY t.deadline ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get upcoming tasks (due within next 7 days)
     */
    public function getUpcomingTasks($days = 7) {
        $sql = "SELECT t.*, 
                       u1.username as assigned_to_username,
                       u1.email as assigned_to_email,
                       u2.username as assigned_by_username
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                WHERE t.deadline BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '$days days'
                AND t.status != 'Completed'
                ORDER BY t.deadline ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Log task status change
     */
    private function logStatusChange($taskId, $userId, $oldStatus, $newStatus) {
        $sql = "INSERT INTO task_history (task_id, user_id, old_status, new_status) 
                VALUES (?, ?, ?, ?)";
        
        $this->db->execute($sql, [$taskId, $userId, $oldStatus, $newStatus]);
    }
    
    /**
     * Get task history
     */
    public function getHistory($taskId) {
        $sql = "SELECT th.*, u.username
                FROM task_history th
                LEFT JOIN users u ON th.user_id = u.id
                WHERE th.task_id = ?
                ORDER BY th.changed_at DESC";
        
        return $this->db->fetchAll($sql, [$taskId]);
    }
    
    /**
     * Search tasks
     */
    public function search($query, $userId = null, $isAdmin = false) {
        $sql = "SELECT t.*, 
                       u1.username as assigned_to_username,
                       u1.email as assigned_to_email,
                       u2.username as assigned_by_username
                FROM tasks t
                LEFT JOIN users u1 ON t.assigned_to = u1.id
                LEFT JOIN users u2 ON t.assigned_by = u2.id
                WHERE (t.title LIKE ? OR t.description LIKE ?)";
        
        $params = ["%$query%", "%$query%"];
        
        // If not admin, only show tasks assigned to the user
        if (!$isAdmin && $userId) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
}