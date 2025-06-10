<?php
require_once 'config/db.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check existing users
    $result = $pdo->query("SELECT id, username, email FROM users LIMIT 5");
    $users = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Existing Users:</h2>";
    if (empty($users)) {
        echo "<p>No users found. You may need to create a test user.</p>";
        echo "<a href='auth/signup.php'>Create Account</a>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['id']}</td><td>{$user['username']}</td><td>{$user['email']}</td></tr>";
        }
        echo "</table>";
        echo "<p><a href='auth/auth.html'>Login</a></p>";
    }
    
    // Check cart table
    $result = $pdo->query("SELECT COUNT(*) as count FROM cart");
    $cartCount = $result->fetch()['count'];
    echo "<h2>Cart Items: {$cartCount}</h2>";
    
    // Check products
    $result = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $result->fetch()['count'];
    echo "<h2>Products: {$productCount}</h2>";
    
    // Check current session
    session_start();
    echo "<h2>Session Status:</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p>Logged in as User ID: {$_SESSION['user_id']}</p>";
        echo "<p><a href='auth/logout.php'>Logout</a></p>";
    } else {
        echo "<p>Not logged in</p>";
        echo "<p><a href='auth/auth.html'>Login</a></p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
a { color: #007cba; text-decoration: none; padding: 5px 10px; background: #f0f0f0; border-radius: 3px; }
</style>
