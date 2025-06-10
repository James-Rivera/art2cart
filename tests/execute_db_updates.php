<?php
// Execute database updates for Art2Cart shopping cart functionality

require_once 'config/db.php';

echo "Starting database updates...\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Read and execute cart_table.sql
    echo "Executing cart_table.sql...\n";
    $cartTableSQL = file_get_contents('config/cart_table.sql');
    if ($cartTableSQL) {
        $statements = explode(';', $cartTableSQL);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "✓ Cart table statement executed successfully\n";
                } catch (PDOException $e) {
                    // Ignore "table already exists" and "column already exists" errors
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate column') === false) {
                        echo "! Cart table warning: " . $e->getMessage() . "\n";
                    } else {
                        echo "✓ Cart table already exists or column already added\n";
                    }
                }
            }
        }
    }
    
    // Read and execute update_cart_functionality.sql
    echo "Executing update_cart_functionality.sql...\n";
    $updateSQL = file_get_contents('config/update_cart_functionality.sql');
    if ($updateSQL) {
        $statements = explode(';', $updateSQL);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !strpos($statement, '--')) {
                try {
                    $pdo->exec($statement);
                    echo "✓ Update statement executed successfully\n";
                } catch (PDOException $e) {
                    // Ignore "column already exists" errors
                    if (strpos($e->getMessage(), 'Duplicate column') === false && 
                        strpos($e->getMessage(), 'already exists') === false) {
                        echo "! Update warning: " . $e->getMessage() . "\n";
                    } else {
                        echo "✓ Column already exists or table already updated\n";
                    }
                }
            }
        }
    }
    
    // Verify the updates
    echo "\nVerifying database structure...\n";
    
    // Check cart table
    $result = $pdo->query("SHOW TABLES LIKE 'cart'");
    if ($result->rowCount() > 0) {
        echo "✓ Cart table exists\n";
    } else {
        echo "✗ Cart table missing\n";
    }
    
    // Check orders table billing_info column
    $result = $pdo->query("SHOW COLUMNS FROM orders LIKE 'billing_info'");
    if ($result->rowCount() > 0) {
        echo "✓ Orders table has billing_info column\n";
    } else {
        echo "✗ Orders table missing billing_info column\n";
    }
    
    // Check order_items table quantity column
    $result = $pdo->query("SHOW COLUMNS FROM order_items LIKE 'quantity'");
    if ($result->rowCount() > 0) {
        echo "✓ Order_items table has quantity column\n";
    } else {
        echo "✗ Order_items table missing quantity column\n";
    }
    
    echo "\nDatabase updates completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>