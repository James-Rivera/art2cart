<?php
require_once '../../config/db.php';
require_once '../../includes/User.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get application ID
$applicationId = $_GET['id'] ?? null;

if (!$applicationId || !is_numeric($applicationId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid application ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();    // Get seller application details with user information
    $stmt = $db->prepare("
        SELECT 
            sa.*,
            u.username,
            u.email,
            u.email_verified,
            u.created_at as user_created_at
        FROM seller_applications sa
        JOIN users u ON sa.user_id = u.id
        WHERE sa.id = ?
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        http_response_code(404);
        echo json_encode(['error' => 'Application not found']);
        exit;
    }
      // Set default for last_login since it doesn't exist in this schema
    $application['last_login'] = null;
      // Fix government ID path for proper web access
    if (!empty($application['government_id_path'])) {
        $path = $application['government_id_path'];
          // Normalize path separators
        $path = str_replace('\\', '/', $path);
        
        // Remove any leading ../ or ./ using safer string replacement
        $path = str_replace('../', '', $path);
        $path = str_replace('./', '', $path);
        
        // If path doesn't start with uploads/, add it
        if (!str_starts_with($path, 'uploads/')) {
            // Check if it's just a filename (no directory separator)
            if (strpos($path, '/') === false) {
                $path = 'uploads/government_ids/' . $path;
            } else {
                $path = 'uploads/' . ltrim($path, '/');
            }
        }        // Build the URL relative to the base href (root of Art2Cart)
        // URL encode the filename part to handle spaces and special characters
        $pathParts = explode('/', $path);
        $fileName = array_pop($pathParts);
        $encodedFileName = rawurlencode($fileName);
        $pathParts[] = $encodedFileName;
        $application['government_id_url'] = implode('/', $pathParts);
        
        // Check if file actually exists
        $fullPath = dirname(dirname(__DIR__)) . '/' . $path;
        $application['file_exists'] = file_exists($fullPath);
        
        // Add debug information
        $application['debug_info'] = [
            'original_path' => $application['government_id_path'],
            'processed_path' => $path,
            'full_path' => $fullPath,
            'url' => $application['government_id_url']
        ];
    } else {
        $application['government_id_url'] = null;
        $application['file_exists'] = false;
        $application['debug_info'] = ['message' => 'No government ID path provided'];
    }
    
    // Get additional user stats
    try {
        // Count user's products (if any)
        $stmt = $db->prepare("SELECT COUNT(*) as product_count FROM products WHERE seller_id = ?");
        $stmt->execute([$application['user_id']]);
        $productStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $application['product_count'] = $productStats['product_count'] ?? 0;
        
        // Count user's orders (if any)
        $stmt = $db->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
        $stmt->execute([$application['user_id']]);
        $orderStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $application['order_count'] = $orderStats['order_count'] ?? 0;
        
    } catch (Exception $e) {
        // If tables don't exist, set defaults
        $application['product_count'] = 0;
        $application['order_count'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'application' => $application
    ]);
    
} catch (Exception $e) {
    error_log("Seller Details API Error: " . $e->getMessage());
    error_log("SQL State: " . ($e->getCode() ? $e->getCode() : 'N/A'));
    error_log("Application ID: " . $applicationId);
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
