<?php
require_once '../config/db.php';
require_once '../includes/User.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['applicationId']) || !isset($data['action']) || !in_array($data['action'], ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Start transaction
    $db->beginTransaction();
      // Get application details
    $stmt = $db->prepare("SELECT user_id, portfolio_url, government_id_path FROM seller_applications WHERE id = ? AND status = 'pending'");
    $stmt->execute([$data['applicationId']]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        throw new Exception('Application not found or already processed');
    }

    // Verify required fields are present
    if (empty($application['portfolio_url']) || empty($application['government_id_path'])) {
        throw new Exception('Application missing required fields (portfolio or government ID)');
    }
      // Update application status with rejection reason if provided
    if ($data['action'] === 'reject' && isset($data['reason'])) {
        $stmt = $db->prepare("UPDATE seller_applications SET status = ?, review_date = NOW(), rejection_reason = ? WHERE id = ?");
        $stmt->execute(['rejected', $data['reason'], $data['applicationId']]);
    } else {
        $stmt = $db->prepare("UPDATE seller_applications SET status = ?, review_date = NOW() WHERE id = ?");
        $status = $data['action'] === 'approve' ? 'approved' : 'rejected';
        $stmt->execute([$status, $data['applicationId']]);
    }
      if ($data['action'] === 'approve') {
        // Get the seller role ID
        $stmt = $db->prepare("SELECT id FROM roles WHERE name = 'seller'");
        $stmt->execute();
        $sellerRole = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sellerRole) {
            throw new Exception('Seller role not found');
        }
        
        // Add seller role to user
        $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$application['user_id'], $sellerRole['id']]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Application ' . ($data['action'] === 'approve' ? 'approved' : 'rejected') . ' successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
