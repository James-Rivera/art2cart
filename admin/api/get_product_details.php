<?php
// Disable error output to prevent HTML from breaking JSON response
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../config/db.php';
require_once '../../includes/User.php';
require_once '../../includes/Art2CartConfig.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get base URL
$baseHref = Art2CartConfig::getBaseUrl();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $productId = (int)$_GET['id'];
    
    $stmt = $db->prepare("
        SELECT p.*, u.username as seller_name, u.email as seller_email 
        FROM products p 
        LEFT JOIN users u ON p.seller_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);    if ($product) {
        // Fix file path for proper web access (the actual product file to review)
        $fileUrl = '';
        $fileExists = false;
        
        if (!empty($product['file_path'])) {
            $path = $product['file_path'];
            
            // Normalize path separators
            $path = str_replace('\\', '/', $path);
            
            // Remove any leading ../ or ./ using safer string replacement
            $path = str_replace('../', '', $path);
            $path = str_replace('./', '', $path);
            
            // Build the URL relative to the base href (root of Art2Cart)
            // URL encode the filename part to handle spaces and special characters
            $pathParts = explode('/', $path);
            $fileName = array_pop($pathParts);
            $encodedFileName = rawurlencode($fileName);
            $pathParts[] = $encodedFileName;
            $fileUrl = implode('/', $pathParts);
            
            // Check if file actually exists
            $fullPath = dirname(dirname(__DIR__)) . '/' . $path;
            $fileExists = file_exists($fullPath);
        }
        
        // Also handle preview image path for reference
        $imageUrl = '';
        $imageExists = false;
        
        if (!empty($product['image_path'])) {
            $path = $product['image_path'];
            
            // Normalize path separators
            $path = str_replace('\\', '/', $path);
            
            // Remove any leading ../ or ./ using safer string replacement
            $path = str_replace('../', '', $path);
            $path = str_replace('./', '', $path);
            
            // Build the URL relative to the base href (root of Art2Cart)
            // URL encode the filename part to handle spaces and special characters
            $pathParts = explode('/', $path);
            $fileName = array_pop($pathParts);
            $encodedFileName = rawurlencode($fileName);
            $pathParts[] = $encodedFileName;
            $imageUrl = implode('/', $pathParts);
            
            // Check if file actually exists
            $fullPath = dirname(dirname(__DIR__)) . '/' . $path;
            $imageExists = file_exists($fullPath);
        }
          echo json_encode([
            'success' => true,
            'base_url' => $baseHref,
            'product' => [
                'id' => $product['id'],
                'title' => $product['title'],
                'description' => $product['description'],
                'price' => number_format($product['price'], 2),
                'image_path' => $product['image_path'],
                'image_url' => $baseHref . $imageUrl,
                'image_exists' => $imageExists,
                'file_path' => $product['file_path'],
                'file_url' => $baseHref . $fileUrl,
                'file_exists' => $fileExists,
                'seller_name' => $product['seller_name'] ?: 'Unknown',
                'seller_email' => $product['seller_email'] ?: 'No email',
                'created_at' => $product['created_at'], // Raw date for JavaScript parsing
                'created_at_formatted' => date('F j, Y \a\t g:i A', strtotime($product['created_at'])), // Pre-formatted date
                'category_id' => $product['category_id'] ?: 'Uncategorized',
                'tags' => $product['tags'] ?: '',
                'review_status' => $product['review_status'] ?: 'pending',
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => "Product with ID $productId not found"]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
