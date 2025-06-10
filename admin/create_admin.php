<?php
require_once '../config/db.php';

$password = 'admin123';
$email = 'admin@art2cart.com';
$username = 'admin';

// Generate password hash
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $db = Database::getInstance()->getConnection();
      // First, check if admin user exists
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND username = ?');
    $stmt->execute([$email, $username]);
    
    if ($stmt->fetch()) {
        // Update existing admin's password
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ? AND username = ?');
        $stmt->execute([$password_hash, $email, $username]);
        echo "Admin password updated successfully!\n";
    } else {
        // Create new admin user
        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $password_hash]);
        
        $userId = $db->lastInsertId();
        
        // Assign admin role
        $stmt = $db->prepare('INSERT INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE name = ?');
        $stmt->execute([$userId, 'admin']);
        
        echo "Admin user created successfully!\n";
    }
    
    echo "\nYou can now login with:\n";
    echo "Email: admin@art2cart.com\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
