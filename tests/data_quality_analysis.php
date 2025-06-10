<?php
// Script to analyze and potentially fix NULL user_id issues in products
require_once __DIR__ . '/config/db.php';

try {
    echo "<h1>Data Quality Analysis</h1>";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check products with NULL user_id that are in cart
    echo "<h2>1. Products in cart with NULL user_id:</h2>";
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id, p.name, p.user_id, c.user_id as cart_user_id
        FROM products p
        JOIN cart c ON p.id = c.product_id
        WHERE p.user_id IS NULL
        LIMIT 10
    ");
    $stmt->execute();
    $nullProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($nullProducts)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Product user_id</th><th>Cart Owner</th></tr>";
        foreach ($nullProducts as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . ($product['user_id'] ?: 'NULL') . "</td>";
            echo "<td>" . $product['cart_user_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p style='color: orange;'>⚠️ Found " . count($nullProducts) . " products in cart with NULL user_id</p>";
        
        // Suggest fix
        echo "<h3>Suggested Fix:</h3>";
        echo "<p>We could assign these products to a default seller (admin) or the cart owner.</p>";
        echo "<button onclick='fixNullUserIds()'>Fix NULL user_id values</button>";
        
    } else {
        echo "<p style='color: green;'>✅ No products with NULL user_id found in cart</p>";
    }
    
    // Check total cart statistics
    echo "<h2>2. Cart Statistics:</h2>";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_cart_items,
            COUNT(DISTINCT user_id) as users_with_items,
            SUM(quantity) as total_quantity
        FROM cart
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total cart entries: " . $stats['total_cart_items'] . "</p>";
    echo "<p>Users with cart items: " . $stats['users_with_items'] . "</p>";
    echo "<p>Total items quantity: " . $stats['total_quantity'] . "</p>";
    
    // Test our fixed Cart class
    echo "<h2>3. Testing Fixed Cart Class:</h2>";
    
    require_once __DIR__ . '/includes/Cart.php';
    $cart = new Cart($db);
    
    // Get a user with cart items
    $stmt = $pdo->prepare("SELECT DISTINCT user_id FROM cart LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $testUserId = $user['user_id'];
        echo "<p>Testing with user ID: " . $testUserId . "</p>";
        
        $cartItems = $cart->getCartItems($testUserId);
        $cartCount = $cart->getCartCount($testUserId);
        $cartTotal = $cart->getCartTotal($testUserId);
        
        echo "<p>Cart items retrieved: " . count($cartItems) . "</p>";
        echo "<p>Cart count: " . $cartCount . "</p>";
        echo "<p>Cart total: ₱" . number_format($cartTotal, 2) . "</p>";
        
        if (count($cartItems) > 0) {
            echo "<p style='color: green; font-weight: bold;'>✅ Cart functionality is working!</p>";
            
            echo "<h4>Sample cart item:</h4>";
            $item = $cartItems[0];
            echo "<ul>";
            echo "<li>Product: " . htmlspecialchars($item['product_name']) . "</li>";
            echo "<li>Price: ₱" . number_format($item['price'], 2) . "</li>";
            echo "<li>Quantity: " . $item['quantity'] . "</li>";
            echo "<li>Seller: " . htmlspecialchars($item['seller_name']) . "</li>";
            echo "<li>Category: " . htmlspecialchars($item['category_name']) . "</li>";
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<script>
function fixNullUserIds() {
    if (confirm('This will assign NULL user_id products to user ID 1 (admin). Continue?')) {
        fetch('/Art2Cart/fix_null_userids.php', {
            method: 'POST'
        })
        .then(response => response.text())
        .then(data => {
            alert('Fix applied! Page will reload.');
            location.reload();
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}
</script>
