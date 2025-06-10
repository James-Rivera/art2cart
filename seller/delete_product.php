<?php
require_once '../config/db.php';
require_once '../includes/User.php';

header('Content-Type: application/json');

session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in']);
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('seller')) {
    http_response_code(403);
    echo json_encode(['error' => 'You must be a seller to delete products']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get and validate input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['productId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID is required']);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    
    // Verify product ownership
    $stmt = $db->prepare("SELECT id, image_path, file_path FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$data['productId'], $_SESSION['user_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not own this product']);
        exit;
    }

    // Start transaction
    $db->beginTransaction();

    // Delete product from database
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $success = $stmt->execute([$data['productId']]);

    if ($success) {
        // Delete associated files
        if (!empty($product['image_path'])) {
            @unlink('../' . $product['image_path']);
        }
        if (!empty($product['file_path'])) {
            @unlink('../' . $product['file_path']);
        }
        
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Product successfully deleted'
        ]);
    } else {
        throw new Exception('Failed to delete product');
    }
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log($e->getMessage());
}