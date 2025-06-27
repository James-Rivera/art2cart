<?php
/**
 * Backup API for Art2Cart Admin Panel
 * Handles backup creation, listing, and deletion
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

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreateBackup($backupManager);
        break;
    
    case 'list':
        handleListBackups($backupManager);
        break;
    
    case 'delete':
        handleDeleteBackup($backupManager);
        break;
    
    case 'download':
        handleDownloadBackup($backupManager);
        break;
    
    case 'info':
        handleDatabaseInfo();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Create a new database backup
 */
function handleCreateBackup($backupManager) {
    try {
        // Create backup
        $result = $backupManager->createBackup();
        
        if ($result['success']) {
            // Log the backup creation
            logBackupActivity('backup_created', $result['filename'], $result['size']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $result['filename'],
                'size' => $result['size'],
                'created' => $result['created'],
                'download_url' => 'admin/api/backup_api.php?action=download&file=' . urlencode($result['filename'])
            ]);
        } else {
            throw new Exception('Backup creation failed');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Backup failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * List all available backups
 */
function handleListBackups($backupManager) {
    try {
        $backups = $backupManager->listBackups();
        
        // Add download URLs and format data
        foreach ($backups as &$backup) {
            $backup['download_url'] = 'admin/api/backup_api.php?action=download&file=' . urlencode($backup['filename']);
            $backup['size_formatted'] = formatBytes($backup['size']);
        }
        
        echo json_encode([
            'success' => true,
            'backups' => $backups
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to list backups: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete a backup file
 */
function handleDeleteBackup($backupManager) {
    $filename = $_POST['filename'] ?? '';
    
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Filename is required']);
        return;
    }
    
    try {
        $backupManager->deleteBackup($filename);
        
        // Log the deletion
        logBackupActivity('backup_deleted', $filename);
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to delete backup: ' . $e->getMessage()
        ]);
    }
}

/**
 * Download a backup file
 */
function handleDownloadBackup($backupManager) {
    $filename = $_GET['file'] ?? '';
    
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Filename is required']);
        return;
    }
    
    try {
        $backupDir = $backupManager->getBackupDir();
        $filepath = $backupDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo json_encode(['error' => 'Backup file not found']);
            return;
        }
        
        // Log the download
        logBackupActivity('backup_downloaded', $filename);
        
        // Set headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Output file
        readfile($filepath);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Download failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get database information
 */
function handleDatabaseInfo() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get MySQL version
        $stmt = $db->query("SELECT VERSION() as version");
        $versionResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $mysqlVersion = $versionResult['version'];
        
        // Get table count
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tableCount = count($tables);
        
        // Get database size
        $stmt = $db->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
            FROM information_schema.tables 
            WHERE table_schema = 'art2cart'
        ");
        $sizeResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $databaseSize = $sizeResult['size_mb'] . ' MB';
        
        echo json_encode([
            'success' => true,
            'info' => [
                'mysql_version' => $mysqlVersion,
                'table_count' => $tableCount,
                'database_size' => $databaseSize,
                'tables' => array_slice($tables, 0, 10) // First 10 tables for preview
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to get database info: ' . $e->getMessage()
        ]);
    }
}

/**
 * Log backup activities
 */
function logBackupActivity($action, $filename, $size = null) {
    try {
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
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at)
                )
            ");
        }
        
        $stmt = $db->prepare("
            INSERT INTO backup_logs (user_id, action, filename, file_size) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $action, $filename, $size]);
        
    } catch (Exception $e) {
        error_log("Failed to log backup activity: " . $e->getMessage());
    }
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
