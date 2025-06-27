<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Cart.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage cart']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    $cart = new Cart($db);
    
    switch ($action) {        case 'add':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = 1; // Always 1 for digital products
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit;
            }
            
            if ($cart->addToCart($user_id, $product_id, $quantity)) {
                $cart_count = $cart->getCartCount($user_id);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Item added to cart',
                    'cart_count' => $cart_count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
            }
            break;
            
        case 'remove':
            $product_id = (int)($_POST['product_id'] ?? 0);
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit;
            }
            
            if ($cart->removeFromCart($user_id, $product_id)) {
                $cart_count = $cart->getCartCount($user_id);
                $cart_total = $cart->getCartTotal($user_id);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Item removed from cart',
                    'cart_count' => $cart_count,
                    'cart_total' => $cart_total
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
            }
            break;
              case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit;
            }
            
            // For digital products: quantity 0 = remove, any other value = keep
            if ($cart->updateQuantity($user_id, $product_id, $quantity)) {
                $cart_count = $cart->getCartCount($user_id);
                $cart_total = $cart->getCartTotal($user_id);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cart updated',
                    'cart_count' => $cart_count,
                    'cart_total' => $cart_total
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
            }
            break;
            
        case 'get':
            $items = $cart->getCartItems($user_id);
            $total = $cart->getCartTotal($user_id);
            $count = $cart->getCartCount($user_id);
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'count' => $count
            ]);
            break;
            
        case 'clear':
            if ($cart->clearCart($user_id)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cart cleared',
                    'cart_count' => 0,
                    'cart_total' => 0
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Cart API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
