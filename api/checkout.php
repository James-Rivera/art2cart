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
    $cartItems = $cart->getCartItems($user_id);
    $cartTotal = $cart->getCartTotal($user_id);
    
    // Check if cart is not empty
    if (empty($cartItems)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Create order
    $order_stmt = $pdo->prepare("
        INSERT INTO orders (user_id, total_amount, status, billing_info, created_at) 
        VALUES (?, ?, 'pending', ?, NOW())
    ");
    
    $billing_info = json_encode([
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'],
        'city' => $_POST['city'],
        'postal_code' => $_POST['postal_code'],
        'country' => $_POST['country'],
        'payment_method' => $_POST['payment_method']
    ]);
    
    $order_stmt->execute([$user_id, $cartTotal, $billing_info]);
    $order_id = $pdo->lastInsertId();
    
    // Create order items
    $order_item_stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, price, quantity, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    foreach ($cartItems as $item) {
        $order_item_stmt->execute([
            $order_id,
            $item['product_id'],
            $item['price'],
            $item['quantity']
        ]);
    }
    
    // Clear cart
    $cart->clearCart($user_id);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollback();
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to place order. Please try again.']);
}
?>
