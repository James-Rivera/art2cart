<?php
// Complete Cart Diagnosis - Final Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Complete Cart Diagnosis</h1>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/Cart.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<div class='info'><strong>Database connection:</strong> ✅ Success</div>";
    
    // Step 1: Check if user is in session or try to find test user
    echo "<h2>Step 1: User Authentication</h2>";
    
    $user_id = null;
    $user_name = '';
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        echo "<p class='success'>✅ User logged in via session: " . $user_id . "</p>";
    } else {
        echo "<p class='warning'>⚠️ No session user, trying test account...</p>";
        
        // Try to find test user
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
        $stmt->execute(['testcj@art2cart.com']);
        $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testUser) {
            $user_id = $testUser['id'];
            $user_name = $testUser['name'];
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $user_name;
            echo "<p class='success'>✅ Found test user: " . htmlspecialchars($user_name) . " (ID: " . $user_id . ")</p>";
        } else {
            // Find any user with cart items
            $stmt = $pdo->prepare("SELECT DISTINCT c.user_id, u.name, u.email FROM cart c JOIN users u ON c.user_id = u.id LIMIT 1");
            $stmt->execute();
            $anyUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($anyUser) {
                $user_id = $anyUser['user_id'];
                $user_name = $anyUser['name'];
                $_SESSION['user_id'] = $user_id;
                echo "<p class='warning'>⚠️ Using any user with cart items: " . htmlspecialchars($user_name) . " (ID: " . $user_id . ")</p>";
            } else {
                echo "<p class='error'>❌ No users with cart items found</p>";
                exit;
            }
        }
    }
    
    // Step 2: Check user details
    echo "<h2>Step 2: User Verification</h2>";
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='success'>✅ User exists: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")</p>";
    } else {
        echo "<p class='error'>❌ User ID " . $user_id . " not found</p>";
        exit;
    }
    
    // Step 3: Raw cart table check
    echo "<h2>Step 3: Raw Cart Table Analysis</h2>";
    $stmt = $pdo->prepare("SELECT id, product_id, quantity, created_at FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $rawCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Raw cart items found:</strong> " . count($rawCartItems) . "</p>";
    
    if (!empty($rawCartItems)) {
        echo "<table>";
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
    } else {
        echo "<p class='warning'>⚠️ No items in cart table for this user</p>";
        
        // Check if there are any cart items at all
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cart");
        $stmt->execute();
        $totalCart = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total cart items in database: " . $totalCart['total'] . "</p>";
        
        if ($totalCart['total'] > 0) {
            // Show users with cart items
            $stmt = $pdo->prepare("SELECT c.user_id, u.name, COUNT(*) as items FROM cart c JOIN users u ON c.user_id = u.id GROUP BY c.user_id LIMIT 5");
            $stmt->execute();
            $usersWithCart = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Users with cart items:</h4>";
            echo "<table>";
            echo "<tr><th>User ID</th><th>Name</th><th>Items</th></tr>";
            foreach ($usersWithCart as $u) {
                echo "<tr>";
                echo "<td>" . $u['user_id'] . "</td>";
                echo "<td>" . htmlspecialchars($u['name']) . "</td>";
                echo "<td>" . $u['items'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // For testing, let's add an item to this user's cart
        echo "<h4>Adding test item to cart...</h4>";
        
        // Get a product to add
        $stmt = $pdo->prepare("SELECT id, name FROM products LIMIT 1");
        $stmt->execute();
        $testProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testProduct) {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, 1, NOW())");
            if ($stmt->execute([$user_id, $testProduct['id']])) {
                echo "<p class='success'>✅ Added test item: " . htmlspecialchars($testProduct['name']) . "</p>";
                
                // Re-fetch cart items
                $stmt = $pdo->prepare("SELECT id, product_id, quantity, created_at FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $rawCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<p>Cart items after adding: " . count($rawCartItems) . "</p>";
            }
        }
    }
    
    // Step 4: Cart Class Testing
    echo "<h2>Step 4: Cart Class Testing</h2>";
    $cart = new Cart($db);
    
    $cartItems = $cart->getCartItems($user_id);
    $cartCount = $cart->getCartCount($user_id);
    $cartTotal = $cart->getCartTotal($user_id);
    
    echo "<p><strong>Cart Class Results:</strong></p>";
    echo "<ul>";
    echo "<li>getCartItems(): " . count($cartItems) . " items</li>";
    echo "<li>getCartCount(): " . $cartCount . "</li>";
    echo "<li>getCartTotal(): ₱" . number_format($cartTotal, 2) . "</li>";
    echo "</ul>";
    
    // Step 5: Compare results
    echo "<h2>Step 5: Consistency Check</h2>";
    
    $rawCount = count($rawCartItems);
    $classCount = count($cartItems);
    
    if ($rawCount === $classCount) {
        echo "<p class='success'>✅ Consistency check passed: " . $rawCount . " items in both raw DB and Cart class</p>";
    } else {
        echo "<p class='error'>❌ Consistency check failed: " . $rawCount . " items in DB, " . $classCount . " items from Cart class</p>";
        
        echo "<h3>Diagnosing the issue...</h3>";
        
        // Test the Cart class query manually
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
        $manualCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Manual query result: " . count($manualCartItems) . " items</p>";
        
        if (count($manualCartItems) !== $rawCount) {
            echo "<p class='error'>❌ The JOIN is filtering out items</p>";
            
            // Check each cart item individually
            foreach ($rawCartItems as $rawItem) {
                echo "<h4>Checking Cart Item ID: " . $rawItem['id'] . " (Product: " . $rawItem['product_id'] . ")</h4>";
                
                // Check if product exists
                $stmt = $pdo->prepare("SELECT id, name, user_id, category_id FROM products WHERE id = ?");
                $stmt->execute([$rawItem['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    echo "<p class='error'>❌ Product " . $rawItem['product_id'] . " does not exist - this cart item is orphaned</p>";
                } else {
                    echo "<p class='success'>✅ Product exists: " . htmlspecialchars($product['name']) . "</p>";
                    
                    if ($product['user_id'] === null) {
                        echo "<p class='warning'>⚠️ Product has NULL user_id</p>";
                    }
                    if ($product['category_id'] === null) {
                        echo "<p class='warning'>⚠️ Product has NULL category_id</p>";
                    }
                }
            }
        }
    }
    
    // Step 6: Cart.php simulation
    echo "<h2>Step 6: cart.php Simulation</h2>";
    
    if (empty($cartItems)) {
        echo "<div style='border: 2px solid red; padding: 20px; background: #ffe6e6;'>";
        echo "<h3 class='error'>❌ cart.php will show: 'Your cart is empty'</h3>";
        echo "<p>The condition empty(\$cartItems) evaluates to TRUE</p>";
        echo "</div>";
    } else {
        echo "<div style='border: 2px solid green; padding: 20px; background: #e6ffe6;'>";
        echo "<h3 class='success'>✅ cart.php will show: Cart items</h3>";
        echo "<p>Found " . count($cartItems) . " items to display</p>";
        echo "</div>";
        
        if (!empty($cartItems)) {
            echo "<h4>Cart Items that would display:</h4>";
            echo "<table>";
            echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Seller</th><th>Category</th></tr>";
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
    }
    
    // Step 7: Final recommendations
    echo "<h2>Step 7: Action Plan</h2>";
    
    if (!empty($cartItems)) {
        echo "<p class='success'>✅ Cart functionality appears to be working correctly</p>";
        echo "<p><a href='/Art2Cart/cart.php' target='_blank' style='background: #007bff; color: white; padding: 10px; text-decoration: none;'>Open Cart Page</a></p>";
    } else {
        echo "<p class='error'>❌ Cart functionality issue identified</p>";
        
        if ($rawCount > 0 && $classCount === 0) {
            echo "<p><strong>Issue:</strong> Cart items exist in database but Cart class cannot retrieve them</p>";
            echo "<p><strong>Likely cause:</strong> JOIN failure due to missing products or data integrity issues</p>";
            echo "<p><strong>Action needed:</strong> Clean up orphaned cart items or fix product data</p>";
        } elseif ($rawCount === 0) {
            echo "<p><strong>Issue:</strong> No cart items in database for this user</p>";
            echo "<p><strong>Action needed:</strong> Add items to cart through the website interface</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
