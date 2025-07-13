<?php
/**
 * Database Connection Class
 * Handles database connectivity and operations
 */
class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $connection;
    
    public function __construct() {
        // Load configuration
        $config = include __DIR__ . '/../config/database.php';
        
        $this->host = $config['host'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->database = $config['database'];
        $this->port = $config['port'] ?? 5432;
        
        $this->connect();
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $this->connection = new PDO(
                "pgsql:host={$this->host};port={$this->port};dbname={$this->database};user={$this->username};password={$this->password}",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get single record
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Get multiple records
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update/Delete record and return affected rows
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Start transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->connection = null;
    }
}