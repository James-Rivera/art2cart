<?php
require_once 'config/db.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Check if cart table exists
    $result = $db->query("SHOW TABLES LIKE 'cart'");
    if ($result->rowCount() > 0) {
        echo "✓ Cart table exists\n";
    } else {
        echo "✗ Cart table does not exist\n";
    }
    
    // Check orders table structure
    $result = $db->query("DESCRIBE orders");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('billing_info', $columns)) {
        echo "✓ Orders table has billing_info column\n";
    } else {
        echo "✗ Orders table missing billing_info column\n";
    }
    
    // Check order_items table structure
    $result = $db->query("DESCRIBE order_items");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('quantity', $columns)) {
        echo "✓ Order_items table has quantity column\n";
    } else {
        echo "✗ Order_items table missing quantity column\n";
    }
    
    echo "\nAll tables:\n";
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }
    
} catch(PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
