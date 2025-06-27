<?php
/**
 * Restore API for Art2Cart Admin Panel
 * Handles database restore operations
 */

require_once '../../config/db.php';
require_once '../../includes/User.php';
require_once '../../includes/BackupManager.php';

// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is authenticated and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Initialize BackupManager
try {
    $backupManager = new BackupManager();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to initialize backup manager: ' . $e->getMessage()]);
    exit;
}

// Handle restore operation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleRestore($backupManager);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug'])) {
    // Simple debug endpoint
    echo json_encode([
        'success' => true,
        'message' => 'Debug endpoint active',
        'server' => [
            'php_version' => PHP_VERSION,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time')
        ]
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

/**
 * Handle database restore
 */
function handleRestore($backupManager) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
          // Handle verification request
        if (isset($input['action']) && $input['action'] === 'verify_restore') {
            $verification = verifyDatabaseIntegrity();
            echo json_encode([
                'success' => true,
                'verification' => $verification
            ]);
            return;
        }
        
        // Handle debug file validation request
        if (isset($input['action']) && $input['action'] === 'debug_file') {
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['error' => 'No file uploaded']);
                return;
            }
            
            $uploadedFile = $_FILES['backup_file'];
            $debugInfo = debugBackupFile($uploadedFile);
            echo json_encode([
                'success' => true,
                'debug' => $debugInfo
            ]);
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred');
        }
        
        $uploadedFile = $_FILES['backup_file'];
          // Validate file
        $validationResult = validateBackupFile($uploadedFile);
        if (!$validationResult['valid']) {
            // Log validation failure for debugging
            error_log("Backup file validation failed: " . $validationResult['error']);
            error_log("File name: " . $uploadedFile['name']);
            error_log("File size: " . $uploadedFile['size']);
            error_log("File type: " . $uploadedFile['type']);
            
            // Read first 500 chars for debugging
            $debugContent = file_get_contents($uploadedFile['tmp_name'], false, null, 0, 500);
            error_log("File content sample: " . substr($debugContent, 0, 200));
            
            throw new Exception($validationResult['error']);
        }
        
        // Get pre-restore database state
        $preRestoreState = verifyDatabaseIntegrity();
        
        // Create safety backup before restore
        $safetyBackup = $backupManager->createBackup('safety_backup_before_restore_' . date('Y-m-d_H-i-s') . '.sql');
        
        // Move uploaded file to temporary location
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/' . 'restore_' . uniqid() . '.sql';
        
        if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFile)) {
            throw new Exception('Failed to process uploaded file');
        }
        
        try {
            // Perform restore
            $result = restoreFromFile($tempFile);
            
            if ($result['success']) {
                // Verify database after restore
                $postRestoreState = verifyDatabaseIntegrity();
                
                // Log the restore activity
                logRestoreActivity('database_restored', $uploadedFile['name'], $uploadedFile['size'], $safetyBackup['filename'], true, $preRestoreState, $postRestoreState);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Database restored successfully',
                    'safety_backup' => $safetyBackup['filename'],
                    'restored_from' => $uploadedFile['name'],
                    'verification' => $postRestoreState,
                    'pre_restore_state' => $preRestoreState,
                    'restore_timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception($result['error']);
            }
            
        } finally {
            // Clean up temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
        
    } catch (Exception $e) {
        // Log failed restore attempt
        if (isset($uploadedFile)) {
            logRestoreActivity('restore_failed', $uploadedFile['name'], $uploadedFile['size'], '', false, null, null, $e->getMessage());
        }
        
        http_response_code(500);
        echo json_encode([
            'error' => 'Restore failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Validate uploaded backup file
 */
function validateBackupFile($file) {
    // Check file size (max 100MB)
    $maxSize = 100 * 1024 * 1024; // 100MB
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large. Maximum size is 100MB'];
    }
    
    // Check file extension
    $filename = $file['name'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension !== 'sql') {
        return ['valid' => false, 'error' => 'Invalid file type. Only .sql files are allowed'];
    }
    
    // Check file content (more flexible validation)
    $tempContent = file_get_contents($file['tmp_name'], false, null, 0, 5000); // Read first 5KB
    
    // Look for common SQL backup patterns (case insensitive)
    $sqlPatterns = [
        'CREATE TABLE',
        'INSERT INTO', 
        'DROP TABLE',
        'CREATE DATABASE',
        'USE ',
        'SET ',
        'LOCK TABLES',
        'UNLOCK TABLES',
        '-- MySQL dump',
        '-- phpMyAdmin',
        'mysqldump'
    ];
    
    $hasValidPattern = false;
    foreach ($sqlPatterns as $pattern) {
        if (stripos($tempContent, $pattern) !== false) {
            $hasValidPattern = true;
            break;
        }
    }
    
    if (!$hasValidPattern) {
        // Additional check for basic SQL syntax
        if (preg_match('/\b(CREATE|INSERT|UPDATE|DELETE|SELECT|DROP|ALTER)\b/i', $tempContent)) {
            $hasValidPattern = true;
        }
    }
    
    if (!$hasValidPattern) {
        return ['valid' => false, 'error' => 'File does not appear to be a valid SQL backup. Please ensure the file contains valid SQL statements.'];
    }
    
    return ['valid' => true];
}

/**
 * Restore database from file
 */
function restoreFromFile($filepath) {
    try {
        if (!function_exists('exec')) {
            throw new Exception('exec() function is not available');
        }
        
        // Find mysql executable
        $mysqlPath = findMysqlExecutable();
        if (!$mysqlPath) {
            throw new Exception('MySQL executable not found');
        }
        
        // Build mysql restore command
        $baseCommand = ($mysqlPath === 'mysql') ? 'mysql' : '"' . $mysqlPath . '"';
        $command = $baseCommand . ' --host=localhost --user=root art2cart < "' . $filepath . '" 2>&1';
        
        // Execute restore
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return [
                'success' => true,
                'message' => 'Database restored successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Restore failed: ' . implode('\n', $output)
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Find MySQL executable
 */
function findMysqlExecutable() {
    // Common paths for mysql on Windows
    $commonPaths = [
        'mysql', // Check if it's in PATH first
    ];
    
    // Add WAMP paths dynamically
    $wampMysqlDir = 'C:\\wamp64\\bin\\mysql';
    if (is_dir($wampMysqlDir)) {
        $subdirs = glob($wampMysqlDir . '\\mysql*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $commonPaths[] = $subdir . '\\bin\\mysql.exe';
        }
    }
    
    // Add other common paths
    $commonPaths = array_merge($commonPaths, [
        'C:\\xampp\\mysql\\bin\\mysql.exe',
        'C:\\mysql\\bin\\mysql.exe'
    ]);
    
    foreach ($commonPaths as $path) {
        if ($path === 'mysql') {
            // Test if mysql is in PATH
            $output = [];
            $returnCode = 0;
            exec('mysql --version 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                return 'mysql';
            }
        } else {
            // Test if file exists at specific path
            if (file_exists($path)) {
                return $path;
            }
        }
    }
    
    return false;
}

/**
 * Verify database integrity and get statistics
 */
function verifyDatabaseIntegrity() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $verification = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tables_count' => 0,
            'users_count' => 0,
            'products_count' => 0,
            'orders_count' => 0,
            'categories_count' => 0,
            'ratings_count' => 0,
            'backup_logs_count' => 0,
            'table_list' => [],
            'database_size' => 0
        ];
        
        // Get table count and list
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $verification['tables_count'] = count($tables);
        $verification['table_list'] = $tables;
        
        // Get database size
        $stmt = $db->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size_mb 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $size = $stmt->fetch(PDO::FETCH_ASSOC);
        $verification['database_size'] = $size['db_size_mb'] . ' MB';
        
        // Count records in key tables (with error handling for missing tables)
        $tableCounts = [
            'users' => 'users_count',
            'products' => 'products_count', 
            'orders' => 'orders_count',
            'categories' => 'categories_count',
            'ratings' => 'ratings_count',
            'backup_logs' => 'backup_logs_count'
        ];
        
        foreach ($tableCounts as $table => $countKey) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM `{$table}`");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $verification[$countKey] = (int)$result['count'];
            } catch (Exception $e) {
                // Table might not exist, set to 0
                $verification[$countKey] = 0;
            }
        }
        
        return $verification;
        
    } catch (Exception $e) {
        return [
            'error' => 'Failed to verify database: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Log restore activities with enhanced details
 */
function logRestoreActivity($action, $filename, $size, $safetyBackup, $success = true, $preState = null, $postState = null, $errorMessage = '') {
    try {
        // Also log to file system
        logToFile($action, $filename, $success, $errorMessage, $preState, $postState);
        
        $db = Database::getInstance()->getConnection();
        
        // Check if backup_logs table exists, create if not
        $stmt = $db->query("SHOW TABLES LIKE 'backup_logs'");
        if ($stmt->rowCount() == 0) {
            $db->exec("
                CREATE TABLE backup_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    filename VARCHAR(255),
                    file_size BIGINT,
                    safety_backup VARCHAR(255),
                    success BOOLEAN DEFAULT TRUE,
                    error_message TEXT,
                    pre_restore_state JSON,
                    post_restore_state JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at),
                    INDEX idx_action (action)
                )
            ");
        }
        
        $stmt = $db->prepare("
            INSERT INTO backup_logs (user_id, action, filename, file_size, safety_backup, success, error_message, pre_restore_state, post_restore_state) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'], 
            $action, 
            $filename, 
            $size, 
            $safetyBackup,
            $success,
            $errorMessage,
            $preState ? json_encode($preState) : null,
            $postState ? json_encode($postState) : null
        ]);
        
    } catch (Exception $e) {
        error_log("Failed to log restore activity: " . $e->getMessage());
        // Ensure file logging still works even if DB logging fails
        logToFile($action, $filename, $success, $errorMessage, $preState, $postState);
    }
}

/**
 * Log restore activities to file system
 */
function logToFile($action, $filename, $success, $errorMessage, $preState, $postState) {
    try {
        $logDir = '../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/restore_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $status = $success ? 'SUCCESS' : 'FAILED';
        $userId = $_SESSION['user_id'] ?? 'Unknown';
        
        $logEntry = "[$timestamp] USER:$userId ACTION:$action STATUS:$status FILE:$filename";
        
        if (!$success && $errorMessage) {
            $logEntry .= " ERROR:$errorMessage";
        }
        
        if ($preState && $postState) {
            $logEntry .= " TABLES_BEFORE:" . ($preState['tables_count'] ?? 0);
            $logEntry .= " TABLES_AFTER:" . ($postState['tables_count'] ?? 0);
            $logEntry .= " RECORDS_BEFORE:" . (($preState['users_count'] ?? 0) + ($preState['products_count'] ?? 0) + ($preState['orders_count'] ?? 0));
            $logEntry .= " RECORDS_AFTER:" . (($postState['users_count'] ?? 0) + ($postState['products_count'] ?? 0) + ($postState['orders_count'] ?? 0));
        }
        
        $logEntry .= "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
    } catch (Exception $e) {
        error_log("Failed to log to file: " . $e->getMessage());
    }
}

/**
 * Debug backup file for troubleshooting
 */
function debugBackupFile($file) {
    $debug = [
        'filename' => $file['name'],
        'size' => $file['size'],
        'type' => $file['type'],
        'error' => $file['error'],
        'extension' => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
    ];
    
    // Read file content sample
    if ($file['error'] === UPLOAD_ERR_OK && file_exists($file['tmp_name'])) {
        $content = file_get_contents($file['tmp_name'], false, null, 0, 2000);
        $debug['content_length'] = strlen($content);
        $debug['content_sample'] = substr($content, 0, 500);
        
        // Check for SQL patterns
        $sqlPatterns = [
            'CREATE TABLE',
            'INSERT INTO', 
            'DROP TABLE',
            'CREATE DATABASE',
            'USE ',
            'SET ',
            'LOCK TABLES',
            'UNLOCK TABLES',
            '-- MySQL dump',
            '-- phpMyAdmin',
            'mysqldump'
        ];
        
        $debug['found_patterns'] = [];
        foreach ($sqlPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $debug['found_patterns'][] = $pattern;
            }
        }
        
        // Check for basic SQL keywords
        if (preg_match_all('/\b(CREATE|INSERT|UPDATE|DELETE|SELECT|DROP|ALTER|USE|SET)\b/i', $content, $matches)) {
            $debug['sql_keywords'] = array_unique($matches[1]);
        }
        
        // File encoding check
        $debug['encoding'] = mb_detect_encoding($content, ['UTF-8', 'ASCII', 'ISO-8859-1'], true);
        
        // Line count sample
        $lines = explode("\n", substr($content, 0, 1000));
        $debug['sample_lines'] = count($lines);
        $debug['first_lines'] = array_slice($lines, 0, 5);
    }
    
    return $debug;
}
?>
