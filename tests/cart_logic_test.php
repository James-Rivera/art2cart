<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Cart.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cart Logic Test with Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #c3e6cb; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #f5c6cb; }
        .warning { color: orange; background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #bee5eb; }
        .form-group { margin: 15px 0; }
        input { padding: 8px; width: 250px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { color: #333; text-align: center; }
        h2 { color: #666; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Cart Logic Test with Login</h1>";

// Handle login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            echo "<div class='success'>✅ Login successful! User: {$user['username']} (ID: {$user['id']})</div>";
        } else {
            echo "<div class='error'>❌ Login failed - invalid credentials</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Login error: " . $e->getMessage() . "</div>";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    session_start();
    echo "<div class='info'>ℹ️ Logged out successfully</div>";
}

// Check session
echo "<h2>🔐 Authentication Status</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>
        ✅ User logged in: {$_SESSION['user_name']} (ID: {$_SESSION['user_id']})
        <form method='post' style='display: inline; margin-left: 10px;'>
            <button type='submit' name='logout'>Logout</button>
        </form>
    </div>";
} else {
    echo "<div class='warning'>⚠️ No user logged in</div>";
    echo "<form method='post' style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
        <h3>Login with Test Account</h3>
        <div class='form-group'>
            <label><strong>Email:</strong></label><br>
            <input type='email' name='email' value='testcj@art2cart.com' required>
        </div>
        <div class='form-group'>
            <label><strong>Password:</strong></label><br>
            <input type='password' name='password' value='reycopogi' required>
        </div>
        <button type='submit' name='login'>🔓 Login and Test Cart</button>
    </form>";
    echo "</div></body></html>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Test database connection
try {
    $db = Database::getInstance();
    echo "<p>✅ Database connection successful</p>";
    
    $cart = new Cart($db);
    echo "<p>✅ Cart object created</p>";
    
    // Get cart data using exact same logic as cart.php
    $cartItems = $cart->getCartItems($user_id);
    $cartTotal = $cart->getCartTotal($user_id);
    $cartCount = $cart->getCartCount($user_id);
    
    echo "<h2>Cart Data:</h2>";
    echo "<p>Cart Count: $cartCount</p>";
    echo "<p>Cart Total: $" . number_format($cartTotal, 2) . "</p>";
    echo "<p>Cart Items Retrieved: " . count($cartItems) . "</p>";
    
    if (empty($cartItems)) {
        echo "<div style='background: #ffcccc; padding: 10px; margin: 10px 0;'>
            <h3>❌ Cart Items Array is Empty!</h3>
            <p>This explains why cart.php shows 'Your cart is empty'</p>
        </div>";
        
        // Let's debug further
        echo "<h3>🔍 Debug Analysis:</h3>";
        
        // Check if there are actually items in database
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $dbCount = $stmt->fetch()['count'];
        
        echo "<p>Direct DB count query: $dbCount items</p>";
        
        if ($dbCount > 0) {
            echo "<div style='background: #ffcccc; padding: 10px;'>
                <h4>⚠️ ISSUE FOUND: Database has $dbCount items but getCartItems() returns empty array!</h4>
                <p>This indicates a problem with the getCartItems() method query.</p>
            </div>";
            
            // Test the exact query from getCartItems
            echo "<h4>Testing getCartItems() Query:</h4>";
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        c.id as cart_id,
                        c.quantity,
                        c.created_at as added_at,
                        p.id as product_id,
                        p.title as product_name,
                        p.description,
                        p.price,
                        p.file_path,
                        p.category_id,
                        COALESCE(cat.name, 'Uncategorized') as category_name,
                        COALESCE(cat.slug, 'uncategorized') as category_slug,
                        COALESCE(u.username, 'Unknown Seller') as seller_name,
                        COALESCE(u.id, 0) as seller_id
                    FROM cart c
                    JOIN products p ON c.product_id = p.id
                    LEFT JOIN categories cat ON p.category_id = cat.id
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE c.user_id = ?
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute([$user_id]);
                $directResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p>Direct query results: " . count($directResults) . " items</p>";
                
                if (count($directResults) > 0) {
                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                    echo "<tr><th>Cart ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Price</th><th>Seller</th></tr>";
                    foreach ($directResults as $row) {
                        echo "<tr>";
                        echo "<td>{$row['cart_id']}</td>";
                        echo "<td>{$row['product_id']}</td>";
                        echo "<td>{$row['product_name']}</td>";
                        echo "<td>{$row['quantity']}</td>";
                        echo "<td>{$row['price']}</td>";
                        echo "<td>{$row['seller_name']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>❌ Direct query also returns no results. Checking for data inconsistencies...</p>";
                }
                
            } catch (Exception $e) {
                echo "<p>❌ Query failed: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<div style='background: #ffffcc; padding: 10px;'>
                <h4>ℹ️ Database is actually empty</h4>
                <p>No cart items found in database for user $user_id</p>
            </div>";
        }
        
    } else {
        echo "<div style='background: #ccffcc; padding: 10px; margin: 10px 0;'>
            <h3>✅ Cart Items Retrieved Successfully!</h3>
            <p>cart.php should be working. Let's display the items:</p>
        </div>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Seller</th><th>Category</th></tr>";
        foreach ($cartItems as $item) {
            echo "<tr>";
            echo "<td>{$item['product_name']}</td>";
            echo "<td>$" . number_format($item['price'], 2) . "</td>";
            echo "<td>{$item['quantity']}</td>";
            echo "<td>{$item['seller_name']}</td>";
            echo "<td>{$item['category_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li><a href='../cart.php'>Test cart.php</a></li>";
echo "<li><a href='../catalogue.php'>Add items from catalogue</a></li>";
echo "<li><a href='final_cart_verification.php'>Run full verification</a></li>";
echo "</ul>";
?>
