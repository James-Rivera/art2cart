<?php
header('Content-Type: application/json');
session_start();

require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['product_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        exit;
    }
    
    $productId = (int)$input['product_id'];
    
    $db = Database::getInstance()->getConnection();
    
    // Get the order ID for this user's purchase of this product
    $stmt = $db->prepare("
        SELECT o.id as order_id
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
        ORDER BY o.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $productId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'order_id' => $result['order_id']
        ]);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'You have not purchased this product']);
    }
    
} catch (Exception $e) {
    error_log("Download info API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
