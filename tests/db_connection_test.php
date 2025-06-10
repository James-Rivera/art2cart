<?php
// Simple database connection test
try {
    require_once __DIR__ . '/config/db.php';
    
    echo "<h1>Database Connection Test</h1>";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if we can query the cart table
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cart");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total items in cart table: " . $result['total'] . "</p>";
    
    // Test if we can query users with cart items
    $stmt = $pdo->prepare("SELECT DISTINCT user_id FROM cart LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Users with cart items: ";
    foreach ($users as $user) {
        echo $user['user_id'] . " ";
    }
    echo "</p>";
    
    // Test a simple cart query
    if (!empty($users)) {
        $testUserId = $users[0]['user_id'];
        echo "<h2>Testing cart for user ID: " . $testUserId . "</h2>";
        
        // Test our fixed query
        $stmt = $pdo->prepare("
            SELECT 
                c.id as cart_id,
                c.quantity,
                p.id as product_id,
                p.name as product_name,
                p.price,
                COALESCE(cat.name, 'Uncategorized') as category_name,
                COALESCE(u.name, 'Unknown Seller') as seller_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$testUserId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Cart items found: " . count($items) . "</p>";
        
        if (!empty($items)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Seller</th><th>Category</th></tr>";
            foreach ($items as $item) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                echo "<td>₱" . number_format($item['price'], 2) . "</td>";
                echo "<td>" . $item['quantity'] . "</td>";
                echo "<td>" . htmlspecialchars($item['seller_name']) . "</td>";
                echo "<td>" . htmlspecialchars($item['category_name']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p style='color: green; font-weight: bold;'>✅ Cart query with LEFT JOIN is working!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
