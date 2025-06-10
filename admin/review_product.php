<?php
require_once '../config/db.php';
require_once '../includes/User.php';

header('Content-Type: application/json');

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

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['productId']) || !isset($data['action']) || 
    !in_array($data['action'], ['approve', 'reject']) || 
    ($data['action'] === 'reject' && !isset($data['notes']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Start transaction
    $db->beginTransaction();    // Update product status
    $review_status = $data['action'] === 'approve' ? 'approved' : 'rejected';
    $product_status = $data['action'] === 'approve' ? 'active' : 'rejected'; // Change this to 'rejected' instead of 'inactive'
    $stmt = $db->prepare('
        UPDATE products 
        SET review_status = ?, 
            status = ?,
            review_notes = ?, 
            review_date = CURRENT_TIMESTAMP,
            reviewed_by = ?
        WHERE id = ?
    ');
      $notes = $data['action'] === 'approve' ? null : $data['notes'];
    $stmt->execute([$review_status, $product_status, $notes, $_SESSION['user_id'], $data['productId']]);
    
    // Get the updated product details
    $stmt = $db->prepare('
        SELECT p.*, u.username as seller_name, c.name as category_name
        FROM products p
        JOIN users u ON p.seller_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ');
    $stmt->execute([$data['productId']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Send email notification to seller
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Product successfully {$data['action']}d",
        'product' => $product
    ]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log($e->getMessage());
}
