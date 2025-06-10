<?php
session_start();

// Include required files
require_once '../config/db.php';
require_once '../includes/Cart.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Final Cart Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info { color: blue; background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .warning { color: orange; background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 8px 15px; margin: 5px; }
        .form-group { margin: 10px 0; }
        input { padding: 5px; width: 200px; }
    </style>
</head>
<body>";

echo "<h1>🔍 Final Cart Functionality Verification</h1>";

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    echo "<div class='success'>✅ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

// Handle login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $login_password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($login_password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        echo "<div class='success'>✅ Login successful! User: {$user['username']} (ID: {$user['id']})</div>";
    } else {
        echo "<div class='error'>❌ Login failed - invalid credentials</div>";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    session_start();
    echo "<div class='info'>ℹ️ Logged out successfully</div>";
}

// Handle add test item
if (isset($_POST['add_test_item']) && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, title FROM products LIMIT 1");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $cart = new Cart($database);
        if ($cart->addToCart($_SESSION['user_id'], $product['id'], 1)) {
            echo "<div class='success'>✅ Added test item: {$product['title']}</div>";
        } else {
            echo "<div class='error'>❌ Failed to add test item</div>";
        }
    }
}

// Handle remove all items
if (isset($_POST['clear_cart']) && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    if ($stmt->execute([$_SESSION['user_id']])) {
        echo "<div class='info'>ℹ️ Cart cleared</div>";
    }
}

echo "<h2>🔐 Authentication Status</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>
        ✅ Logged in as: {$_SESSION['user_name']} (ID: {$_SESSION['user_id']})
        <form method='post' style='display: inline;'>
            <button type='submit' name='logout'>Logout</button>
        </form>
    </div>";
} else {
    echo "<div class='warning'>⚠️ Not logged in</div>";
    echo "<form method='post'>
        <div class='form-group'>
            <label>Email:</label><br>
            <input type='email' name='email' value='testcj@art2cart.com' required>
        </div>
        <div class='form-group'>
            <label>Password:</label><br>
            <input type='password' name='password' value='reycopogi' required>
        </div>
        <button type='submit' name='login'>Login with Test Account</button>
    </form>";
}

if (isset($_SESSION['user_id'])) {
    echo "<h2>🛒 Cart Testing</h2>";
    
    // Test cart functionality
    $cart = new Cart($database);
    
    echo "<form method='post' style='margin: 10px 0;'>
        <button type='submit' name='add_test_item'>Add Test Item to Cart</button>
        <button type='submit' name='clear_cart'>Clear Cart</button>
    </form>";
    
    // Get cart items
    $cartItems = $cart->getCartItems($_SESSION['user_id']);
    
    echo "<h3>Cart Contents:</h3>";
    if (empty($cartItems)) {
        echo "<div class='warning'>⚠️ Cart is empty</div>";
    } else {
        echo "<div class='success'>✅ Found " . count($cartItems) . " items in cart</div>";
        echo "<table>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Seller</th>
                <th>Category</th>
            </tr>";
        
        foreach ($cartItems as $item) {
            echo "<tr>";
            echo "<td>{$item['product_id']}</td>";
            echo "<td>{$item['product_name']}</td>";
            echo "<td>$" . number_format($item['price'], 2) . "</td>";
            echo "<td>{$item['quantity']}</td>";
            echo "<td>{$item['seller_name']}</td>";
            echo "<td>{$item['category_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test cart count
    $cartCount = $cart->getCartCount($_SESSION['user_id']);
    echo "<div class='info'>ℹ️ Cart count: $cartCount items</div>";
    
    // Test cart total
    $cartTotal = $cart->getCartTotal($_SESSION['user_id']);
    echo "<div class='info'>ℹ️ Cart total: $" . number_format($cartTotal, 2) . "</div>";
    
    // Test database query directly
    echo "<h3>Direct Database Query Test:</h3>";
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
            LEFT JOIN users u ON p.seller_id = u.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>✅ Direct query successful - found " . count($results) . " rows</div>";
        
        if (!empty($results)) {
            echo "<table>
                <tr><th>Cart ID</th><th>Product</th><th>Quantity</th><th>Price</th><th>Added</th></tr>";
            foreach ($results as $row) {
                echo "<tr>";
                echo "<td>{$row['cart_id']}</td>";
                echo "<td>{$row['product_name']}</td>";
                echo "<td>{$row['quantity']}</td>";
                echo "<td>$" . number_format($row['price'], 2) . "</td>";
                echo "<td>{$row['added_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Direct query failed: " . $e->getMessage() . "</div>";
    }
}

echo "<h2>📋 Summary</h2>";
echo "<div class='info'>
<p><strong>Fixed Issues:</strong></p>
<ul>
    <li>✅ Database column references corrected (p.name → p.title, u.name → u.username)</li>
    <li>✅ Cart.php getCartItems() method updated</li>
    <li>✅ LEFT JOIN implementation for NULL handling</li>
    <li>✅ CSS layout fixes applied</li>
</ul>
<p><strong>Test with real cart page:</strong> <a href='cart.php' target='_blank'>Open cart.php</a></p>
</div>";

echo "</body></html>";
?>
