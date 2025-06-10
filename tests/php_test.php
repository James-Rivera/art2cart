<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "\n";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "\n";

if (extension_loaded('pdo')) {
    echo "Available PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
}

// Test basic database connection
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "Basic MySQL connection: Success\n";
    $pdo = null;
} catch (Exception $e) {
    echo "Basic MySQL connection failed: " . $e->getMessage() . "\n";
}
?>
