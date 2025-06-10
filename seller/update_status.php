<?php
// Prevent any unwanted output
error_reporting(0);
ini_set('display_errors', 0);

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
    echo json_encode(['error' => 'You must be a seller to update products']);
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
    
    if (!isset($data['productId']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $productId = filter_var($data['productId'], FILTER_VALIDATE_INT);
    $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

    if (!$productId || !in_array($status, ['active', 'inactive'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    
    // Verify product ownership
    $stmt = $db->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not own this product']);
        exit;
    }    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update product status
        $stmt = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $productId]);

        if ($success) {
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Product status updated successfully',
                'newStatus' => $status
            ]);
        } else {
            throw new Exception('Failed to update product status');
        }
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error updating product status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update product status. Please try again.']);
}
