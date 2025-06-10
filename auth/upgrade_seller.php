<?php
require_once '../config/db.php';
require_once '../includes/User.php';

header('Content-Type: application/json');

// Ensure user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to become a seller']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $user = new User($_SESSION['user_id']);
    
    // Check if already a seller
    if ($user->hasRole('seller')) {
        http_response_code(400);
        echo json_encode(['error' => 'You are already a seller']);
        exit;
    }    // Get form data from $_POST since we're handling file uploads
    $data = $_POST;
    
    // Validate required fields
    if (empty($data['name']) || !isset($data['experience']) || empty($data['bio']) || empty($data['portfolio'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Handle government ID file upload
    if (!isset($_FILES['governmentId']) || $_FILES['governmentId']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Government ID is required']);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/government_ids/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename for government ID
    $fileExtension = strtolower(pathinfo($_FILES['governmentId']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.']);
        exit;
    }

    $governmentIdPath = 'uploads/government_ids/' . uniqid() . '_' . $_FILES['governmentId']['name'];
    $fullPath = '../' . $governmentIdPath;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['governmentId']['tmp_name'], $fullPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload government ID']);
        exit;
    }

    // Start transaction
    $db->beginTransaction();    // Create seller application
    $stmt = $db->prepare("
        INSERT INTO seller_applications (
            user_id, 
            name, 
            experience_years, 
            portfolio_url, 
            bio,
            government_id_path,
            application_date,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')
    ");

    $success = $stmt->execute([
        $_SESSION['user_id'],
        $data['name'],
        $data['experience'],
        $data['portfolio'],
        $data['bio'],
        $governmentIdPath
    ]);    if ($success) {
        $db->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Your seller application has been submitted successfully and is pending approval.'
        ]);
    } else {
        // Delete uploaded file if database insert fails
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit seller application']);
    }

} catch (Exception $e) {
    // Delete uploaded file if there's an error
    if (isset($fullPath) && file_exists($fullPath)) {
        unlink($fullPath);
    }
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
