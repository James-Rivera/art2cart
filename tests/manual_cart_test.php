<?php
/**
 * Manual Cart Test - Direct database connection
 * This script tests cart functionality without relying on web server session
 */

echo "=== Manual Cart Test ===\n";

// Test database connection first
echo "Testing database connection...\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=art2cart;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ Database connection successful\n\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Make sure WAMP MySQL service is running\n";
    exit(1);
}

// Test user lookup
echo "Looking for test user (testcj@art2cart.com)...\n";
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
$stmt->execute(['testcj@art2cart.com']);
$testUser = $stmt->fetch();

if ($testUser) {
    $user_id = $testUser['id'];
    echo "✅ Found test user: {$testUser['name']} (ID: {$user_id})\n\n";
} else {
    echo "❌ Test user not found. Looking for any user with cart items...\n";
    $stmt = $pdo->prepare("SELECT DISTINCT c.user_id, u.name, u.email FROM cart c JOIN users u ON c.user_id = u.id LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        $user_id = $testUser['user_id'];
        echo "✅ Using user: {$testUser['name']} (ID: {$user_id})\n\n";
    } else {
        echo "❌ No users with cart items found\n";
        
        // Find any user
        $stmt = $pdo->prepare("SELECT id, name, email FROM users LIMIT 1");
        $stmt->execute();
        $anyUser = $stmt->fetch();
        
        if ($anyUser) {
            $user_id = $anyUser['id'];
            echo "✅ Using any user: {$anyUser['name']} (ID: {$user_id})\n\n";
        } else {
            echo "❌ No users found in database\n";
            exit(1);
        }
    }
}

// Check raw cart data
echo "Checking raw cart data for user {$user_id}...\n";
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$rawCart = $stmt->fetchAll();

echo "Raw cart items found: " . count($rawCart) . "\n";
if (!empty($rawCart)) {
    foreach ($rawCart as $item) {
        echo "  - Cart ID: {$item['id']}, Product ID: {$item['product_id']}, Quantity: {$item['quantity']}\n";
    }
} else {
    echo "No cart items found for this user\n";
    
    // Check if there are any cart items at all
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cart");
    $stmt->execute();
    $totalCart = $stmt->fetch();
    echo "Total cart items in database: {$totalCart['total']}\n";
    
    if ($totalCart['total'] == 0) {
        echo "Adding test cart item...\n";
        
        // Get a product
        $stmt = $pdo->prepare("SELECT id, name FROM products LIMIT 1");
        $stmt->execute();
        $product = $stmt->fetch();
        
        if ($product) {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, 1, NOW())");
            if ($stmt->execute([$user_id, $product['id']])) {
                echo "✅ Added test item: {$product['name']}\n";
                
                // Re-check cart
                $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $rawCart = $stmt->fetchAll();
                echo "Cart items after adding: " . count($rawCart) . "\n";
            } else {
                echo "❌ Failed to add test item\n";
            }
        } else {
            echo "❌ No products found to add to cart\n";
        }
    }
}

echo "\n";

// Now test the Cart class behavior
echo "Testing Cart class...\n";
try {    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/Cart.php';
    
    $db = Database::getInstance();
    $cart = new Cart($db);
    
    echo "✅ Cart class loaded successfully\n";
    
    // Test cart methods
    $cartItems = $cart->getCartItems($user_id);
    $cartCount = $cart->getCartCount($user_id);
    $cartTotal = $cart->getCartTotal($user_id);
    
    echo "Cart items retrieved: " . count($cartItems) . "\n";
    echo "Cart count: " . $cartCount . "\n";
    echo "Cart total: $" . number_format($cartTotal, 2) . "\n";
    
    if (!empty($cartItems)) {
        echo "\nCart Items Details:\n";
        foreach ($cartItems as $item) {
            echo "  - {$item['product_name']} (Qty: {$item['quantity']}, Price: $" . number_format($item['price'], 2) . ")\n";
            echo "    Seller: {$item['seller_name']}, Category: {$item['category_name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Cart class error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
