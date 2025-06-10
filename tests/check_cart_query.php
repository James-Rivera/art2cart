<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Cart.php';

// For testing, we'll use a test user ID or the logged in user if available
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Use ID 1 for testing

echo "<h1>Cart Debugging</h1>";
echo "<p>Testing user ID: {$user_id}</p>";

try {
    $db = Database::getInstance();
    echo "<p>✓ Database connection successful</p>";
    
    // Check if cart table exists
    $pdo = $db->getConnection();
    $stmt = $pdo->query("SHOW TABLES LIKE 'cart'");
    $tableExists = $stmt->fetchColumn();
    
    if (!$tableExists) {
        echo "<p style='color:red;'>✗ Cart table does not exist!</p>";
        exit;
    }
    
    echo "<p>✓ Cart table exists</p>";
    
    // Check cart items directly from the database
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $rawItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Raw Cart Items</h2>";
    if (empty($rawItems)) {
        echo "<p style='color:orange;'>No raw items found for user {$user_id} in the cart table</p>";
    } else {
        echo "<pre>" . print_r($rawItems, true) . "</pre>";
    }
    
    // Now check using the Cart class
    $cart = new Cart($db);
    $cartItems = $cart->getCartItems($user_id);
    
    echo "<h2>Cart Items via Cart Class</h2>";
    if (empty($cartItems)) {
        echo "<p style='color:orange;'>No items returned by Cart::getCartItems() for user {$user_id}</p>";
    } else {
        echo "<pre>" . print_r($cartItems, true) . "</pre>";
    }
    
    // Check cart count
    $cartCount = $cart->getCartCount($user_id);
    echo "<h2>Cart Count</h2>";
    echo "<p>Cart count: {$cartCount}</p>";
    
    // Check if there are any JOIN issues in the query
    if (empty($cartItems) && !empty($rawItems)) {
        echo "<h2>JOIN Issue Detection</h2>";
        echo "<p style='color:red;'>Possible JOIN issue - raw items exist but joined query returns none</p>";
        
        // Check each product in cart
        foreach ($rawItems as $item) {
            $product_id = $item['product_id'];
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                echo "<p style='color:red;'>Product ID {$product_id} in cart does not exist in products table!</p>";
            } else {
                echo "<p>Product ID {$product_id} exists. Category ID: {$product['category_id']}, Seller ID: {$product['user_id']}</p>";
                
                // Check category
                $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                $stmt->execute([$product['category_id']]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$category) {
                    echo "<p style='color:red;'>Category ID {$product['category_id']} for product {$product_id} does not exist!</p>";
                }
                
                // Check seller
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$product['user_id']]);
                $seller = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$seller) {
                    echo "<p style='color:red;'>User ID {$product['user_id']} for product {$product_id} does not exist!</p>";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
