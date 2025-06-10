<!DOCTYPE html>
<html>
<head>
    <title>Cart Diagnosis Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-group { margin: 10px 0; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Cart Diagnosis Tool</h1>
    
    <?php
    session_start();
    
    // Handle login form
    if (isset($_POST['login'])) {
        require_once __DIR__ . '/../config/db.php';
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $email = $_POST['email'];
        $password = $_POST['password'];
          $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            echo "<div class='success'>✅ Login successful! User: {$user['username']}</div>";
        } else {
            echo "<div class='error'>❌ Login failed. Check credentials.</div>";
        }
    }
    
    // Handle logout
    if (isset($_POST['logout'])) {
        session_destroy();
        session_start();
        echo "<div class='info'>Logged out successfully</div>";
    }
    ?>
    
    <div class="info">
        <strong>Current Session:</strong>
        <?php if (isset($_SESSION['user_id'])): ?>
            User ID: <?php echo $_SESSION['user_id']; ?>, Name: <?php echo $_SESSION['user_name'] ?? 'Unknown'; ?>
            <form method="post" style="display: inline; margin-left: 10px;">
                <button type="submit" name="logout">Logout</button>
            </form>
        <?php else: ?>
            Not logged in
        <?php endif; ?>
    </div>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <h3>Login to Test Cart</h3>
        <form method="post">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="testcj@art2cart.com" style="width: 300px; padding: 5px;">
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" value="reycopogi" style="width: 300px; padding: 5px;">
            </div>
            <button type="submit" name="login">Login and Test</button>
        </form>
    <?php else: ?>
        
        <h2>Cart Analysis</h2>
        <?php
        try {            require_once __DIR__ . '/../config/db.php';
            require_once __DIR__ . '/../includes/Cart.php';
            
            $user_id = $_SESSION['user_id'];
            $db = Database::getInstance();
            $cart = new Cart($db);
            $pdo = $db->getConnection();
            
            echo "<div class='success'>✅ Database and Cart class loaded successfully</div>";
            
            // Step 1: Raw database query
            echo "<h3>Step 1: Raw Cart Data</h3>";
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $rawCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Raw cart items in database:</strong> " . count($rawCartItems) . "</p>";
            
            if (!empty($rawCartItems)) {
                echo "<table>";
                echo "<tr><th>Cart ID</th><th>Product ID</th><th>Quantity</th><th>Created</th></tr>";
                foreach ($rawCartItems as $item) {
                    echo "<tr>";
                    echo "<td>{$item['id']}</td>";
                    echo "<td>{$item['product_id']}</td>";
                    echo "<td>{$item['quantity']}</td>";
                    echo "<td>{$item['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ No cart items found in database for this user</p>";
                
                // Add a test item
                $stmt = $pdo->prepare("SELECT id, title FROM products LIMIT 1");
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, 1, NOW())");
                    if ($stmt->execute([$user_id, $product['id']])) {
                        echo "<p class='success'>✅ Added test item: {$product['title']}</p>";
                        
                        // Refresh the page to show the new item
                        echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
                    }
                }
            }
            
            // Step 2: Cart class methods
            echo "<h3>Step 2: Cart Class Methods</h3>";
            $cartItems = $cart->getCartItems($user_id);
            $cartCount = $cart->getCartCount($user_id);
            $cartTotal = $cart->getCartTotal($user_id);
            
            echo "<p><strong>Cart items via Cart class:</strong> " . count($cartItems) . "</p>";
            echo "<p><strong>Cart count:</strong> " . $cartCount . "</p>";
            echo "<p><strong>Cart total:</strong> $" . number_format($cartTotal, 2) . "</p>";
            
            if (!empty($cartItems)) {
                echo "<h4>Cart Items Details:</h4>";
                echo "<table>";
                echo "<tr><th>Product</th><th>Seller</th><th>Category</th><th>Quantity</th><th>Price</th><th>Total</th></tr>";
                foreach ($cartItems as $item) {
                    $itemTotal = $item['quantity'] * $item['price'];
                    echo "<tr>";
                    echo "<td>{$item['product_name']}</td>";
                    echo "<td>{$item['seller_name']}</td>";
                    echo "<td>{$item['category_name']}</td>";
                    echo "<td>{$item['quantity']}</td>";
                    echo "<td>$" . number_format($item['price'], 2) . "</td>";
                    echo "<td>$" . number_format($itemTotal, 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ Cart class returned no items (this is the problem!)</p>";
                
                // Debug the Cart query
                echo "<h4>Debug: Cart Query Analysis</h4>";
                $debugQuery = "
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
                ";
                
                $stmt = $pdo->prepare($debugQuery);
                $stmt->execute([$user_id]);
                $debugItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p><strong>Debug query results:</strong> " . count($debugItems) . "</p>";
                if (!empty($debugItems)) {
                    echo "<table>";
                    echo "<tr><th>Cart ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Seller</th></tr>";
                    foreach ($debugItems as $item) {
                        echo "<tr>";
                        echo "<td>{$item['cart_id']}</td>";
                        echo "<td>{$item['product_id']}</td>";
                        echo "<td>{$item['product_name']}</td>";
                        echo "<td>{$item['quantity']}</td>";
                        echo "<td>{$item['seller_name']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
            
            // Step 3: Check the cart.php page behavior
            echo "<h3>Step 3: Cart Page Test</h3>";
            echo "<p><a href='cart.php' target='_blank'>Open Cart Page</a></p>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
        ?>
        
    <?php endif; ?>
    
</body>
</html>
