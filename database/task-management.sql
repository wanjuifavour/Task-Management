-- Task Management System Database Schema
-- Database: task_management
-- PostgreSQL Version

-- Create database (run this separately if needed)
-- CREATE DATABASE task_management;

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) DEFAULT 'user' CHECK (role IN ('admin', 'user')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks table
CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INTEGER NOT NULL,
    assigned_by INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN ('Pending', 'In Progress', 'Completed')),
    priority VARCHAR(10) DEFAULT 'Medium' CHECK (priority IN ('Low', 'Medium', 'High')),
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Task status history (optional - for tracking changes)
CREATE TABLE task_history (
    id SERIAL PRIMARY KEY,
    task_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers for updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tasks_updated_at BEFORE UPDATE ON tasks
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert default admin user
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Default password is 'password' (hashed)

-- Insert sample users for testing
INSERT INTO users (username, email, password, role) VALUES 
('John Doe', 'john@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Jane Doe', 'jane@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Insert sample tasks
INSERT INTO tasks (title, description, assigned_to, assigned_by, status, priority, deadline) VALUES
('Setup Development Environment', 'Configure local development environment with PHP and PostgreSQL', 2, 1, 'Pending', 'High', '2025-07-20'),
('Create User Authentication', 'Implement login and registration functionality', 3, 1, 'In Progress', 'Medium', '2025-07-25'),
('Design Database Schema', 'Create and optimize database tables', 2, 1, 'Completed', 'High', '2025-07-15');