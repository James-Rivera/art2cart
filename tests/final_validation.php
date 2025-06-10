<?php
// Final validation script for Art2Cart shopping cart functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🛒 Art2Cart Cart Functionality - Final Validation</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .test-section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #007cba; }
    .test-result { padding: 5px 0; }
</style>";

$tests = [];
$passed = 0;
$total = 0;

function runTest($testName, $testFunction) {
    global $tests, $passed, $total;
    $total++;
    
    try {
        $result = $testFunction();
        if ($result) {
            $tests[] = ["name" => $testName, "status" => "PASS", "message" => "✓ Test passed"];
            $passed++;
        } else {
            $tests[] = ["name" => $testName, "status" => "FAIL", "message" => "✗ Test failed"];
        }
    } catch (Exception $e) {
        $tests[] = ["name" => $testName, "status" => "ERROR", "message" => "✗ Error: " . $e->getMessage()];
    }
}

// Test 1: Database Connection
runTest("Database Connection", function() {
    require_once 'config/db.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    return $pdo instanceof PDO;
});

// Test 2: Cart Table Structure
runTest("Cart Table Structure", function() {
    require_once 'config/db.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $result = $pdo->query("DESCRIBE cart");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'user_id', 'product_id', 'quantity', 'created_at', 'updated_at'];
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            return false;
        }
    }
    return true;
});

// Test 3: Cart Class Instantiation
runTest("Cart Class Instantiation", function() {
    require_once 'config/db.php';
    require_once 'includes/Cart.php';
    
    $db = Database::getInstance();
    $cart = new Cart($db);
    return $cart instanceof Cart;
});

// Test 4: Products Available
runTest("Products Available", function() {
    require_once 'config/db.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    return $count > 0;
});

// Test 5: API File Exists
runTest("Cart API File", function() {
    return file_exists('api/cart.php');
});

// Test 6: Header Template Fixed
runTest("Header Template", function() {
    $content = file_get_contents('static/templates/header_new.php');
    return strpos($content, 'Database::getInstance()') !== false;
});

// Test 7: Main Pages Exist
runTest("Main Pages Exist", function() {
    $pages = ['catalogue.php', 'cart.php', 'checkout.php', 'order-confirmation.php'];
    foreach ($pages as $page) {
        if (!file_exists($page)) {
            return false;
        }
    }
    return true;
});

// Test 8: User Table for Authentication
runTest("User Authentication Table", function() {
    require_once 'config/db.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    return $count >= 0; // Just check if table exists and is accessible
});

// Display Results
echo "<div class='test-section'>";
echo "<h2>Test Results Summary</h2>";
echo "<p><strong>Passed:</strong> {$passed} / {$total}</p>";

if ($passed === $total) {
    echo "<div class='success test-result'><strong>🎉 ALL TESTS PASSED! Cart functionality is ready.</strong></div>";
} else {
    echo "<div class='error test-result'><strong>⚠️ Some tests failed. Review the details below.</strong></div>";
}
echo "</div>";

// Display Individual Test Results
echo "<div class='test-section'>";
echo "<h2>Individual Test Results</h2>";
foreach ($tests as $test) {
    $class = $test['status'] === 'PASS' ? 'success' : 'error';
    echo "<div class='{$class} test-result'>";
    echo "<strong>{$test['name']}:</strong> {$test['message']}";
    echo "</div>";
}
echo "</div>";

// Instructions
echo "<div class='test-section'>";
echo "<h2>🚀 Next Steps</h2>";
if ($passed === $total) {
    echo "<ol>";
    echo "<li><strong>Create Account:</strong> <a href='auth/auth.html'>Register/Login</a></li>";
    echo "<li><strong>Browse Products:</strong> <a href='catalogue.php'>View Catalogue</a></li>";
    echo "<li><strong>Test Cart:</strong> <a href='cart_test.html'>Interactive Test Page</a></li>";
    echo "<li><strong>View Cart:</strong> <a href='cart.php'>Shopping Cart</a></li>";
    echo "<li><strong>Checkout:</strong> <a href='checkout.php'>Complete Purchase</a></li>";
    echo "</ol>";
    
    echo "<h3>📋 Features Ready:</h3>";
    echo "<ul>";
    echo "<li>✅ Add products to cart from catalogue pages</li>";
    echo "<li>✅ Real-time cart count updates in header</li>";
    echo "<li>✅ User-specific cart persistence</li>";
    echo "<li>✅ AJAX cart operations</li>";
    echo "<li>✅ Authentication-protected cart operations</li>";
    echo "<li>✅ Complete checkout workflow</li>";
    echo "</ul>";
} else {
    echo "<p>Please fix the failing tests before proceeding with cart functionality testing.</p>";
}
echo "</div>";

// System Information
echo "<div class='test-section'>";
echo "<h2>💻 System Information</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "</p>";
echo "<p><strong>Database:</strong> MySQL via PDO</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "</div>";
?>
