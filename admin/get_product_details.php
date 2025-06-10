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

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare('
        SELECT 
            p.*,
            u.username as seller_name,
            c.name as category_name
        FROM products p
        JOIN users u ON p.seller_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ');
    
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    echo json_encode($product);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log($e->getMessage());
}
