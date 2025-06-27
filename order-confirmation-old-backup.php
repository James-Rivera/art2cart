<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Art2CartConfig.php';

// Get base URL configuration
$baseUrl = Art2CartConfig::getBaseUrl();

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
if (!$loggedIn) {
    header('Location: ' . $baseUrl . 'auth/auth.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    header('Location: ' . $baseUrl . 'catalogue.php');
    exit;
}

// Initialize database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Fetch order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username as customer_name,
               ba.first_name, ba.last_name, ba.email, ba.phone,
               ba.address, ba.city, ba.postal_code, ba.country, ba.payment_method
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN billing_addresses ba ON o.id = ba.order_id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$order) {
        header('Location: ' . $baseUrl . 'catalogue.php');
        exit;
    }
    
    // Mark order as completed when user views confirmation page
    if ($order['status'] === 'pending') {
        $update_stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ?");
        $update_stmt->execute([$order_id, $user_id]);
        
        // Update the order array to reflect the new status
        $order['status'] = 'completed';
        
        error_log("Order $order_id marked as completed for user $user_id");
    }
      
    // Fetch order items
    $items_stmt = $pdo->prepare("
        SELECT oi.*, p.title as product_name, p.file_path, 
               cat.name as category_name, seller.username as seller_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories cat ON p.category_id = cat.id
        JOIN users seller ON p.seller_id = seller.id
        WHERE oi.order_id = ?
    ");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Billing info is now directly available in $order array from the JOIN
    
} catch (PDOException $e) {
    error_log("Order fetch error: " . $e->getMessage());
    header('Location: ' . $baseUrl . 'catalogue.php');
    exit;
}

$pageTitle = "Order Confirmation - Art2Cart";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title><?php echo $pageTitle; ?></title>    <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" />
    <link rel="stylesheet" href="static/css/order-confirmation.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <style>
        /* Additional styles to match the design */
        .main-content {
            margin-top: 89px;
            padding: 40px 0;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23f3f4f6' fill-opacity='0.4' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
        
        /* Image path fixes */
        .item-image-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include 'static/templates/header_new.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="order-confirmation-container">
            <div class="checkmark-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p class="confirmation-message">Thank you for your purchase. Your order has been successfully processed.</p>

            <div class="order-details-container">
                <!-- Left Column -->
                <div class="order-details-left">
                    <!-- Order Confirmation Section -->
                    <div class="order-confirmation-section">
                        <div class="section-header">
                            <i class="fas fa-calendar-alt"></i>
                            <h2>Order Confirmation</h2>
                        </div>
                        <div class="order-info">
                            <div class="order-info-row">
                                <div class="order-info-label">Order Number</div>
                                <div class="order-info-value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            </div>
                            <div class="order-info-row">
                                <div class="order-info-label">Order Date</div>
                                <div class="order-info-value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                            </div>
                            <div class="order-info-row">
                                <div class="order-info-label">Estimated Delivery</div>
                                <div class="order-info-value">Digital item available immediately</div>
                            </div>
                        </div>
                    </div>

                    <!-- Item Purchase Section -->
                    <div class="item-purchase-section">
                        <div class="section-header">
                            <i class="fas fa-shopping-bag"></i>
                            <h2>Item Purchase</h2>
                        </div>
                        
                        <?php foreach ($order_items as $item): ?>
                            <div class="purchase-item">
                                <div class="item-image-container">
                                    <?php 
                                    $imagePath = $item['file_path'] ?? '';                                    // If it's an uploaded file (starts with 'uploads/') or static file (starts with 'static/')
                                    if (strpos($imagePath, 'uploads/') === 0 || strpos($imagePath, 'static/') === 0) {
                                        $displayPath = $baseUrl . $imagePath;
                                    } 
                                    // If it already starts with the base URL
                                    else if (strpos($imagePath, $baseUrl) === 0) {
                                        $displayPath = $imagePath;
                                    }
                                    // Default fallback image
                                    else {
                                        $displayPath = $baseUrl . 'static/images/products/sample.jpg';
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($displayPath); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                                </div>                                <div class="item-details">
                                    <h3 class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <p class="item-artist">by <?php echo htmlspecialchars($item['seller_name']); ?></p>
                                </div>
                                <div class="item-price">
                                    <p>₱<?php echo number_format($item['price'], 2); ?></p>
                                </div><div class="item-download">
                                    <button class="download-btn" onclick="downloadProduct(<?php echo $item['product_id']; ?>, <?php echo $order['id']; ?>)">
                                        <i class="fas fa-download"></i>
                                        Download
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="order-details-right">
                    <div class="order-summary-section">
                        <h2 class="summary-title">Order Summary</h2>
                        
                        <div class="summary-row">
                            <div class="summary-label">Subtotal</div>
                            <div class="summary-value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                        
                        <div class="summary-row">
                            <div class="summary-label">Items</div>
                            <div class="summary-value"><?php echo count($order_items); ?></div>
                        </div>
                        
                        <div class="summary-row">
                            <div class="summary-label">Tax</div>
                            <div class="summary-value">₱0.00</div>
                        </div>
                        
                        <div class="summary-row total-row">
                            <div class="summary-label">Total</div>
                            <div class="summary-value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                        
                        <button class="download-all-btn" onclick="downloadAllItems(<?php echo $order['id']; ?>)">Download All Items</button>                        <button class="continue-shopping-btn" onclick="window.location.href='<?php echo $baseUrl; ?>catalogue.php'">Continue Shopping</button>
                        
                        <div class="help-text">
                            Need help? <a href="<?php echo $baseUrl; ?>contact" class="support-link">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'static/templates/footer_new.html'; ?>
    
    <script>
    function downloadProduct(productId, orderId) {
        // Show loading state
        const button = event.target.closest('.download-btn');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';
        button.disabled = true;
        
        // Create a temporary anchor element for download
        const link = document.createElement('a');
        link.href = `/Art2Cart/download.php?product_id=${productId}&order_id=${orderId}`;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Reset button after a short delay
        setTimeout(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        }, 2000);
    }
    
    function downloadAllItems(orderId) {
        // Show loading state
        const button = event.target;
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing Download...';
        button.disabled = true;
        
        // Create a temporary anchor element for download
        const link = document.createElement('a');
        link.href = `/Art2Cart/download_all.php?order_id=${orderId}`;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Reset button after a short delay
        setTimeout(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        }, 3000);
    }
    
    // Add some visual feedback when downloads complete
    document.addEventListener('DOMContentLoaded', function() {
        // Check if this is a fresh order confirmation (from URL parameter)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('fresh') === '1') {
            // Show a welcome message or animation
            const container = document.querySelector('.order-confirmation-container');
            if (container) {
                container.classList.add('fresh-order');
                // Remove the parameter from URL without page reload
                const newUrl = window.location.pathname + '?order_id=' + urlParams.get('order_id');
                window.history.replaceState({}, '', newUrl);
            }
        }
    });
    </script>
    
    <style>
    .download-btn:disabled,
    .download-all-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .fresh-order {
        animation: fadeInUp 0.8s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fas.fa-spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    </style>
</body>
</html>
