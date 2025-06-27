<?php
require_once __DIR__ . '/../config/db.php';

// Check for pending ratings and show notification
function checkPendingRatings($user_id) {
    if (!isset($_COOKIE) || !$user_id) return [];
    
    $pendingRatings = [];
    
    try {
        $db = Database::getInstance()->getConnection();
        
        foreach ($_COOKIE as $cookieName => $cookieValue) {
            if (strpos($cookieName, 'pending_ratings_') === 0) {
                $orderId = str_replace('pending_ratings_', '', $cookieName);
                $productIds = json_decode($cookieValue, true);
                
                if (is_array($productIds) && !empty($productIds)) {
                    // Get product details for pending ratings
                    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                    $stmt = $db->prepare("
                        SELECT id, title, image_path 
                        FROM products 
                        WHERE id IN ($placeholders) AND status = 'active'
                    ");
                    $stmt->execute($productIds);
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($products)) {
                        $pendingRatings[] = [
                            'order_id' => $orderId,
                            'products' => $products
                        ];
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error checking pending ratings: " . $e->getMessage());
    }
    
    return $pendingRatings;
}

// Filter pending ratings based on skip preferences and timing
function filterPendingRatings($pendingRatings) {
    $filteredRatings = [];
    
    foreach ($pendingRatings as $rating) {
        $orderId = $rating['order_id'];
        
        // Skip orders that user chose to skip
        echo '<script>';
        echo 'if (localStorage.getItem("skipRatings_' . $orderId . '") === "true") continue;';
        echo '</script>';
        
        // Check if enough time has passed for "Rate Later" orders
        echo '<script>';
        echo '
        (function() {
            const reminderKey = "rateLaterReminder_' . $orderId . '";
            const reminderTime = localStorage.getItem(reminderKey);
            if (reminderTime) {
                const now = Date.now();
                const timeSinceReminder = now - parseInt(reminderTime);
                const REMINDER_DELAY = 2 * 60 * 1000; // 2 minutes for demo
                
                if (timeSinceReminder < REMINDER_DELAY) {
                    return; // Not enough time has passed
                }
            }
        })();
        ';
        echo '</script>';
        
        $filteredRatings[] = $rating;
    }
    
    return $filteredRatings;
}

function showPendingRatingsNotification($pendingRatings, $baseUrl) {
    if (empty($pendingRatings)) return;
    
    // Calculate total products first
    $totalProducts = 0;
    foreach ($pendingRatings as $order) {
        $totalProducts += count($order['products']);
    }
    
    echo '<script>';
    echo '
    (function() {
        // Wait for pendingRatingsData to be available
        document.addEventListener("DOMContentLoaded", function() {
            // Check if notification should be shown based on order-specific dismissals
            if (typeof window.pendingRatingsData !== "undefined" && window.pendingRatingsData.length > 0) {
                const orderIds = window.pendingRatingsData.map(order => order.order_id).sort().join("_");
                const dismissalKey = "pendingRatingsDismissed_" + orderIds;
                const dismissed = localStorage.getItem(dismissalKey);
                
                let shouldShow = true;
                  if (dismissed) {
                    const dismissedTime = parseInt(dismissed);
                    const now = Date.now();
                    const DISMISS_DURATION = 2 * 60 * 1000; // 2 minutes for demo
                    
                    if ((now - dismissedTime) < DISMISS_DURATION) {
                        // Check if there are new orders since last dismissal
                        let hasNewOrders = false;
                        for (let i = 0; i < window.pendingRatingsData.length; i++) {
                            const orderId = window.pendingRatingsData[i].order_id;
                            const individualKey = "pendingRatingsDismissed_" + orderId;
                            const individualDismissed = localStorage.getItem(individualKey);
                            
                            if (!individualDismissed || (now - parseInt(individualDismissed)) >= DISMISS_DURATION) {
                                hasNewOrders = true;
                                break;
                            }
                        }
                        
                        if (!hasNewOrders) {
                            shouldShow = false;
                        }
                    }
                }
                
                // Check if any orders are marked as skipped (permanent dismissal)
                if (shouldShow) {
                    const nonSkippedOrders = window.pendingRatingsData.filter(order => {
                        const skipKey = "skipRatings_" + order.order_id;
                        return localStorage.getItem(skipKey) !== "true";
                    });
                    
                    if (nonSkippedOrders.length === 0) {
                        shouldShow = false;
                    } else {
                        // Update pendingRatingsData to only include non-skipped orders
                        window.pendingRatingsData = nonSkippedOrders;
                    }
                }
                
                // Check "Rate Later" timing
                if (shouldShow) {
                    shouldShow = window.pendingRatingsData.some(order => {
                        const reminderKey = "rateLaterReminder_" + order.order_id;
                        const reminderTime = localStorage.getItem(reminderKey);
                        
                        if (!reminderTime) {
                            // No reminder set, this is a fresh order - show notification
                            return true;
                        }
                        
                        const now = Date.now();
                        const reminderTimestamp = parseInt(reminderTime);
                        const REMINDER_DELAY = 2 * 60 * 1000; // 2 minutes for demo
                        
                        return (now - reminderTimestamp) >= REMINDER_DELAY;
                    });
                }                if (shouldShow) {
                    showPendingNotificationHTML();
                }
            }
        });
    })();
    
    function showPendingNotificationHTML() {
        // Create notification HTML
        const notificationHTML = `
        <div id="pendingRatingsNotification" class="pending-ratings-notification">
            <div class="notification-content">
                <div class="notification-icon">⭐</div>
                <div class="notification-text">
                    <strong>Rate Your Recent Purchases</strong>
                    <p>You have ' . $totalProducts . ' product(s) waiting for your review</p>
                </div>
                <div class="notification-actions">
                    <button onclick="showPendingRatingsModal()" class="rate-now-btn">Rate Now</button>
                    <button onclick="hidePendingRatingsNotification()" class="dismiss-btn">×</button>
                </div>
            </div>
        </div>`;
        
        // Insert notification into page
        document.body.insertAdjacentHTML("beforeend", notificationHTML);
    }
    ';
    echo '</script>';
    
    // Add the modal HTML
    echo '<div id="pendingRatingsModal" class="rating-modal" style="display: none;">';
    echo '<div class="modal-overlay" onclick="closePendingRatingsModal()"></div>';
    echo '<div class="modal-content">';
    echo '<div class="modal-header">';
    echo '<h3>Rate Your Purchases</h3>';
    echo '<button class="close-btn" onclick="closePendingRatingsModal()">&times;</button>';    echo '</div>';
    echo '<div class="modal-body">';
    echo '<p>Help other customers by rating the products you purchased:</p>';
    echo '<div id="pendingRatingProducts" class="rating-products">';
    
    foreach ($pendingRatings as $order) {
        foreach ($order['products'] as $product) {
            echo '<div class="rating-product-item">';
            echo '<div class="product-info">';
            echo '<img src="' . htmlspecialchars($baseUrl . $product['image_path']) . '" alt="' . htmlspecialchars($product['title']) . '" class="product-thumbnail">';
            echo '<div class="product-details">';
            echo '<h4>' . htmlspecialchars($product['title']) . '</h4>';
            echo '</div>';
            echo '</div>';
            echo '<div class="rating-input-section">';
            echo '<div class="star-rating" data-product-id="' . $product['id'] . '">';
            for ($i = 1; $i <= 5; $i++) {
                echo '<span class="rating-star" data-rating="' . $i . '" onclick="setPendingRating(' . $product['id'] . ', ' . $i . ')">★</span>';
            }
            echo '</div>';
            echo '<textarea class="rating-comment" placeholder="Add a comment (optional)" data-product-id="' . $product['id'] . '"></textarea>';
            echo '<input type="hidden" class="product-rating" data-product-id="' . $product['id'] . '" value="">';
            echo '</div>';
            echo '</div>';
        }
    }
    
    echo '</div>';
    echo '<div class="modal-actions">';
    echo '<button id="submitPendingRatingsBtn" class="submit-ratings-btn" onclick="submitPendingRatings()">Submit Ratings</button>';
    echo '<button class="rate-later-btn" onclick="closePendingRatingsModal()">Close</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';    
    // Add the data for this specific notification
    echo '<script>';
    echo 'window.pendingRatingsData = ' . json_encode($pendingRatings) . ';';
    echo '</script>';
    
    // Add CSS for the notification
    echo '<style>';
    echo '
    .pending-ratings-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border: 1px solid #e5e5e5;
        border-left: 4px solid #FBBC05;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        max-width: 350px;
        animation: slideInRight 0.3s ease;
    }
    
    .notification-content {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
    }
    
    .notification-icon {
        font-size: 20px;
        margin-top: 2px;
    }
    
    .notification-text {
        flex: 1;
    }
    
    .notification-text strong {
        font-family: "Poppins", sans-serif;
        font-weight: 600;
        color: #333;
        display: block;
        margin-bottom: 4px;
    }
    
    .notification-text p {
        margin: 0;
        font-size: 14px;
        color: #666;
    }
    
    .notification-actions {
        display: flex;
        flex-direction: row;
        margin-top: 30px;
        gap: 8px;
    }
    
    .rate-now-btn {
        background: #FBBC05;
        color: #333;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .rate-now-btn:hover {
        background: #E6AB00;
    }
    
    .dismiss-btn {
        background: none;
        border: none;
        color: #999;
        font-size: 18px;
        cursor: pointer;
        padding: 2px;
        line-height: 1;
    }
    
    .dismiss-btn:hover {
        color: #666;
    }
      @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
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
        font-family: "Poppins", sans-serif;
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
        font-family: "Poppins", sans-serif;
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
    }

    .submit-ratings-btn, .rate-later-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-family: "Poppins", sans-serif;
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
    }

    .rate-later-btn:hover {
        background: #e9ecef;
    }    @media (max-width: 768px) {
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
    ';
    echo '</style>';
}

// Simply include the external JS file instead of duplicating functions
function addPendingRatingsGlobalScript($baseUrl = '') {
    echo '<script src="' . $baseUrl . 'static/js/pending-ratings.js?v=' . time() . '"></script>';
}
?>
