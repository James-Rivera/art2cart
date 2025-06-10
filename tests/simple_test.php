<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Cart Test Results:\n";
echo "==================\n";

// Test database connection
try {
    require_once 'config/db.php';
    $db = Database::getInstance();
    echo "✓ Database connection: OK\n";
    
    // Test cart table
    $pdo = $db->getConnection();
    $result = $pdo->query("SELECT COUNT(*) FROM cart");
    echo "✓ Cart table: OK\n";
    
    // Test products table
    $result = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $result->fetch()['count'];
    echo "✓ Products available: " . $count . "\n";
    
    // Test cart class
    require_once 'includes/Cart.php';
    $cart = new Cart($db);
    echo "✓ Cart class: OK\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
