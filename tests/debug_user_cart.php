<?php
// Cart debugging script with user authentication
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Cart.php';

echo "<h1>Cart Debug - User Authentication Test</h1>";

// Test with provided credentials
$test_email = 'testcj@art2cart.com';
$test_password = 'reycopogi';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>1. Testing User Authentication</h2>";
    
    // Find user by email
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>✅ User found: " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")</p>";
        
        // Verify password (assuming password_verify is used)
        if (password_verify($test_password, $user['password']) || $user['password'] === $test_password) {
            echo "<p>✅ Password verification successful</p>";
            
            // Simulate login session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            $user_id = $user['id'];
            
            echo "<h2>2. Testing Cart for User ID: " . $user_id . "</h2>";
            
            // Test direct cart queries
            echo "<h3>2a. Direct Cart Table Query:</h3>";
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $directCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Direct cart items found: " . count($directCartItems) . "</p>";
            
            if (!empty($directCartItems)) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Cart ID</th><th>Product ID</th><th>Quantity</th><th>Created At</th></tr>";
                foreach ($directCartItems as $item) {
                    echo "<tr>";
                    echo "<td>" . $item['id'] . "</td>";
                    echo "<td>" . $item['product_id'] . "</td>";
                    echo "<td>" . $item['quantity'] . "</td>";
                    echo "<td>" . $item['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test Cart class methods
            echo "<h3>2b. Cart Class Methods:</h3>";
            $cart = new Cart($db);
            
            $cartItems = $cart->getCartItems($user_id);
            $cartCount = $cart->getCartCount($user_id);
            $cartTotal = $cart->getCartTotal($user_id);
            
            echo "<p>Cart->getCartItems(): " . count($cartItems) . " items</p>";
            echo "<p>Cart->getCartCount(): " . $cartCount . "</p>";
            echo "<p>Cart->getCartTotal(): ₱" . number_format($cartTotal, 2) . "</p>";
            
            // Test the exact query used in Cart class
            echo "<h3>2c. Testing Cart Class Query:</h3>";
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as cart_id,
                    c.quantity,
                    c.created_at as added_at,
                    p.id as product_id,
                    p.name as product_name,
                    p.description,
                    p.price,
                    p.file_path,
                    p.category_id,
                    COALESCE(cat.name, 'Uncategorized') as category_name,
                    COALESCE(cat.slug, 'uncategorized') as category_slug,
                    COALESCE(u.name, 'Unknown Seller') as seller_name,
                    COALESCE(u.id, 0) as seller_id
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN users u ON p.user_id = u.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $queryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Direct query result: " . count($queryResult) . " items</p>";
            
            if (!empty($queryResult)) {
                echo "<h4>Cart Items Details:</h4>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background: #f0f0f0;'>";
                echo "<th>Product</th><th>Price</th><th>Quantity</th><th>Seller</th><th>Category</th><th>File Path</th>";
                echo "</tr>";
                
                foreach ($queryResult as $item) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                    echo "<td>₱" . number_format($item['price'], 2) . "</td>";
                    echo "<td>" . $item['quantity'] . "</td>";
                    echo "<td>" . htmlspecialchars($item['seller_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['category_name']) . "</td>";
                    echo "<td>" . ($item['file_path'] ? "✅" : "❌") . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Test session simulation for cart.php
            echo "<h2>3. Session Simulation Test</h2>";
            echo "<p>Current session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
            echo "<p>Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "</p>";
            
            // Test what cart.php would see
            echo "<h3>3a. What cart.php would see:</h3>";
            $loggedIn = isset($_SESSION['user_id']);
            echo "<p>Logged in check: " . ($loggedIn ? 'TRUE' : 'FALSE') . "</p>";
            
            if ($loggedIn) {
                $cart_user_id = $_SESSION['user_id'];
                $cart_items = $cart->getCartItems($cart_user_id);
                $cart_total = $cart->getCartTotal($cart_user_id);
                $cart_count = $cart->getCartCount($cart_user_id);
                
                echo "<p>Cart items for cart.php: " . count($cart_items) . "</p>";
                echo "<p>Cart count for cart.php: " . $cart_count . "</p>";
                echo "<p>Cart total for cart.php: ₱" . number_format($cart_total, 2) . "</p>";
                
                echo "<h4>Cart.php condition check:</h4>";
                if (empty($cart_items)) {
                    echo "<p style='color: red;'>❌ cart.php would show 'Your cart is empty'</p>";
                } else {
                    echo "<p style='color: green;'>✅ cart.php would show cart items</p>";
                }
            }
            
            // Check for potential issues
            echo "<h2>4. Potential Issues Analysis</h2>";
            
            // Check if products exist
            if (!empty($directCartItems)) {
                echo "<h3>4a. Product Existence Check:</h3>";
                foreach ($directCartItems as $cartItem) {
                    $stmt = $pdo->prepare("SELECT id, name, user_id FROM products WHERE id = ?");
                    $stmt->execute([$cartItem['product_id']]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product) {
                        echo "<p>✅ Product " . $cartItem['product_id'] . " exists: " . htmlspecialchars($product['name']) . "</p>";
                        if ($product['user_id'] === null) {
                            echo "<p style='color: orange;'>⚠️ Product has NULL user_id</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>❌ Product " . $cartItem['product_id'] . " NOT FOUND</p>";
                    }
                }
            }
            
        } else {
            echo "<p style='color: red;'>❌ Password verification failed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ User not found with email: " . htmlspecialchars($test_email) . "</p>";
        
        // List some users for reference
        echo "<h3>Available users (first 5):</h3>";
        $stmt = $pdo->prepare("SELECT id, name, email FROM users LIMIT 5");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $u) {
            echo "<p>ID: " . $u['id'] . " - " . htmlspecialchars($u['name']) . " (" . htmlspecialchars($u['email']) . ")</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
