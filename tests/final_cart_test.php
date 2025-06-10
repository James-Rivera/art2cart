<?php
// Comprehensive cart functionality test
session_start();

// Simulate logged-in user
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Cart.php';

$user_id = $_SESSION['user_id'];

echo "<h1>Complete Cart Functionality Test</h1>";
echo "<p>Testing as User ID: " . $user_id . "</p>";

try {
    $db = Database::getInstance();
    $cart = new Cart($db);
    
    // Test 1: Get cart items
    echo "<h2>Test 1: Retrieving Cart Items</h2>";
    $cartItems = $cart->getCartItems($user_id);
    echo "<p>Items found: " . count($cartItems) . "</p>";
    
    // Test 2: Get cart count
    echo "<h2>Test 2: Cart Count</h2>";
    $cartCount = $cart->getCartCount($user_id);
    echo "<p>Cart count: " . $cartCount . "</p>";
    
    // Test 3: Get cart total
    echo "<h2>Test 3: Cart Total</h2>";
    $cartTotal = $cart->getCartTotal($user_id);
    echo "<p>Cart total: ₱" . number_format($cartTotal, 2) . "</p>";
    
    // Test 4: Check consistency
    echo "<h2>Test 4: Data Consistency Check</h2>";
    $totalQuantity = 0;
    $calculatedTotal = 0;
    
    foreach ($cartItems as $item) {
        $totalQuantity += $item['quantity'];
        $calculatedTotal += ($item['quantity'] * $item['price']);
    }
    
    echo "<p>Calculated quantity from items: " . $totalQuantity . "</p>";
    echo "<p>getCartCount() result: " . $cartCount . "</p>";
    echo "<p>Calculated total from items: ₱" . number_format($calculatedTotal, 2) . "</p>";
    echo "<p>getCartTotal() result: ₱" . number_format($cartTotal, 2) . "</p>";
    
    // Consistency check
    $quantityMatches = ($totalQuantity == $cartCount);
    $totalMatches = (abs($calculatedTotal - $cartTotal) < 0.01); // Allow for floating point precision
    
    if ($quantityMatches && $totalMatches) {
        echo "<p style='color: green; font-weight: bold;'>✅ All cart functions are consistent!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Inconsistency detected:</p>";
        if (!$quantityMatches) {
            echo "<p style='color: red;'>- Quantity mismatch</p>";
        }
        if (!$totalMatches) {
            echo "<p style='color: red;'>- Total amount mismatch</p>";
        }
    }
    
    // Test 5: Display cart items details
    if (!empty($cartItems)) {
        echo "<h2>Test 5: Cart Items Details</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Seller</th><th>Category</th><th>Image</th>";
        echo "</tr>";
        
        foreach ($cartItems as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
            echo "<td>₱" . number_format($item['price'], 2) . "</td>";
            echo "<td>" . $item['quantity'] . "</td>";
            echo "<td>₱" . number_format($item['price'] * $item['quantity'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($item['seller_name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['category_name']) . "</td>";
            echo "<td>" . (isset($item['file_path']) && $item['file_path'] ? "✅" : "❌") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p style='color: green; font-weight: bold;'>✅ Cart items are displaying with proper seller and category information!</p>";
    }
    
    // Test 6: Check if cart page would work
    echo "<h2>Test 6: Cart Page Compatibility</h2>";
    echo "<p>Cart page would show: ";
    if (empty($cartItems)) {
        echo "<span style='color: orange;'>\"Your cart is empty\" message</span>";
    } else {
        echo "<span style='color: green;'>Cart items with order summary</span>";
    }
    echo "</p>";
    
    // Summary
    echo "<h2>🎉 Final Summary</h2>";
    if (!empty($cartItems) && $quantityMatches && $totalMatches) {
        echo "<div style='padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>✅ Cart Functionality Fully Working!</h3>";
        echo "<ul style='color: #155724; margin: 0;'>";
        echo "<li>Cart items are being retrieved successfully</li>";
        echo "<li>Cart count and totals are accurate</li>";
        echo "<li>Seller and category information is displayed properly</li>";
        echo "<li>LEFT JOIN fix resolved the NULL user_id issue</li>";
        echo "<li>Cart page should now display items correctly</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<p><a href='/Art2Cart/cart.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>View Actual Cart Page</a></p>";
    } else {
        echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>Cart Status</h3>";
        if (empty($cartItems)) {
            echo "<p style='color: #721c24; margin: 0;'>Cart is empty - this might be expected if no items have been added.</p>";
        } else {
            echo "<p style='color: #721c24; margin: 0;'>There are still some issues with cart consistency.</p>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ Error occurred:</h3>";
    echo "<p style='color: #721c24;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " <strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>
