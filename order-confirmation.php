<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Art2CartConfig.php';

// Get base URL configuration
$baseHref = Art2CartConfig::getBaseUrl();
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

// Fetch order details with billing information using JOIN
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            u.username as customer_name,
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
            CONCAT(ba.first_name, ' ', ba.last_name) as full_name
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
    
    // Handle legacy orders that might not have billing addresses yet
    if (!$order['first_name']) {
        // Try to get billing info from old JSON format if it still exists
        if (isset($order['billing_info']) && !empty($order['billing_info'])) {
            $billing_info = json_decode($order['billing_info'], true);
            if ($billing_info) {
                $order['first_name'] = $billing_info['first_name'] ?? 'Unknown';
                $order['last_name'] = $billing_info['last_name'] ?? 'Customer';
                $order['billing_email'] = $billing_info['email'] ?? '';
                $order['phone'] = $billing_info['phone'] ?? '';
                $order['address'] = $billing_info['address'] ?? '';
                $order['city'] = $billing_info['city'] ?? '';
                $order['postal_code'] = $billing_info['postal_code'] ?? '';
                $order['country'] = $billing_info['country'] ?? '';
                $order['payment_method'] = $billing_info['payment_method'] ?? 'unknown';
                $order['full_name'] = $order['first_name'] . ' ' . $order['last_name'];
            }
        } else {
            // Default values for orders without billing info
            $order['first_name'] = 'Unknown';
            $order['last_name'] = 'Customer';
            $order['full_name'] = 'Unknown Customer';
            $order['billing_email'] = '';
            $order['payment_method'] = 'unknown';
        }
    }

    // Mark order as completed when user views confirmation page (for digital products)
    if ($order['status'] === 'pending') {
        $update_stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ?");
        $update_stmt->execute([$order_id, $user_id]);
        
        // Update the order array to reflect the new status
        $order['status'] = 'completed';
        
        error_log("Order $order_id marked as completed for user $user_id");
    }
      
    // Fetch order items with enhanced error handling
    $items_stmt = $pdo->prepare("
        SELECT 
            oi.*,
            p.title as product_name, 
            p.file_path, 
            p.image_path,
            cat.name as category_name, 
            seller.username as seller_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories cat ON p.category_id = cat.id
        JOIN users seller ON p.seller_id = seller.id
        WHERE oi.order_id = ?
        ORDER BY oi.created_at
    ");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($order_items)) {
        error_log("Warning: Order $order_id has no items");
    }
    
} catch (PDOException $e) {
    error_log("Order fetch error: " . $e->getMessage());    header('Location: ' . $baseUrl . 'catalogue.php');
    exit;
}

$pageTitle = "Order Confirmation - Art2Cart";
?>
<!DOCTYPE html>
<html lang="en">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Base URL configuration -->
    <?php Art2CartConfig::echoBaseHref(); ?>
    
    <!-- Standard favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="static/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="static/images/favicon/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="96x96" href="static/images/favicon/favicon-96x96.png">
    
    <!-- ICO fallback for older browsers -->
    <link rel="shortcut icon" href="static/images/favicon/favicon.ico" type="image/x-icon">

    <!-- Apple Touch Icon (iOS/iPadOS) -->
    <link rel="apple-touch-icon" sizes="180x180" href="static/images/favicon/apple-touch-icon.png">

    <!-- Android/Chrome -->
    <link rel="icon" type="image/png" sizes="192x192" href="static/images/favicon/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="static/images/favicon/android-chrome-512x512.png">

    <!-- Web Manifest for PWA support -->
    <link rel="manifest" href="static/images/favicon/site.webmanifest">

    <!-- Optional theme color -->
    <meta name="theme-color" content="#ffffff">
      <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" />
    <link rel="stylesheet" href="static/css/template/header.css" />
    <link rel="stylesheet" href="static/css/order-confirmation.css" /><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <script>
        window.baseHref = '<?php echo rtrim($baseHref, '/') . '/'; ?>';
    </script>
      
    <style>
        /* Additional styles for billing information display */
        .billing-info-section {
            background: #FFFFFF;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            padding: 24px;
            margin-top: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .billing-info-section h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 18px;
            color: #000;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .billing-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .billing-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .billing-info-label {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 500;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .billing-info-value {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        
        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #FFD700;
            color: #000;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        @media (max-width: 768px) {
            .billing-info-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Error state styles */
        .order-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            color: #DC2626;
        }
        
        .order-warning {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            color: #D97706;
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

            <?php if (empty($order_items)): ?>
                <div class="order-error">
                    <strong>Notice:</strong> This order appears to have no items. Please contact support if you believe this is an error.
                </div>
            <?php endif; ?>

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
                                <div class="order-info-label">Status</div>
                                <div class="order-info-value">
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="order-info-row">
                                <div class="order-info-label">Estimated Delivery</div>
                                <div class="order-info-value">Digital item available immediately</div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information Section -->
                    <div class="billing-info-section">
                        <h3>
                            <i class="fas fa-credit-card"></i>
                            Billing Information
                        </h3>
                        <div class="billing-info-grid">
                            <div class="billing-info-item">
                                <div class="billing-info-label">Full Name</div>
                                <div class="billing-info-value"><?php echo htmlspecialchars($order['full_name']); ?></div>
                            </div>
                            <div class="billing-info-item">
                                <div class="billing-info-label">Email</div>
                                <div class="billing-info-value"><?php echo htmlspecialchars($order['billing_email'] ?: $order['customer_name']); ?></div>
                            </div>
                            <?php if (!empty($order['phone'])): ?>
                            <div class="billing-info-item">
                                <div class="billing-info-label">Phone</div>
                                <div class="billing-info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="billing-info-item">
                                <div class="billing-info-label">Payment Method</div>
                                <div class="billing-info-value">
                                    <span class="payment-method-badge">
                                        <i class="fas fa-<?php echo $order['payment_method'] === 'paypal' ? 'paypal' : ($order['payment_method'] === 'gcash' ? 'mobile-alt' : 'credit-card'); ?>"></i>
                                        <?php echo ucfirst($order['payment_method']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (!empty($order['address'])): ?>
                            <div class="billing-info-item" style="grid-column: 1 / -1;">
                                <div class="billing-info-label">Billing Address</div>
                                <div class="billing-info-value">
                                    <?php echo htmlspecialchars($order['address']); ?><br>
                                    <?php echo htmlspecialchars($order['city']); ?>
                                    <?php if (!empty($order['state_province'])): ?>, <?php echo htmlspecialchars($order['state_province']); ?><?php endif; ?>
                                    <?php echo htmlspecialchars($order['postal_code']); ?><br>
                                    <?php echo htmlspecialchars($order['country']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Item Purchase Section -->
                    <?php if (!empty($order_items)): ?>
                    <div class="item-purchase-section">
                        <div class="section-header">
                            <i class="fas fa-shopping-bag"></i>
                            <h2>Item Purchase</h2>
                        </div>
                        
                        <?php foreach ($order_items as $item): ?>
                            <div class="purchase-item">
                                <div class="item-image-container">
                                    <?php 
                                    $imagePath = $item['image_path'] ?? '';                                    // Handle different image path formats
                                    if (empty($imagePath)) {
                                        $displayPath = rtrim($baseHref, '/') . '/' . 'static/images/products/sample.jpg';
                                    } else {
                                        // Clean up path and make it relative to base
                                        $imagePath = ltrim($imagePath, '/');
                                        if (strpos($imagePath, 'Art2Cart/') === 0) {
                                            $imagePath = substr($imagePath, 9); // Remove 'Art2Cart/'
                                        }
                                        $displayPath = rtrim($baseHref, '/') . '/' . $imagePath;
                                    }
                                    ?>                                    <img src="<?php echo htmlspecialchars($displayPath); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         class="item-image"
                                         onerror="this.src='<?php echo rtrim($baseHref, '/') . '/'; ?>static/images/products/sample.jpg';">>
                                </div>
                                
                                <div class="item-details">
                                    <h3 class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <p class="item-artist">by <?php echo htmlspecialchars($item['seller_name']); ?></p>
                                    <p class="item-category"><?php echo htmlspecialchars($item['category_name']); ?></p>
                                </div>
                                
                                <div class="item-price">
                                    <p>₱<?php echo number_format($item['price'], 2); ?></p>
                                </div>

                                <div class="item-download">
                                    <button class="download-btn" onclick="downloadProduct(<?php echo $item['product_id']; ?>, <?php echo $order['id']; ?>)">
                                        <i class="fas fa-download"></i>
                                        Download
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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
                        
                        <?php if (!empty($order_items)): ?>
                        <button class="download-all-btn" onclick="downloadAllItems(<?php echo $order['id']; ?>)">Download All Items</button>
                        <?php endif; ?>                          <button class="continue-shopping-btn" onclick="window.location.href='<?php echo rtrim($baseUrl, '/') . '/'; ?>catalogue.php'">Continue Shopping</button>
                        
                        <div class="help-text">
                            Need help? <a href="<?php echo rtrim($baseUrl, '/') . '/'; ?>contact" class="support-link">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'static/templates/footer_new.php'; ?>
    
    <script>
    function downloadProduct(productId, orderId) {
        // Show loading state
        const button = event.target.closest('.download-btn');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';
        button.disabled = true;
          // Create a temporary anchor element for download
        const link = document.createElement('a');
        link.href = `${window.baseHref}download.php?product_id=${productId}&order_id=${orderId}`;
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
        link.href = `${window.baseHref}download_all.php?order_id=${orderId}`;
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
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-completed {
        background: #D1FAE5;
        color: #065F46;
    }
    
    .status-pending {
        background: #FEF3C7;
        color: #92400E;
    }
    
    .status-cancelled {
        background: #FEE2E2;
        color: #991B1B;
    }
    
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
    }    </style>

    <!-- Rating Modal -->
    <div id="ratingModal" class="rating-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeRatingModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rate Your Purchase</h3>
                <button class="close-btn" onclick="closeRatingModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Help other customers by rating the products you purchased:</p>
                <div id="ratingProducts" class="rating-products">
                    <!-- Products will be loaded here -->
                </div>                <div class="modal-actions">
                    <button id="submitRatingsBtn" class="submit-ratings-btn" onclick="submitAllRatings()">Submit Ratings</button>
                    <button class="rate-later-btn" onclick="rateLater()">Rate Later</button>
                    <button class="skip-ratings-btn" onclick="skipRatings()">Skip</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const orderItems = <?php echo json_encode($order_items); ?>;
        const baseUrl = '<?php echo $baseUrl; ?>';
        
        // Show rating modal after 3 seconds
        setTimeout(() => {
            showRatingModal();
        }, 3000);

        function showRatingModal() {
            const modal = document.getElementById('ratingModal');
            const ratingProducts = document.getElementById('ratingProducts');
            
            // Clear previous content
            ratingProducts.innerHTML = '';
              // Create rating forms for each product
            orderItems.forEach(item => {
                const productDiv = document.createElement('div');
                productDiv.className = 'rating-product-item';
                
                // Safe fallbacks for missing data
                const productTitle = item.product_name || item.title || 'Unknown Product';
                const productPrice = item.price ? parseFloat(item.price).toFixed(2) : '0.00';
                const productImage = item.image_path || 'static/images/products/sample.jpg';
                const productId = item.product_id || item.id;
                
                productDiv.innerHTML = `
                    <div class="product-info">
                        <img src="${baseUrl}${productImage}" alt="${productTitle}" class="product-thumbnail" 
                             onerror="this.src='${baseUrl}static/images/products/sample.jpg';">
                        <div class="product-details">
                            <h4>${productTitle}</h4>
                            <p class="product-price">₱${productPrice}</p>
                        </div>
                    </div>
                    <div class="rating-input-section">
                        <div class="star-rating" data-product-id="${productId}">
                            ${[1,2,3,4,5].map(star => `
                                <span class="rating-star" data-rating="${star}" onclick="setProductRating(${productId}, ${star})">★</span>
                            `).join('')}
                        </div>
                        <textarea class="rating-comment" placeholder="Add a comment (optional)" data-product-id="${productId}"></textarea>
                        <input type="hidden" class="product-rating" data-product-id="${productId}" value="">
                    </div>
                `;
                ratingProducts.appendChild(productDiv);
            });
            
            modal.style.display = 'flex';
        }

        function setProductRating(productId, rating) {
            const starContainer = document.querySelector(`[data-product-id="${productId}"].star-rating`);
            const stars = starContainer.querySelectorAll('.rating-star');
            const hiddenInput = document.querySelector(`input[data-product-id="${productId}"]`);
            
            // Update visual stars
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
            
            // Update hidden input
            hiddenInput.value = rating;
        }

        async function submitAllRatings() {
            const submitBtn = document.getElementById('submitRatingsBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const ratings = [];
            
            orderItems.forEach(item => {
                const rating = document.querySelector(`input[data-product-id="${item.product_id}"]`).value;
                const comment = document.querySelector(`textarea[data-product-id="${item.product_id}"]`).value;
                
                if (rating) {
                    ratings.push({
                        product_id: item.product_id,
                        rating: parseFloat(rating),
                        comment: comment.trim()
                    });
                }
            });
            
            if (ratings.length === 0) {
                alert('Please rate at least one product');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Ratings';
                return;
            }
            
            try {
                for (const ratingData of ratings) {
                    const response = await fetch(baseUrl + 'api/ratings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(ratingData)
                    });
                    
                    const result = await response.json();
                    if (!result.success) {
                        console.error('Failed to submit rating for product', ratingData.product_id);
                    }
                }
                
                alert('Thank you for your ratings!');
                closeRatingModal();
                
                // Clear any "rate later" cookies
                document.cookie = "pending_ratings_" + <?php echo $order_id; ?> + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                
            } catch (error) {
                console.error('Error submitting ratings:', error);
                alert('An error occurred while submitting your ratings. Please try again.');
            }
            
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Ratings';
        }        function rateLater() {
            // Store product IDs in cookie for later rating
            const productIds = orderItems.map(item => item.product_id);
            const cookieName = "pending_ratings_" + <?php echo $order_id; ?>;
            const cookieValue = JSON.stringify(productIds);
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + 30); // 30 days
            
            document.cookie = `${cookieName}=${cookieValue}; expires=${expiryDate.toUTCString()}; path=/`;
            
            // Store reminder timestamp for 2-3 day delay (using 2 minutes for demo)
            const reminderKey = "rateLaterReminder_" + <?php echo $order_id; ?>;
            localStorage.setItem(reminderKey, Date.now().toString());
            
            alert('We\'ll remind you to rate these products in a few days!');
            closeRatingModal();
        }

        function skipRatings() {
            // Store skip preference - never show notifications for this order
            const skipKey = "skipRatings_" + <?php echo $order_id; ?>;
            localStorage.setItem(skipKey, "true");
            
            // Clear any existing pending rating cookies for this order
            const cookieName = "pending_ratings_" + <?php echo $order_id; ?>;
            document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
            
            alert('You won\'t be reminded to rate these products.');
            closeRatingModal();
        }

        function closeRatingModal() {
            document.getElementById('ratingModal').style.display = 'none';
        }
    </script>

    <style>
        /* Rating Modal Styles */
        .rating-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 5px;
        }

        .modal-body {
            padding: 25px;
        }

        .rating-products {
            margin: 20px 0;
        }

        .rating-product-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .product-info {
            display: flex;
            gap: 15px;
            flex: 1;
        }

        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }

        .product-details h4 {
            margin: 0 0 5px 0;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .product-price {
            margin: 0;
            color: #666;
            font-size: 13px;
        }

        .rating-input-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 200px;
        }

        .star-rating {
            display: flex;
            gap: 2px;
        }

        .rating-star {
            font-size: 20px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .rating-star:hover,
        .rating-star.active {
            color: #FBBC05;
        }

        .rating-comment {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 12px;
            resize: vertical;
            min-height: 60px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
        }        .submit-ratings-btn, .rate-later-btn, .skip-ratings-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.2s;
        }

        .submit-ratings-btn {
            background: #007bff;
            color: white;
        }

        .submit-ratings-btn:hover {
            background: #0056b3;
        }

        .submit-ratings-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .rate-later-btn {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }        .rate-later-btn:hover {
            background: #e9ecef;
        }

        .skip-ratings-btn {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }

        .skip-ratings-btn:hover {
            background: #c82333;
            border-color: #bd2130;
        }        @media (max-width: 768px) {
            .rating-product-item {
                flex-direction: column;
                gap: 15px;
            }
            
            .rating-input-section {
                min-width: auto;
            }
        }
        
        /* Dark Mode Support */
        [data-theme="dark"] .modal-content {
            background: #2d3748;
            border: 1px solid #4a5568;
        }
        
        [data-theme="dark"] .modal-header {
            border-bottom: 1px solid #4a5568;
        }
        
        [data-theme="dark"] .modal-header h3 {
            color: #e2e8f0;
        }
        
        [data-theme="dark"] .close-btn {
            color: #a0aec0;
        }
        
        [data-theme="dark"] .modal-body {
            color: #e2e8f0;
        }
        
        [data-theme="dark"] .modal-body p {
            color: #e2e8f0;
        }
        
        [data-theme="dark"] .rating-product-item {
            border: 1px solid #4a5568;
            background: #374151;
        }
        
        [data-theme="dark"] .product-details h4 {
            color: #e2e8f0;
        }
        
        [data-theme="dark"] .product-price {
            color: #a0aec0;
        }
        
        [data-theme="dark"] .rating-comment {
            background: #4a5568;
            border: 1px solid #6b7280;
            color: #e2e8f0;
        }
        
        [data-theme="dark"] .rating-comment::placeholder {
            color: #a0aec0;
        }
        
        [data-theme="dark"] .submit-ratings-btn {
            background: #3182ce;
        }
        
        [data-theme="dark"] .submit-ratings-btn:hover {
            background: #2c5aa0;
        }
        
        [data-theme="dark"] .rate-later-btn {
            background: #4a5568;
            color: #e2e8f0;
            border: 1px solid #6b7280;
        }
        
        [data-theme="dark"] .rate-later-btn:hover {
            background: #374151;
        }
        
        [data-theme="dark"] .skip-ratings-btn {
            background: #e53e3e;
            border-color: #e53e3e;
        }
        
        [data-theme="dark"] .skip-ratings-btn:hover {
            background: #c53030;
            border-color: #c53030;
        }
    </style>
</body>
</html>
