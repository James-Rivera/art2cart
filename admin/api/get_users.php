<?php
require_once '../../config/db.php';
require_once '../../includes/User.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Fetch all users with their basic information
    $stmt = $db->prepare("
        SELECT 
            id,
            username,
            email,
            CASE 
                WHEN active = 1 THEN 'active'
                WHEN active = 0 THEN 'inactive'
                ELSE 'active'
            END as status,
            created_at
        FROM users 
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If the active column doesn't exist, fallback to simple query
    if (!$users && $stmt->errorCode() !== '00000') {
        $stmt = $db->prepare("
            SELECT 
                id,
                username,
                email,
                'active' as status,
                created_at
            FROM users 
            ORDER BY created_at DESC
            LIMIT 100
        ");
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => count($users)
    ]);
    
} catch (Exception $e) {
    error_log("Get users error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch users data: ' . $e->getMessage()
    ]);
}
?>
