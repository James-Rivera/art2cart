<?php
header('Content-Type: application/json');
session_start();

require_once '../config/db.php';
require_once '../includes/products.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to rate products']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$productService = new ProductService();
$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'POST':
            // Submit or update rating
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['product_id']) || !isset($input['rating'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID and rating are required']);
                exit;
            }
            
            $productId = (int)$input['product_id'];
            $rating = (float)$input['rating'];
            $comment = isset($input['comment']) ? trim($input['comment']) : null;
            
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                http_response_code(400);
                echo json_encode(['error' => 'Rating must be between 1 and 5']);
                exit;
            }
            
            // Check if user has purchased the product
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
            ");
            $stmt->execute([$userId, $productId]);
            $hasPurchased = $stmt->fetch()['count'] > 0;
            
            if (!$hasPurchased) {
                http_response_code(403);
                echo json_encode(['error' => 'You can only rate products you have purchased']);
                exit;
            }
            
            // Submit rating
            if ($productService->submitRating($productId, $userId, $rating, $comment)) {
                // Get updated ratings
                $ratings = $productService->getProductRatings($productId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Rating submitted successfully',
                    'ratings' => $ratings
                ]);
            } else {                http_response_code(500);
                echo json_encode(['error' => 'Failed to submit rating']);
            }
            break;
            
        case 'PUT':
            // Update rating
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['rating_id']) || !isset($input['product_id']) || !isset($input['rating'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Rating ID, Product ID and rating are required']);
                exit;
            }
            
            $ratingId = (int)$input['rating_id'];
            $productId = (int)$input['product_id'];
            $rating = (float)$input['rating'];
            $comment = isset($input['comment']) ? trim($input['comment']) : null;
            
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                http_response_code(400);
                echo json_encode(['error' => 'Rating must be between 1 and 5']);
                exit;
            }
            
            // Update rating (includes ownership check in the method)
            if ($productService->updateUserRating($ratingId, $userId, $rating, $comment)) {
                // Get updated ratings
                $ratings = $productService->getProductRatings($productId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Rating updated successfully',
                    'ratings' => $ratings
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update rating']);
            }
            break;
            
        case 'DELETE':
            // Delete rating
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['rating_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Rating ID is required']);
                exit;
            }
            
            $ratingId = (int)$input['rating_id'];
            
            if ($productService->deleteUserRating($ratingId, $userId)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Rating deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete rating']);
            }
            break;
            
        case 'GET':
            // Get user's ratings
            if (isset($_GET['user_ratings'])) {
                $ratings = $productService->getUserRatings($userId);
                echo json_encode(['ratings' => $ratings]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Rating API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
