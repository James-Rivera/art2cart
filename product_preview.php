<?php
require_once 'config/db.php';
require_once 'includes/products.php';
require_once 'includes/User.php';

session_start();

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    header('Location: /Art2Cart/catalogue.php');
    exit;
}

// Get product details
$productService = new ProductService();
$product = $productService->getProductById($productId);

if (!$product) {
    header('Location: /Art2Cart/catalogue.php');
    exit;
}

// Get product ratings
$ratings = $productService->getProductRatings($productId);

// Get user's rating if logged in
$userRating = null;
if (isset($_SESSION['user_id'])) {
    $userRating = $productService->getUserRating($productId, $_SESSION['user_id']);
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating']) && isset($_SESSION['user_id'])) {
    $rating = (float)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5) {
        if ($productService->submitRating($productId, $_SESSION['user_id'], $rating, $comment)) {
            header("Location: /Art2Cart/product_preview.php?id=$productId&rated=1");
            exit;
        }
    }
}

// Check if user has purchased this product
$hasPurchased = false;
if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
    ");
    $stmt->execute([$_SESSION['user_id'], $productId]);
    $hasPurchased = $stmt->fetch()['count'] > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title><?php echo htmlspecialchars($product['title']); ?> - Art2Cart</title>
    <link rel="stylesheet" href="/Art2Cart/static/css/product_preview/preview_product.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;400;600;700&family=Karla:ital,wght@0,400;0,500;1,500&family=Poppins:wght@400;500;600&family=Satoshi:wght@400;500&family=Inika:wght@700&family=Roboto:wght@200;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'static/templates/header_new.php'; ?>
      <!-- Main Product Section -->
    <main class="product-main">
        <!-- Product Content (Images and Details) -->
        <div class="product-content">
            <!-- Left - Product Images -->
            <div class="product-images">
            <div class="image-overlay"></div>
              <!-- Main Image -->
            <div class="main-image">
                <img id="mainProductImage" src="<?php echo htmlspecialchars('/Art2Cart/' . $product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
            </div>
        </div>

        <!-- Right - Product Details -->
        <div class="product-details">
            <div class="details-container">
                <!-- Back Button -->
                <button class="back-btn" onclick="goBack()">
                    <svg width="13" height="10" viewBox="0 0 13 10" fill="none">
                        <path d="M1 5h12M5 1l-4 4 4 4" stroke="black" stroke-width="1"/>
                    </svg>
                    <span>Back</span>
                </button>                <!-- Category -->
                <?php 
                    // Convert category name to CSS class format
                    $categoryClass = strtolower(str_replace(' ', '-', $product['category_name']));
                ?>
                <div class="category-btn <?php echo $categoryClass; ?>">
                    <span id="productCategory"><?php echo htmlspecialchars($product['category_name']); ?></span>
                </div>

                <!-- Product Title -->
                <h1 class="product-title" id="productTitle"><?php echo htmlspecialchars($product['title']); ?></h1>

                <!-- Artist Info -->                <div class="artist-info">
                    <div class="artist-container">
                        <div class="artist-profile">
                            <div class="artist-avatar"><?php echo strtoupper(substr($product['seller_name'], 0, 1)); ?></div>
                            <div class="artist-details">
                                <div class="artist-name" id="artistName"><?php echo htmlspecialchars($product['seller_name']); ?></div>
                                <div class="artist-rating">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <path d="M8 1l2.09 4.26L14 6l-3 2.96L11.82 13 8 10.74 4.18 13 5 8.96 2 6l3.91-.74L8 1z" fill="#FFC700"/>
                                    </svg>
                                    <span id="artistRating"><?php echo number_format($product['rating'], 1); ?></span>
                                </div>
                                <div class="artist-sales" id="artistSales"># Followers</div>
                            </div>
                        </div>
                        <button class="follow-btn">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>Follow</span>
                        </button>
                    </div>                </div>                <!-- Product Information Section -->
                <div class="product-information">
                    <h3 class="section-title">Product Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2L13.09 8.26L19 9L13.09 9.74L12 16L10.91 9.74L5 9L10.91 8.26L12 2Z" fill="#FFD700"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Downloads</span>
                                <span class="info-value"><?php echo number_format($product['downloads']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M8 2V5M16 2V5M3.5 9.09H20.5M4 18V10A2 2 0 0 1 6 8H18A2 2 0 0 1 20 10V18A2 2 0 0 1 18 20H6A2 2 0 0 1 4 18Z" stroke="#666" stroke-width="2"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Date Added</span>
                                <span class="info-value"><?php echo date('M j, Y', strtotime($product['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M9.5 3A6.5 6.5 0 0 1 16 9.5C16 11.11 15.41 12.59 14.44 13.73L14.71 14H15.5L20.5 19L19 20.5L14 15.5V14.71L13.73 14.44C12.59 15.41 11.11 16 9.5 16A6.5 6.5 0 0 1 3 9.5A6.5 6.5 0 0 1 9.5 3M9.5 5C7 5 5 7 5 9.5S7 14 9.5 14S14 12 14 9.5S12 5 9.5 5Z" fill="#666"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Category</span>
                                <span class="info-value"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 1L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 1Z" fill="#FFC700"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Rating</span>
                                <span class="info-value"><?php echo number_format($product['rating'], 1); ?>/5</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description Section -->
                <div class="description-section">
                    <h3 class="section-title">Description</h3>
                    <div class="description-content">
                        <p id="productDescription"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                </div>

                <hr class="divider">

                <!-- Price -->
                <div class="price">
                    <span id="productPrice">₱<?php echo number_format($product['price'], 2); ?></span>
                </div>

                <!-- Quantity -->
                <div class="quantity-section">
                    <span class="quantity-label">Quantity</span>
                    <div class="quantity-controls">
                        <button class="quantity-btn minus-btn" onclick="decreaseQuantity()">
                            <svg width="19" height="19" viewBox="0 0 19 19" fill="none">
                                <path d="M4 9.5h11" stroke="black" stroke-width="2"/>
                            </svg>
                        </button>
                        <span class="quantity-value" id="quantityValue">1</span>
                        <button class="quantity-btn plus-btn" onclick="increaseQuantity()">
                            <svg width="19" height="19" viewBox="0 0 19 19" fill="none">
                                <path d="M9.5 4v11M4 9.5h11" stroke="black" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </div>                <!-- Add to Cart Button -->
                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $productId; ?>)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L6 5H3m4 8v6a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-6" stroke="black" stroke-width="2"/>
                    </svg>
                    Add to Cart                </button>
            </div>
        </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <div class="reviews-container">                <div class="reviews-header">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="switchTab('reviews')">Reviews</button>
                    </div>
                </div>

            <div class="reviews-content" id="reviewsTab">
                <!-- Rating Summary -->
                <div class="rating-summary">
                    <div class="rating-score" id="averageRating"><?php echo $ratings['average']; ?></div>
                    <div class="rating-stars" id="ratingStars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M8 1l2.09 4.26L14 6l-3 2.96L11.82 13 8 10.74 4.18 13 5 8.96 2 6l3.91-.74L8 1z" fill="<?php echo $i <= round($ratings['average']) ? '#FBBC05' : '#E0E0E0'; ?>"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-count" id="reviewCount"><?php echo $ratings['total']; ?> review<?php echo $ratings['total'] != 1 ? 's' : ''; ?></div>
                </div>

                <!-- Rating Breakdown -->
                <div class="rating-breakdown">
                    <?php for ($star = 5; $star >= 1; $star--): 
                        $count = $ratings['breakdown'][$star];
                        $percentage = $ratings['total'] > 0 ? ($count / $ratings['total']) * 100 : 0;
                    ?>
                        <div class="rating-bar" data-star="<?php echo $star; ?>">
                            <span class="star-number"><?php echo $star; ?></span>
                            <div class="progress-bar <?php echo $star <= 3 ? 'blue' : ''; ?>">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <span class="star-count"><?php echo str_pad($count, 2, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Rating Form (for users who purchased the product) -->
                <?php if (isset($_SESSION['user_id']) && $hasPurchased): ?>
                    <div class="rating-form">
                        <h3><?php echo $userRating ? 'Update Your Rating' : 'Rate This Product'; ?></h3>
                        <form method="POST" action="">
                            <div class="rating-input">
                                <label>Your Rating:</label>
                                <div class="star-rating" id="starRating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="rating-star <?php echo $userRating && $i <= $userRating['rating'] ? 'active' : ''; ?>" 
                                              data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">★</span>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="ratingInput" value="<?php echo $userRating ? $userRating['rating'] : ''; ?>" required>
                            </div>
                            <div class="comment-input">
                                <label for="comment">Comment (optional):</label>
                                <textarea name="comment" id="comment" rows="4" placeholder="Share your thoughts about this product..."><?php echo $userRating ? htmlspecialchars($userRating['comment']) : ''; ?></textarea>
                            </div>
                            <button type="submit" name="submit_rating" class="submit-rating-btn">
                                <?php echo $userRating ? 'Update Rating' : 'Submit Rating'; ?>
                            </button>
                        </form>
                    </div>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <div class="login-prompt">
                        <p><a href="/Art2Cart/auth/auth.html">Login</a> to rate this product.</p>
                    </div>
                <?php elseif (!$hasPurchased): ?>
                    <div class="purchase-prompt">
                        <p>Purchase this product to leave a rating.</p>
                    </div>                <?php endif; ?>
            </div>
        </div>
    </div>
    </main>

    <!-- Footer -->
    <?php include 'static/templates/footer_new.html'; ?>

    <script>
        // Product data for JavaScript - prevent override from external JS
        const productData = {
            id: <?php echo $productId; ?>,
            title: <?php echo json_encode($product['title']); ?>,
            price: <?php echo $product['price']; ?>,
            image: <?php echo json_encode('/Art2Cart/' . $product['image_path']); ?>
        };

        // Prevent the original JS from overriding our data
        let currentQuantity = 1;
        let currentImageIndex = 0;

        // Go back function
        function goBack() {
            if (document.referrer && document.referrer.includes('Art2Cart')) {
                window.history.back();
            } else {
                window.location.href = '/Art2Cart/catalogue.php';
            }
        }

        // Rating functionality
        function setRating(rating) {
            document.getElementById('ratingInput').value = rating;
            
            const stars = document.querySelectorAll('.rating-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }        // Tab switching (simplified since only Reviews tab exists)
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
            
            // Show reviews content
            document.getElementById('reviewsTab').style.display = 'flex';
        }// Add to cart functionality
        async function addToCart(productId) {
            try {
                const quantity = parseInt(document.getElementById('quantityValue').textContent);
                const response = await fetch('/Art2Cart/api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add&product_id=${productId}&quantity=${quantity}`
                });

                const data = await response.json();
                
                if (data.success) {
                    // Show success message with notification
                    showNotification('Product added to cart successfully!', 'success');
                    
                    // Update cart count in header if exists
                    updateHeaderCartCount(data.cart_count);
                } else {
                    if (data.message && data.message.includes('log in')) {
                        // Redirect to login if not logged in
                        window.location.href = '/Art2Cart/auth/auth.html';
                    } else {
                        showNotification(data.message || 'Failed to add item to cart', 'error');
                    }
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showNotification('An error occurred while adding the item to cart', 'error');
            }
        }

        // Quantity controls (from original JS)
        function increaseQuantity() {
            const quantityElement = document.getElementById('quantityValue');
            let currentQuantity = parseInt(quantityElement.textContent);
            quantityElement.textContent = currentQuantity + 1;
        }        function decreaseQuantity() {
            const quantityElement = document.getElementById('quantityValue');
            let currentQuantity = parseInt(quantityElement.textContent);
            if (currentQuantity > 1) {
                quantityElement.textContent = currentQuantity - 1;
            }
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#E6C200' : '#EF4444'};
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                font-family: var(--font-inter);
                font-weight: 500;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        function updateHeaderCartCount(count) {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
                cartCountElement.style.display = count > 0 ? 'inline' : 'none';
            }
        }

        // Add CSS animations for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);// Show success message if rating was submitted
        <?php if (isset($_GET['rated'])): ?>
            alert('Thank you for your rating!');
        <?php endif; ?>
    </script>
</body>
</html>
