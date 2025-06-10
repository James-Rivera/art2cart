<?php
session_start();
require_once __DIR__ . '/config/db.php';

echo "<h1>Direct Cart Database Check</h1>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get current user ID
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    echo "<p>Current session user ID: " . ($user_id ?: 'Not logged in') . "</p>";
    
    // Check all cart entries
    echo "<h2>All Cart Entries</h2>";
    $stmt = $pdo->query("SELECT * FROM cart ORDER BY created_at DESC");
    $allCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allCartItems)) {
        echo "<p style='color:red;'>No items found in cart table at all!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Product ID</th><th>Quantity</th><th>Created At</th></tr>";
        foreach ($allCartItems as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['user_id']}</td>";
            echo "<td>{$item['product_id']}</td>";
            echo "<td>{$item['quantity']}</td>";
            echo "<td>{$item['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // If user is logged in, check their specific items
    if ($user_id) {
        echo "<h2>Items for User {$user_id}</h2>";
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($userCartItems)) {
            echo "<p style='color:orange;'>No items found for current user {$user_id}</p>";
        } else {
            echo "<pre>" . print_r($userCartItems, true) . "</pre>";
        }
        
        // Check the JOIN query that getCartItems uses
        echo "<h2>JOIN Query Test for User {$user_id}</h2>";
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
        $joinedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($joinedItems)) {
            echo "<p style='color:red;'>JOIN query returned no results!</p>";
            
            // Debug each part of the JOIN
            if (!empty($userCartItems)) {
                echo "<h3>Checking JOIN issues...</h3>";
                foreach ($userCartItems as $cartItem) {
                    $product_id = $cartItem['product_id'];
                    echo "<p>Checking product ID {$product_id}:</p>";
                    
                    // Check if product exists
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$product) {
                        echo "<p style='color:red;'>❌ Product {$product_id} not found in products table!</p>";
                    } else {
                        echo "<p style='color:green;'>✓ Product {$product_id} exists</p>";
                        
                        // Check category
                        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                        $stmt->execute([$product['category_id']]);
                        $category = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$category) {
                            echo "<p style='color:red;'>❌ Category {$product['category_id']} not found!</p>";
                        } else {
                            echo "<p style='color:green;'>✓ Category {$product['category_id']} exists</p>";
                        }
                        
                        // Check user/seller
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$product['user_id']]);
                        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$seller) {
                            echo "<p style='color:red;'>❌ Seller {$product['user_id']} not found!</p>";
                        } else {
                            echo "<p style='color:green;'>✓ Seller {$product['user_id']} exists</p>";
                        }
                    }
                }
            }
        } else {
            echo "<p style='color:green;'>JOIN query successful! Found " . count($joinedItems) . " items.</p>";
            echo "<pre>" . print_r($joinedItems, true) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
