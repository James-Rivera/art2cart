<?php
// Test script to verify cart functionality fix
session_start();

// Simulate a logged-in user for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test with user ID 1
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Cart.php';

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();
$cart = new Cart($db);

echo "<h1>Cart Functionality Test</h1>";
echo "<h2>Testing User ID: " . $user_id . "</h2>";

try {
    // Test cart items retrieval
    echo "<h3>1. Testing getCartItems():</h3>";
    $cartItems = $cart->getCartItems($user_id);
    echo "<p>Cart items count: " . count($cartItems) . "</p>";
    
    if (!empty($cartItems)) {
        echo "<h4>Cart Items:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Quantity</th><th>Seller</th><th>Category</th></tr>";
        foreach ($cartItems as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
            echo "<td>₱" . number_format($item['price'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($item['seller_name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['category_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No items in cart</p>";
    }
    
    // Test cart count
    echo "<h3>2. Testing getCartCount():</h3>";
    $cartCount = $cart->getCartCount($user_id);
    echo "<p>Cart count: " . $cartCount . "</p>";
    
    // Test cart total
    echo "<h3>3. Testing getCartTotal():</h3>";
    $cartTotal = $cart->getCartTotal($user_id);
    echo "<p>Cart total: ₱" . number_format($cartTotal, 2) . "</p>";
    
    // Summary
    echo "<h3>4. Summary:</h3>";
    if (count($cartItems) > 0 && $cartCount > 0 && $cartTotal > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Cart functionality is working correctly!</p>";
        echo "<p>Items are being retrieved and displayed properly.</p>";
    } else if (count($cartItems) == 0 && $cartCount == 0 && $cartTotal == 0) {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ Cart is empty (this might be expected)</p>";
        echo "<p>All functions are working, but there are no items in the cart.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ There's still a mismatch in cart data</p>";
        echo "<p>Items: " . count($cartItems) . ", Count: " . $cartCount . ", Total: " . $cartTotal . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
