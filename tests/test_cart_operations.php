<?php
session_start();

// Simulate a logged-in user for testing
$_SESSION['user_id'] = 1; // Use test user ID

echo "<h1>Cart Add/Remove Test</h1>";

try {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/includes/Cart.php';
    
    $db = Database::getInstance();
    $cart = new Cart($db);
    $user_id = $_SESSION['user_id'];
    
    echo "<h2>Initial Cart State</h2>";
    $initialCount = $cart->getCartCount($user_id);
    $initialTotal = $cart->getCartTotal($user_id);
    echo "<p>Cart Count: $initialCount</p>";
    echo "<p>Cart Total: $" . number_format($initialTotal, 2) . "</p>";
    
    // Test adding an item (product ID 1, quantity 2)
    echo "<h2>Adding Item to Cart</h2>";
    $addResult = $cart->addToCart($user_id, 1, 2);
    echo "<p>Add Result: " . ($addResult ? "Success" : "Failed") . "</p>";
    
    // Check cart count after adding
    $newCount = $cart->getCartCount($user_id);
    $newTotal = $cart->getCartTotal($user_id);
    echo "<p>New Cart Count: $newCount</p>";
    echo "<p>New Cart Total: $" . number_format($newTotal, 2) . "</p>";
    
    // Get cart items
    echo "<h2>Cart Items</h2>";
    $items = $cart->getCartItems($user_id);
    if (empty($items)) {
        echo "<p>No items in cart</p>";
    } else {
        foreach ($items as $item) {
            echo "<p>Product ID: {$item['product_id']}, Quantity: {$item['quantity']}, Price: $" . number_format($item['price'], 2) . "</p>";
        }
    }
    
    // Test updating quantity
    if (!empty($items)) {
        $firstItem = $items[0];
        echo "<h2>Updating Quantity</h2>";
        $updateResult = $cart->updateQuantity($user_id, $firstItem['product_id'], 3);
        echo "<p>Update Result: " . ($updateResult ? "Success" : "Failed") . "</p>";
        
        $updatedCount = $cart->getCartCount($user_id);
        echo "<p>Updated Cart Count: $updatedCount</p>";
    }
    
    // Test removing an item
    if (!empty($items)) {
        $firstItem = $items[0];
        echo "<h2>Removing Item</h2>";
        $removeResult = $cart->removeFromCart($user_id, $firstItem['product_id']);
        echo "<p>Remove Result: " . ($removeResult ? "Success" : "Failed") . "</p>";
        
        $finalCount = $cart->getCartCount($user_id);
        echo "<p>Final Cart Count: $finalCount</p>";
    }
    
    echo "<h2>Test Complete!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
