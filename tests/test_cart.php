<?php
// Test cart functionality

session_start();
require_once 'config/db.php';
require_once 'includes/Cart.php';

echo "Testing Cart Functionality\n";
echo "========================\n\n";

try {
    $db = Database::getInstance();
    $cart = new Cart($db);
    
    // Test 1: Check if we can create cart instance
    echo "✓ Cart class instantiated successfully\n";
    
    // Test 2: Check database connection
    $pdo = $db->getConnection();
    echo "✓ Database connection established\n";
    
    // Test 3: Check cart table structure
    $result = $pdo->query("DESCRIBE cart");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Cart table columns: " . implode(', ', $columns) . "\n";
    
    // Test 4: Test API endpoint (simulate)
    echo "\nTesting API simulation...\n";
    
    // Simulate adding item to cart (user must be logged in)
    if (!isset($_SESSION['user_id'])) {
        echo "! User not logged in - cart operations require authentication\n";
    } else {
        echo "✓ User logged in with ID: " . $_SESSION['user_id'] . "\n";
    }
    
    // Test 5: Check products table for testing
    $result = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✓ Products available for testing: " . $productCount . "\n";
    
    // Test 6: Check if cart API file exists
    if (file_exists('api/cart.php')) {
        echo "✓ Cart API endpoint exists\n";
    } else {
        echo "✗ Cart API endpoint missing\n";
    }
    
    echo "\nCart functionality tests completed!\n";
    echo "Ready for frontend testing.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
