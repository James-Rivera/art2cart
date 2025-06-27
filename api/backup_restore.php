<?php
/**
 * API endpoint for backup restore operations and verification
 * Usage: /api/backup_restore.php?action=restore&file=backup.sql
 */

header('Content-Type: application/json');
require_once '../includes/BackupManager.php';

// Simple authentication check (you should implement proper admin authentication)
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$backupManager = new BackupManager();

try {
    switch ($action) {
        case 'restore':
            $filename = $_GET['file'] ?? $_POST['file'] ?? '';
            
            if (empty($filename)) {
                http_response_code(400);
                echo json_encode(['error' => 'Filename is required']);
                exit;
            }
            
            // Perform restore with verification
            $result = $backupManager->restoreBackup($filename);
            
            // Return detailed result
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
                'timestamp' => date('c')
            ]);
            break;
            
        case 'verify_db':
            // Quick database health check
            $conn = Database::getInstance()->getConnection();
            
            // Get table count
            $stmt = $conn->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'art2cart'");
            $tableCount = $stmt->fetch()['count'];
            
            // Test a simple query
            $stmt = $conn->query("SELECT 1 as test");
            $queryTest = $stmt->fetch()['test'] === 1;
            
            // Get database size
            $stmt = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size_mb' FROM information_schema.tables WHERE table_schema='art2cart'");
            $dbSize = $stmt->fetch()['db_size_mb'];
            
            echo json_encode([
                'success' => true,
                'database_health' => [
                    'connection' => true,
                    'table_count' => $tableCount,
                    'query_test' => $queryTest,
                    'database_size_mb' => $dbSize,
                    'timestamp' => date('c')
                ]
            ]);
            break;
            
        case 'list_backups':
            $backups = $backupManager->listBackups();
            echo json_encode([
                'success' => true,
                'backups' => $backups,
                'count' => count($backups)
            ]);
            break;
            
        case 'create_backup':
            $result = $backupManager->createBackup();
            echo json_encode([
                'success' => true,
                'backup' => $result,
                'message' => 'Backup created successfully'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
