<?php
// Prevent output buffering
ob_start();

// Ensure only JSON responses
header('Content-Type: application/json');

// Prevent PHP errors from corrupting JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../php_errors.log');

// Set upload limits
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

// Clear any existing output
while (ob_get_level()) ob_end_clean();

require_once '../config/db.php';
require_once '../includes/User.php';

// Always set JSON header
header('Content-Type: application/json');

session_start();

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in']);
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('seller')) {
    http_response_code(403);
    echo json_encode(['error' => 'You must be a seller to upload products']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check file upload errors first
if (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $message = match($_FILES['file']['error']) {
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
        default => 'Unknown upload error'
    };
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}

// Check if the request size exceeds post_max_size
if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    http_response_code(413); // Request Entity Too Large
    echo json_encode(['error' => 'The uploaded file exceeds the server\'s maximum allowed size']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Validate request has expected content type
    if (!isset($_SERVER['CONTENT_TYPE']) || !str_contains($_SERVER['CONTENT_TYPE'], 'multipart/form-data')) {
        throw new Exception('Invalid request format. Expected multipart/form-data');
    }
    
    // Get and validate form data
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // Validate inputs
    if (!$title || !$description || !$price || !$categoryId) {
        throw new Exception('Missing or invalid required fields');
    }

    // Handle file uploads
    $uploadDir = '../static/images/products/';
    $filesDir = '../uploads/files/';

    // Create directories if they don't exist
    foreach ([$uploadDir, $filesDir] as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
        if (!is_writable($dir)) {
            throw new Exception("Directory not writable: $dir");
        }
    }

    // Function to handle file upload with validation
    function handleFileUpload($file, $directory, $maxSize, $allowedTypes = null) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $error = match($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds allowed size limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
                default => 'Unknown upload error'
            };
            throw new Exception($error);
        }

        // Check file size
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds limit of ' . ($maxSize / 1024 / 1024) . 'MB');
        }

        // Validate file type if specified
        if ($allowedTypes) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
            }
        }

        // Create safe filename
        $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
        $filePath = $directory . $fileName;
          if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to move uploaded file. Please check directory permissions.');
        }

        // Remove the "../" prefix for storing in database
        return ltrim(str_replace('../', '', $filePath), '/');
    }

    $imagePath = '';
    $filePath = '';// Handle product image
    if (isset($_FILES['image'])) {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $imagePath = handleFileUpload($_FILES['image'], $uploadDir, 10 * 1024 * 1024, $allowedImageTypes);
    }

    // Handle product file
    if (isset($_FILES['file'])) {
        // Allow common file types - extend this list as needed
        $allowedFileTypes = [
            'application/pdf', 
            'application/zip',
            'application/x-zip-compressed',
            'image/jpeg', 
            'image/png',
            'image/gif',
            'image/webp',
            'application/octet-stream'
        ];
        $filePath = handleFileUpload($_FILES['file'], $filesDir, 100 * 1024 * 1024, $allowedFileTypes);
    }

    // Start transaction
    $db->beginTransaction();

    try {
        if ($productId) {
            // Update existing product
            $sql = "UPDATE products SET 
                    title = ?, 
                    description = ?, 
                    price = ?, 
                    category_id = ?";
            
            $params = [$title, $description, $price, $categoryId];
            
            if ($imagePath) {
                $sql .= ", image_path = ?";
                $params[] = $imagePath;
            }
            
            if ($filePath) {
                $sql .= ", file_path = ?";
                $params[] = $filePath;
            }
            
            $sql .= " WHERE id = ? AND seller_id = ?";
            $params[] = $productId;
            $params[] = $_SESSION['user_id'];
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Product not found or you do not have permission to edit it');
            }
        } else {
            // Insert new product
            $stmt = $db->prepare("
                INSERT INTO products (title, description, price, category_id, seller_id, image_path, file_path, status, downloads)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0)
            ");
            
            $stmt->execute([
                $title,
                $description,
                $price,
                $categoryId,
                $_SESSION['user_id'],
                $imagePath,
                $filePath
            ]);
        }

        $db->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
