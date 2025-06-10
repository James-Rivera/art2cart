<?php
// Live cart debugging - checks what's currently in session
session_start();

echo "<h1>Live Cart Session Debug</h1>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "</p>";

echo "<h2>Current Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<h2>Testing Cart for Current User ID: " . $user_id . "</h2>";
    
    try {
        require_once __DIR__ . '/../config/db.php';
        require_once __DIR__ . '/../includes/Cart.php';
        
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>✅ User exists: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")</p>";
        } else {
            echo "<p style='color: red;'>❌ User ID " . $user_id . " not found in database!</p>";
        }
        
        // Direct cart table check
        echo "<h3>Direct Cart Table Query:</h3>";
        $stmt = $pdo->prepare("SELECT id, product_id, quantity, created_at FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $rawCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Raw cart items in database: " . count($rawCartItems) . "</p>";
        
        if (!empty($rawCartItems)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Cart ID</th><th>Product ID</th><th>Quantity</th><th>Created</th></tr>";
            foreach ($rawCartItems as $item) {
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
        echo "<h3>Cart Class Methods:</h3>";
        $cart = new Cart($db);
        
        $cartItems = $cart->getCartItems($user_id);
        $cartCount = $cart->getCartCount($user_id);
        $cartTotal = $cart->getCartTotal($user_id);
        
        echo "<p>getCartItems() result: " . count($cartItems) . " items</p>";
        echo "<p>getCartCount() result: " . $cartCount . "</p>";
        echo "<p>getCartTotal() result: ₱" . number_format($cartTotal, 2) . "</p>";
        
        // Check if there's a mismatch
        if (count($rawCartItems) != count($cartItems)) {
            echo "<p style='color: red;'>❌ MISMATCH: Raw DB has " . count($rawCartItems) . " items, but Cart class returns " . count($cartItems) . " items</p>";
            
            // Test the exact Cart class query manually
            echo "<h4>Testing Cart Class Query Manually:</h4>";
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
            $manualResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Manual query result: " . count($manualResult) . " items</p>";
            
            // Check for broken JOINs
            if (count($manualResult) != count($rawCartItems)) {
                echo "<p style='color: red;'>❌ JOIN is failing! Some products don't exist or have issues</p>";
                
                // Check each cart item individually
                echo "<h4>Individual Product Checks:</h4>";
                foreach ($rawCartItems as $cartItem) {
                    $stmt = $pdo->prepare("SELECT id, name, user_id, category_id FROM products WHERE id = ?");
                    $stmt->execute([$cartItem['product_id']]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product) {
                        echo "<p>✅ Product " . $cartItem['product_id'] . " exists: " . htmlspecialchars($product['name']) . "</p>";
                        if ($product['user_id'] === null) {
                            echo "<p style='color: orange;'>⚠️ Product has NULL user_id</p>";
                        }
                        if ($product['category_id'] === null) {
                            echo "<p style='color: orange;'>⚠️ Product has NULL category_id</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>❌ Product " . $cartItem['product_id'] . " DOES NOT EXIST</p>";
                    }
                }
            }
        } else {
            echo "<p style='color: green;'>✅ Raw DB and Cart class counts match</p>";
        }
        
        // Final diagnosis
        echo "<h2>Final Diagnosis:</h2>";
        if (count($cartItems) > 0) {
            echo "<p style='color: green; font-weight: bold;'>✅ Cart should display items on cart.php</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Cart will show 'empty' message on cart.php</p>";
            
            if (count($rawCartItems) > 0) {
                echo "<p style='color: red;'>There are items in the cart table but Cart class can't retrieve them!</p>";
            } else {
                echo "<p style='color: orange;'>Cart table is actually empty for this user.</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ No user_id in session - user not logged in</p>";
    echo "<p>You need to log in first to test cart functionality.</p>";
    echo "<p><a href='/Art2Cart/auth/auth.html'>Go to Login Page</a></p>";
}
?>
