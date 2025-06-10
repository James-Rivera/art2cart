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
$product_id = $_GET['product_id'] ?? 0;
$order_id = $_GET['order_id'] ?? 0;

if (!$product_id || !$order_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product or order ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Verify that the user owns this order and the product is in the order
    $stmt = $pdo->prepare("
        SELECT p.file_path, p.title, oi.order_id
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE oi.product_id = ? AND oi.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$product_id, $order_id, $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have access to download this product']);
        exit;
    }
    
    $file_path = $result['file_path'];
    $product_title = $result['title'];
    
    // Construct the full file path
    $full_file_path = __DIR__ . '/' . $file_path;
    
    // Check if file exists
    if (!file_exists($full_file_path)) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        exit;
    }
    
    // Update download count
    $update_stmt = $pdo->prepare("UPDATE products SET downloads = downloads + 1 WHERE id = ?");
    $update_stmt->execute([$product_id]);    // Log the download activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $log_stmt = $pdo->prepare("
        INSERT INTO download_logs (user_id, product_id, order_id, download_time, ip_address, user_agent) 
        VALUES (?, ?, ?, NOW(), ?, ?)
    ");
    $log_stmt->execute([$user_id, $product_id, $order_id, $ip_address, $user_agent]);
    
    // Set headers for file download
    $file_info = pathinfo($full_file_path);
    $file_extension = strtolower($file_info['extension'] ?? 'bin');
    
    // Set appropriate content type
    $content_types = [
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'psd' => 'application/octet-stream',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
    ];
    
    $content_type = $content_types[$file_extension] ?? 'application/octet-stream';
    
    // Clean the output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set download headers
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($product_title . '.' . $file_extension) . '"');
    header('Content-Length: ' . filesize($full_file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the file
    readfile($full_file_path);
    exit;
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to download file']);
}

function sanitizeFilename($filename) {
    // Remove or replace invalid characters for filename
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename); // Replace multiple underscores with single
    return trim($filename, '_');
}
?>
