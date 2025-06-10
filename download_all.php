<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to download products']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Verify that the user owns this order and get all products in the order
    $stmt = $pdo->prepare("
        SELECT p.file_path, p.title, p.id as product_id, oi.quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have access to download products from this order']);
        exit;
    }
    
    // Create a temporary ZIP file
    $zip = new ZipArchive();
    $zip_filename = 'order_' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . '_' . time() . '.zip';
    $temp_zip_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zip_filename;
    
    if ($zip->open($temp_zip_path, ZipArchive::CREATE) !== TRUE) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create ZIP file']);
        exit;
    }
    
    $files_added = 0;
    $missing_files = [];
    
    foreach ($products as $product) {
        $file_path = $product['file_path'];
        $product_title = $product['title'];
        $product_id = $product['product_id'];
        
        // Construct the full file path
        $full_file_path = __DIR__ . '/' . $file_path;
        
        if (file_exists($full_file_path)) {
            $file_info = pathinfo($full_file_path);
            $file_extension = $file_info['extension'] ?? 'bin';
            
            // Create a safe filename for the ZIP
            $safe_filename = sanitizeFilename($product_title) . '.' . $file_extension;
            
            // Add file to ZIP
            $zip->addFile($full_file_path, $safe_filename);
            $files_added++;
            
            // Update download count
            $update_stmt = $pdo->prepare("UPDATE products SET downloads = downloads + 1 WHERE id = ?");
            $update_stmt->execute([$product_id]);
              // Log the download activity
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $log_stmt = $pdo->prepare("
                INSERT INTO download_logs (user_id, product_id, order_id, download_time, ip_address, user_agent) 
                VALUES (?, ?, ?, NOW(), ?, ?)
            ");
            $log_stmt->execute([$user_id, $product_id, $order_id, $ip_address, $user_agent]);
            
        } else {
            $missing_files[] = $product_title;
        }
    }
    
    if ($files_added == 0) {
        $zip->close();
        unlink($temp_zip_path);
        http_response_code(404);
        echo json_encode(['error' => 'No files found to download']);
        exit;
    }
    
    // Add a README file if there are missing files
    if (!empty($missing_files)) {
        $readme_content = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " - Download Information\n\n";
        $readme_content .= "Downloaded files: " . $files_added . "\n";
        $readme_content .= "Missing files: " . count($missing_files) . "\n\n";
        $readme_content .= "The following files could not be included:\n";
        foreach ($missing_files as $missing_file) {
            $readme_content .= "- " . $missing_file . "\n";
        }
        $readme_content .= "\nPlease contact support if you need assistance with missing files.";
        
        $zip->addFromString('README.txt', $readme_content);
    }
    
    $zip->close();
    
    // Clean the output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set download headers
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($temp_zip_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the ZIP file
    readfile($temp_zip_path);
    
    // Clean up the temporary file
    unlink($temp_zip_path);
    exit;
    
} catch (Exception $e) {
    error_log("Download all error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to download files']);
}

function sanitizeFilename($filename) {
    // Remove or replace invalid characters for filename
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename); // Replace multiple underscores with single
    return trim($filename, '_');
}
?>
