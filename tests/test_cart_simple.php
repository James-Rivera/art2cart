<?php
session_start();
echo "<h1>Cart System Test</h1>";

try {
    require_once __DIR__ . '/config/db.php';
    echo "<p>✓ Database config loaded</p>";
    
    $db = Database::getInstance();
    echo "<p>✓ Database instance created</p>";
    
    $pdo = $db->getConnection();
    echo "<p>✓ PDO connection obtained</p>";
    
    require_once __DIR__ . '/includes/Cart.php';
    echo "<p>✓ Cart class loaded</p>";
    
    $cart = new Cart($db);
    echo "<p>✓ Cart instance created</p>";
    
    // Test basic database query
    $stmt = $pdo->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✓ Database query works: " . $result['test'] . "</p>";
    
    // Test cart table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'cart'");
    $stmt->execute();
    $cartTableExists = $stmt->fetch() !== false;
    echo "<p>" . ($cartTableExists ? "✓" : "✗") . " Cart table exists</p>";
    
    if ($cartTableExists) {
        // Check cart table structure
        $stmt = $pdo->prepare("DESCRIBE cart");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>✓ Cart table columns: " . implode(', ', $columns) . "</p>";
    }
    
    echo "<h2>All systems operational!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
