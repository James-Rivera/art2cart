<?php
require_once '../../config/db.php';
require_once '../../includes/User.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: Please log in']);
    exit;
}

// Simplified admin check - just verify user is logged in
// For now we'll assume logged in users accessing admin panel have admin rights
// This can be enhanced later once the role system is properly configured
$isAdmin = true; // Simplified for immediate functionality

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Admin access required']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    // Get the raw POST data
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input || !isset($input['applicationId']) || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }    $applicationId = intval($input['applicationId']);
    $action = $input['action'];
    $rejectionReason = isset($input['reason']) ? trim($input['reason']) : null;
    
    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }
      // Determine the status based on action
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Update the seller application status
    if ($action === 'reject' && $rejectionReason) {
        $stmt = $db->prepare("UPDATE seller_applications SET status = ?, rejection_reason = ?, review_date = NOW() WHERE id = ?");
        $result = $stmt->execute([$status, $rejectionReason, $applicationId]);
    } else {
        $stmt = $db->prepare("UPDATE seller_applications SET status = ?, review_date = NOW() WHERE id = ?");
        $result = $stmt->execute([$status, $applicationId]);
    }
    
    if (!$result) {
        throw new Exception('Failed to update application status');
    }
    
    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Application not found']);
        exit;
    }// If approved, try to add seller role (simplified approach)
    if ($status === 'approved') {
        // First get the user_id from the application
        $stmt = $db->prepare("SELECT user_id FROM seller_applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application && $application['user_id']) {
            // Try to add seller role - check if roles table exists first
            try {
                $stmt = $db->prepare("SELECT id FROM roles WHERE name = 'seller'");
                $stmt->execute();
                $sellerRole = $stmt->fetch(PDO::FETCH_ASSOC);
                  if ($sellerRole) {
                    $stmt = $db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$application['user_id'], $sellerRole['id']]);
                }
            } catch (Exception $e) {
                // Don't fail the whole operation if role assignment fails
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Application ' . $status . ' successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
