<?php
require_once 'config/db.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get some actual products from the database
    $result = $pdo->query("SELECT id, title, price, image_path FROM products LIMIT 5");
    $products = $result->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
