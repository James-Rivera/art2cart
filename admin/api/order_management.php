<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

require_once '../../config/db.php';

// Set proper JSON header
header('Content-Type: application/json');

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Try to get database connection
    $db = Database::getInstance();
    if (!$db) {
        throw new Exception('Database instance failed');
    }
    
    $pdo = $db->getConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    if ($method === 'GET') {
        // Get order details
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required']);
            exit;
        }
        
        $orderId = intval($_GET['id']);
        
        // Get order and billing information
        $stmt = $pdo->prepare("
            SELECT 
                o.*,
                u.username,
                u.email as user_email,
                ba.first_name,
                ba.last_name,
                ba.email as billing_email,
                ba.phone,
                ba.address,
                ba.city,
                ba.state_province,
                ba.postal_code,
                ba.country,
                ba.payment_method,
                CONCAT(ba.first_name, ' ', ba.last_name) as customer_name
            FROM orders o
            INNER JOIN users u ON o.user_id = u.id
            LEFT JOIN billing_addresses ba ON o.id = ba.order_id
            WHERE o.id = ?
        ");
        
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        
        // Get order items
        $items_stmt = $pdo->prepare("
            SELECT 
                oi.*,
                p.title,
                p.image_path,
                p.file_path,
                c.name as category_name,
                s.username as seller_name
            FROM order_items oi
            INNER JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users s ON p.seller_id = s.id
            WHERE oi.order_id = ?
            ORDER BY oi.created_at
        ");
        
        $items_stmt->execute([$orderId]);
        $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
          // Format dates
        $order['formatted_date'] = date('F j, Y \a\t g:i A', strtotime($order['created_at']));
        // Remove updated_at formatting since the column doesn't exist in this schema
        
        echo json_encode([
            'success' => true,
            'order' => $order
        ]);
        
    } elseif ($method === 'POST') {
        // Update order status
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['orderId']) || !isset($input['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID and status are required']);
            exit;
        }
        
        $orderId = intval($input['orderId']);
        $status = $input['status'];
        $notes = $input['notes'] ?? null;
        
        // Validate status
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }
          // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $orderId]);
        
        if (!$result) {
            throw new Exception('Failed to update order status');
        }
        
        // Check if any rows were affected
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        
        // Log the status change if notes are provided
        if ($notes) {
            $log_stmt = $pdo->prepare("
                INSERT INTO order_status_logs (order_id, status, notes, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            // This will fail silently if the table doesn't exist
            try {
                $log_stmt->execute([$orderId, $status, $notes]);
            } catch (Exception $e) {
                // Table doesn't exist, that's OK
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Order Management API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("Order ID: " . ($orderId ?? 'N/A'));
    error_log("Method: " . $method);
    
    // Make sure we only output JSON
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'debug' => $e->getMessage() // Remove this in production
    ]);
} catch (PDOException $e) {
    error_log("Database Error in Order Management: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'debug' => $e->getMessage() // Remove this in production
    ]);
}
?>
