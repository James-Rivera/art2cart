<?php
// Script to fix NULL user_id values in products table
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update products with NULL user_id to use admin user (ID 1)
    $stmt = $pdo->prepare("
        UPDATE products 
        SET user_id = 1 
        WHERE user_id IS NULL
    ");
    $stmt->execute();
    $affectedRows = $stmt->rowCount();
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Updated $affectedRows products with NULL user_id",
        'affected_rows' => $affectedRows
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
