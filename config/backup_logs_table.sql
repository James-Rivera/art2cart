-- SQL script to create backup_logs table for Art2Cart
-- Run this script in your MySQL database to create the logging table

USE art2cart;

-- Create backup_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    filename VARCHAR(255),
    file_size BIGINT,
    safety_backup VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert a comment explaining the table
INSERT INTO backup_logs (user_id, action, filename, file_size) 
VALUES (1, 'table_created', 'backup_logs_table', 0);

-- Show the table structure
DESCRIBE backup_logs;
