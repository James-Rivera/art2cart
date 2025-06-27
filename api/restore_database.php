<?php
/**
 * Restore Database API Endpoint
 * Handles file upload and database restore with comprehensive verification
 */

header('Content-Type: application/json');
require_once '../includes/BackupManager.php';

// Simple authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if user is admin
require_once '../includes/User.php';
$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action !== 'restore') {
        throw new Exception('Invalid action');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No backup file uploaded or upload error');
    }
    
    $uploadedFile = $_FILES['backup_file'];
    
    // Validate file type
    if (!str_ends_with($uploadedFile['name'], '.sql')) {
        throw new Exception('Only SQL files are allowed');
    }
    
    // Validate file size (max 100MB)
    if ($uploadedFile['size'] > 100 * 1024 * 1024) {
        throw new Exception('File too large (max 100MB)');
    }
    
    // Create backup manager
    $backupManager = new BackupManager();
    
    // Move uploaded file to backup directory
    $backupDir = $backupManager->getBackupDir();
    $filename = 'uploaded_' . date('Y-m-d_H-i-s') . '_' . basename($uploadedFile['name']);
    $targetPath = $backupDir . '/' . $filename;
    
    if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    try {
        // Perform restore with verification
        $result = $backupManager->restoreBackup($filename);
        
        // Clean up uploaded file after successful restore
        unlink($targetPath);
        
        echo json_encode([
            'success' => true,
            'message' => 'Database restored successfully',
            'data' => $result,
            'timestamp' => date('c')
        ]);
        
    } catch (Exception $restoreError) {
        // Clean up uploaded file on error
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        throw $restoreError;
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
