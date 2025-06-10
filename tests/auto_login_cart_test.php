<?php
// Auto-login and cart test script
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Cart.php';

echo "<h1>Auto-Login Cart Test</h1>";

$test_email = 'testcj@art2cart.com';
$test_password = 'reycopogi';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>1. Finding User Account</h2>";
    
    // Find user by email
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color: red;'>❌ User not found with email: " . htmlspecialchars($test_email) . "</p>";
        
        // Show available users
        echo "<h3>Available users:</h3>";
        $stmt = $pdo->prepare("SELECT id, name, email FROM users LIMIT 10");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        foreach ($users as $u) {
            echo "<tr><td>" . $u['id'] . "</td><td>" . htmlspecialchars($u['name']) . "</td><td>" . htmlspecialchars($u['email']) . "</td></tr>";
        }
        echo "</table>";
        exit;
    }
    
    echo "<p>✅ User found: " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")</p>";
    
    // Verify password
    $password_valid = false;
    if (password_verify($test_password, $user['password'])) {
        $password_valid = true;
        echo "<p>✅ Password verified with password_verify()</p>";
    } elseif ($user['password'] === $test_password) {
        $password_valid = true;
        echo "<p>✅ Password matched directly (plain text)</p>";
    } elseif (md5($test_password) === $user['password']) {
        $password_valid = true;
        echo "<p>✅ Password matched with MD5 hash</p>";
    } else {
        echo "<p style='color: red;'>❌ Password verification failed</p>";
        echo "<p>Stored password: " . substr($user['password'], 0, 20) . "...</p>";
        echo "<p>Trying anyway for testing...</p>";
        $password_valid = true; // Force for testing
    }
    
    if ($password_valid) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        
        $user_id = $user['id'];
        
        echo "<h2>2. Session Set - Testing Cart</h2>";
        echo "<p>Session user_id: " . $_SESSION['user_id'] . "</p>";
        
        // Check raw cart data
        echo "<h3>2a. Raw Cart Data:</h3>";
        $stmt = $pdo->prepare("SELECT id, product_id, quantity, created_at FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $rawCart = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Raw cart items: " . count($rawCart) . "</p>";
        
        if (!empty($rawCart)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Cart ID</th><th>Product ID</th><th>Quantity</th><th>Created</th></tr>";
            foreach ($rawCart as $item) {
                echo "<tr>";
                echo "<td>" . $item['id'] . "</td>";
                echo "<td>" . $item['product_id'] . "</td>";
                echo "<td>" . $item['quantity'] . "</td>";
                echo "<td>" . $item['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Test Cart class
        echo "<h3>2b. Cart Class Test:</h3>";
        $cart = new Cart($db);
        
        $cartItems = $cart->getCartItems($user_id);
        $cartCount = $cart->getCartCount($user_id);
        $cartTotal = $cart->getCartTotal($user_id);
        
        echo "<p>Cart->getCartItems(): " . count($cartItems) . " items</p>";
        echo "<p>Cart->getCartCount(): " . $cartCount . "</p>";
        echo "<p>Cart->getCartTotal(): ₱" . number_format($cartTotal, 2) . "</p>";
        
        if (!empty($cartItems)) {
            echo "<h4>Cart Items Details:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>Product</th><th>Price</th><th>Quantity</th><th>Seller</th><th>Category</th></tr>";
            
            foreach ($cartItems as $item) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                echo "<td>₱" . number_format($item['price'], 2) . "</td>";
                echo "<td>" . $item['quantity'] . "</td>";
                echo "<td>" . htmlspecialchars($item['seller_name']) . "</td>";
                echo "<td>" . htmlspecialchars($item['category_name']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Test what cart.php would show
        echo "<h2>3. Cart.php Simulation</h2>";
        echo "<p>Simulating cart.php logic:</p>";
        
        $loggedIn = isset($_SESSION['user_id']);
        echo "<p>Logged in check: " . ($loggedIn ? 'TRUE' : 'FALSE') . "</p>";
        
        if ($loggedIn) {
            $cart_user_id = $_SESSION['user_id'];
            $cart_items = $cart->getCartItems($cart_user_id);
            
            echo "<p>Cart items retrieved: " . count($cart_items) . "</p>";
            
            if (empty($cart_items)) {
                echo "<div style='border: 2px solid red; padding: 10px; background: #ffe6e6;'>";
                echo "<h3>❌ Cart.php would show: 'Your cart is empty'</h3>";
                echo "</div>";
            } else {
                echo "<div style='border: 2px solid green; padding: 10px; background: #e6ffe6;'>";
                echo "<h3>✅ Cart.php would show: Cart items (" . count($cart_items) . " items)</h3>";
                echo "</div>";
            }
        }
        
        // Check for data consistency issues
        echo "<h2>4. Data Consistency Check</h2>";
        
        if (count($rawCart) > 0 && count($cartItems) === 0) {
            echo "<p style='color: red; font-weight: bold;'>❌ PROBLEM FOUND: Raw cart has items but Cart class returns empty!</p>";
            
            echo "<h3>Investigating JOIN failure:</h3>";
            
            foreach ($rawCart as $cartItem) {
                $pid = $cartItem['product_id'];
                echo "<h4>Checking Product ID: " . $pid . "</h4>";
                
                // Check if product exists
                $stmt = $pdo->prepare("SELECT id, name, user_id, category_id FROM products WHERE id = ?");
                $stmt->execute([$pid]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    echo "<p style='color: red;'>❌ Product " . $pid . " does not exist!</p>";
                } else {
                    echo "<p>✅ Product exists: " . htmlspecialchars($product['name']) . "</p>";
                    
                    // Test the JOIN manually
                    $stmt = $pdo->prepare("
                        SELECT 
                            c.id as cart_id,
                            p.name as product_name,
                            COALESCE(u.name, 'Unknown Seller') as seller_name,
                            COALESCE(cat.name, 'Uncategorized') as category_name
                        FROM cart c
                        JOIN products p ON c.product_id = p.id
                        LEFT JOIN categories cat ON p.category_id = cat.id
                        LEFT JOIN users u ON p.user_id = u.id
                        WHERE c.id = ?
                    ");
                    $stmt->execute([$cartItem['id']]);
                    $joinResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($joinResult) {
                        echo "<p>✅ JOIN works for this item</p>";
                    } else {
                        echo "<p style='color: red;'>❌ JOIN fails for this item</p>";
                    }
                }
            }
        } elseif (count($rawCart) === 0) {
            echo "<p style='color: orange;'>Cart is actually empty in database</p>";
        } else {
            echo "<p style='color: green;'>✅ Data consistency looks good</p>";
        }
        
        echo "<h2>5. Action Items</h2>";
        echo "<p><a href='/Art2Cart/cart.php' target='_blank' style='padding: 10px; background: #007bff; color: white; text-decoration: none;'>Open Cart Page (New Tab)</a></p>";
        echo "<p><a href='/Art2Cart/tests/live_cart_debug.php' target='_blank' style='padding: 10px; background: #28a745; color: white; text-decoration: none;'>Check Live Session Debug</a></p>";
        
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
