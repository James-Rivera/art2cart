<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Cart.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to place an order']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action !== 'place_order') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Validate required fields
$required_fields = ['first_name', 'last_name', 'email', 'address', 'city', 'postal_code', 'country', 'payment_method'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $cart = new Cart($db);
    
    // Debug: Log user and cart info
    error_log("Checkout attempt by user: $user_id");
    
    $cartItems = $cart->getCartItems($user_id);
    
    // Get selected items from POST data
    $selectedItems = isset($_POST['selected_items']) ? json_decode($_POST['selected_items'], true) : [];
    
    // If no selected items provided, use all cart items (backward compatibility)
    if (empty($selectedItems)) {
        $selectedItems = array_column($cartItems, 'product_id');
    }
    
    // Filter cart items to only include selected ones
    $filteredCartItems = array_filter($cartItems, function($item) use ($selectedItems) {
        return in_array($item['product_id'], $selectedItems);
    });
    
    // Calculate total for selected items only
    $cartTotal = array_sum(array_column($filteredCartItems, 'price'));
    
    // Debug: Log cart contents
    error_log("Cart items count: " . count($filteredCartItems));
    error_log("Selected items: " . implode(', ', $selectedItems));
    error_log("Cart total: $cartTotal");
    
    // Check if cart is not empty
    if (empty($filteredCartItems)) {
        error_log("No selected items for user: $user_id");
        echo json_encode(['success' => false, 'message' => 'No items selected for checkout']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    error_log("Transaction started for order creation");
    
    try {
        // Create order
        $order_stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_amount, status, created_at) 
            VALUES (?, ?, 'pending', NOW())
        ");
        
        if (!$order_stmt->execute([$user_id, $cartTotal])) {
            throw new Exception("Failed to create order: " . implode(', ', $order_stmt->errorInfo()));
        }
        
        $order_id = $pdo->lastInsertId();
        error_log("Order created successfully: ID = $order_id");
          // Create billing address entry
        $billing_stmt = $pdo->prepare("
            INSERT INTO billing_addresses (
                order_id, first_name, last_name, email, phone, 
                address, city, state_province, postal_code, country, payment_method, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $billing_data = [
            $order_id,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'] ?? '',
            $_POST['address'],
            $_POST['city'],
            $_POST['state_province'] ?? null,
            $_POST['postal_code'],
            $_POST['country'],
            $_POST['payment_method']
        ];
        
        error_log("Attempting to create billing address for order: $order_id");
        
        if (!$billing_stmt->execute($billing_data)) {
            throw new Exception("Failed to create billing address: " . implode(', ', $billing_stmt->errorInfo()));
        }
        
        error_log("Billing address created successfully");
          // Create order items
        $item_success_count = 0;
        foreach ($filteredCartItems as $item) {
            $item_stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, price, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if ($item_stmt->execute([$order_id, $item['product_id'], $item['price']])) {
                $item_success_count++;
                error_log("Order item added: Product ID {$item['product_id']}");
            } else {
                error_log("Failed to add order item: Product ID {$item['product_id']} - " . implode(', ', $item_stmt->errorInfo()));
            }
        }
        
        if ($item_success_count != count($filteredCartItems)) {
            throw new Exception("Failed to add all items to order. Added $item_success_count out of " . count($filteredCartItems));
        }

        // Remove only selected items from cart
        foreach ($selectedItems as $productId) {
            $cart->removeFromCart($user_id, $productId);
        }
        
        // Commit transaction
        $pdo->commit();
        error_log("Order transaction committed successfully");
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Order creation failed: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to place order: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    error_log("Checkout error: " . $e->getMessage());
    error_log("Error file: " . $e->getFile() . " line: " . $e->getLine());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to place order. Please try again.',
        'debug_error' => $e->getMessage() // Remove this in production
    ]);
}
?>
