<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Cart.php';

// Set error reporting to show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cart Issue Investigation</h1>";

// Get user ID (use session or default to 5 for testing)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 5;
echo "<p><strong>User ID:</strong> {$user_id}</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<p>✓ Database connection successful</p>";
    
    // Test 1: Raw cart data
    echo "<h2>Test 1: Raw Cart Data</h2>";
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $rawCart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Raw cart items found: " . count($rawCart) . "</p>";
    if (!empty($rawCart)) {
        echo "<pre>" . print_r($rawCart, true) . "</pre>";
        
        // Test 2: Check if products exist for these cart items
        echo "<h2>Test 2: Product Validation</h2>";
        foreach ($rawCart as $cartItem) {
            $productId = $cartItem['product_id'];
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                echo "<p>✓ Product {$productId} exists: {$product['name']}</p>";
                
                // Check category
                if (!empty($product['category_id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                    $stmt->execute([$product['category_id']]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($category) {
                        echo "<p>  ✓ Category {$product['category_id']} exists: {$category['name']}</p>";
                    } else {
                        echo "<p style='color:red;'>  ✗ Category {$product['category_id']} missing!</p>";
                    }
                } else {
                    echo "<p style='color:orange;'>  ⚠ Product has NULL category_id</p>";
                }
                
                // Check seller
                if (!empty($product['user_id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$product['user_id']]);
                    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($seller) {
                        echo "<p>  ✓ Seller {$product['user_id']} exists: {$seller['name']}</p>";
                    } else {
                        echo "<p style='color:red;'>  ✗ Seller {$product['user_id']} missing!</p>";
                    }
                } else {
                    echo "<p style='color:orange;'>  ⚠ Product has NULL user_id</p>";
                }
            } else {
                echo "<p style='color:red;'>✗ Product {$productId} does not exist!</p>";
            }
        }
        
        // Test 3: Try the actual JOIN query
        echo "<h2>Test 3: JOIN Query Test</h2>";
        try {
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
                    cat.name as category_name,
                    cat.slug as category_slug,
                    u.name as seller_name,
                    u.id as seller_id
                FROM cart c
                JOIN products p ON c.product_id = p.id
                JOIN categories cat ON p.category_id = cat.id
                JOIN users u ON p.user_id = u.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $joinResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>JOIN query result count: " . count($joinResult) . "</p>";
            if (!empty($joinResult)) {
                echo "<pre>" . print_r($joinResult, true) . "</pre>";
            } else {
                echo "<p style='color:red;'>JOIN query returned no results despite having cart items!</p>";
                
                // Test LEFT JOINs to see what's missing
                echo "<h3>Testing with LEFT JOINs to identify missing data:</h3>";
                $stmt = $pdo->prepare("
                    SELECT 
                        c.id as cart_id,
                        c.product_id,
                        p.id as product_exists,
                        p.name as product_name,
                        p.category_id,
                        cat.id as category_exists,
                        cat.name as category_name,
                        p.user_id,
                        u.id as user_exists,
                        u.name as seller_name
                    FROM cart c
                    LEFT JOIN products p ON c.product_id = p.id
                    LEFT JOIN categories cat ON p.category_id = cat.id
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE c.user_id = ?
                ");
                $stmt->execute([$user_id]);
                $leftJoinResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<pre>" . print_r($leftJoinResult, true) . "</pre>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color:red;'>JOIN query failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test 4: Cart class methods
    echo "<h2>Test 4: Cart Class Methods</h2>";
    $cart = new Cart($db);
    
    $cartCount = $cart->getCartCount($user_id);
    echo "<p>Cart count: {$cartCount}</p>";
    
    $cartTotal = $cart->getCartTotal($user_id);
    echo "<p>Cart total: {$cartTotal}</p>";
    
    $cartItems = $cart->getCartItems($user_id);
    echo "<p>Cart items from class: " . count($cartItems) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
