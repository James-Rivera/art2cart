<?php
// Test cart API endpoints
session_start();
require_once '../config/db.php';
require_once '../includes/Cart.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $cart = new Cart($db);
    
    // Test if we're getting a POST request for adding to cart
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
            exit;
        }
        
        $action = $input['action'] ?? '';
        
        if ($action === 'add') {
            $product_id = $input['product_id'] ?? 0;
            $quantity = $input['quantity'] ?? 1;
            
            if ($product_id > 0) {
                $result = $cart->addToCart($_SESSION['user_id'], $product_id, $quantity);
                if ($result) {
                    $cartCount = $cart->getCartCount($_SESSION['user_id']);
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Item added to cart successfully!',
                        'cart_count' => $cartCount
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            }
        } else if ($action === 'get_count') {
            if (isset($_SESSION['user_id'])) {
                $cartCount = $cart->getCartCount($_SESSION['user_id']);
                echo json_encode(['success' => true, 'cart_count' => $cartCount]);
            } else {
                echo json_encode(['success' => true, 'cart_count' => 0]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        // GET request - return current cart status
        if (isset($_SESSION['user_id'])) {
            $cartCount = $cart->getCartCount($_SESSION['user_id']);
            echo json_encode(['success' => true, 'cart_count' => $cartCount]);
        } else {
            echo json_encode(['success' => true, 'cart_count' => 0]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
