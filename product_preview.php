<?php
require_once 'config/db.php';
require_once 'includes/products.php';
require_once 'includes/User.php';
require_once 'includes/Art2CartConfig.php';

session_start();

// Get base URL configuration
$baseHref = Art2CartConfig::getBaseUrl();
$baseUrl = Art2CartConfig::getBaseUrl();

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isPurchasedView = isset($_GET['purchased']) && $_GET['purchased'] === '1';

if (!$productId) {
    header('Location: ' . $baseUrl . 'catalogue.php');
    exit;
}

// Get product details
$productService = new ProductService();
$product = null;

// If this is a purchased view and user is logged in, try to get purchased product
if ($isPurchasedView && isset($_SESSION['user_id'])) {
    $product = $productService->getPurchasedProductById($productId, $_SESSION['user_id']);
    
    // If user hasn't purchased this product, redirect to regular view
    if (!$product) {
        header('Location: ' . $baseUrl . 'product_preview.php?id=' . $productId);
        exit;
    }
} else {
    // Regular product view - only active products
    $product = $productService->getProductById($productId);
}

if (!$product) {
    header('Location: ' . $baseUrl . 'catalogue.php');
    exit;
}

// Get product ratings
$ratings = $productService->getProductRatings($productId);

// Get individual reviews
$reviews = $productService->getProductReviews($productId, 10, 0);

// Check if this is an AJAX request for modal content
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    // Return only the product content for the modal
    $images = json_decode($product['images'], true);
    $firstImage = is_array($images) && !empty($images) ? $images[0] : null;
    
    echo '<div class="product-preview-header">';
    echo '<h3 class="product-preview-title">' . htmlspecialchars($product['title']) . '</h3>';
    echo '<div class="product-preview-price">₱' . number_format($product['price'], 2) . '</div>';
    echo '</div>';
    
    if ($firstImage) {
        echo '<img src="' . htmlspecialchars($firstImage) . '" alt="' . htmlspecialchars($product['title']) . '" class="product-preview-image">';
    }
    
    if (!empty($product['description'])) {
        echo '<div class="product-preview-description">' . nl2br(htmlspecialchars($product['description'])) . '</div>';
    }
    
    echo '<div class="product-preview-meta">';
    echo '<div class="meta-item"><i class="fas fa-calendar"></i> <span>Uploaded ' . date('M j, Y', strtotime($product['created_at'])) . '</span></div>';
    echo '<div class="meta-item"><i class="fas fa-user"></i> <span>Seller ID: ' . htmlspecialchars($product['user_id']) . '</span></div>';
    echo '<div class="meta-item"><i class="fas fa-star"></i> <span>Rating: ' . number_format($ratings['average_rating'], 1) . ' (' . $ratings['total_ratings'] . ' reviews)</span></div>';
    echo '</div>';
    
    exit; // Stop execution for AJAX requests
}

// Get user's rating if logged in
$userRating = null;
if (isset($_SESSION['user_id'])) {
    $userRating = $productService->getUserRating($productId, $_SESSION['user_id']);
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating']) && isset($_SESSION['user_id'])) {
    $rating = (float)$_POST['rating'];
    $comment = trim($_POST['comment']);    if ($rating >= 1 && $rating <= 5) {
        if ($productService->submitRating($productId, $_SESSION['user_id'], $rating, $comment)) {
            header("Location: " . $baseUrl . "product_preview.php?id=$productId&rated=1");
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
    
    <!-- Base URL configuration -->
    <base href="<?php echo htmlspecialchars($baseHref); ?>">
    
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
    <link rel="manifest" href="static/images/favicon/site.webmanifest">    <!-- Optional theme color -->
    <meta name="theme-color" content="#ffffff">
    
    <link rel="stylesheet" href="static/css/var.css">
    <link rel="stylesheet" href="static/css/fonts.css">
    <link rel="stylesheet" href="static/css/template/header.css">
    <link rel="stylesheet" href="static/css/product_preview/preview_product.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;400;600;700&family=Karla:ital,wght@0,400;0,500;1,500&family=Poppins:wght@400;500;600&family=Satoshi:wght@400;500&family=Inika:wght@700&family=Roboto:wght@200;400;500;700&display=swap" rel="stylesheet">
      <script>
        window.baseHref = '<?php echo $baseHref; ?>';
    </script>
</head>
<body<?php echo $isPurchasedView ? ' class="purchased-view"' : ''; ?>>
    <?php include 'static/templates/header_new.php'; ?>
    
    <?php if ($isPurchasedView): ?>
        <!-- Purchased Product Notice -->
        <div class="purchased-notice">
            <div class="notice-content">
                <div class="notice-icon">✓</div>
                <div class="notice-text">
                    <strong>You own this product</strong>
                    <span>This is your purchased product view with full access</span>
                </div>
                <a href="account.php" class="back-to-account">Back to My Account</a>
            </div>
        </div>
    <?php endif; ?>
      <!-- Main Product Section -->
    <main class="product-main">
        <!-- Product Content (Images and Details) -->
        <div class="product-content">
            <!-- Left - Product Images -->
            <div class="product-images">
            <div class="image-overlay"></div>
              <!-- Main Image -->            <div class="main-image">
                <img id="mainProductImage" src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
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
                ?>                <div class="category-btn <?php echo $categoryClass; ?>">
                    <span id="productCategory"><?php echo htmlspecialchars($product['category_name']); ?></span>
                </div>

                <!-- Product Title -->
                <h1 class="product-title" id="productTitle"><?php echo htmlspecialchars($product['title']); ?></h1>

                <!-- Artist Info -->
                <div class="artist-info">
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
                            </svg>                            <span>Follow</span>
                        </button>
                    </div>
                </div>
                
                <!-- Product Information Section -->
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

                <hr class="divider">                <!-- Price -->
                <div class="price">
                    <span id="productPrice">₱<?php echo number_format($product['price'], 2); ?></span>
                    <?php if ($isPurchasedView): ?>
                        <span class="purchased-badge">✓ Purchased</span>
                    <?php endif; ?>
                </div>

                <!-- Status and Action Buttons -->
                <?php if ($isPurchasedView): ?>
                    <!-- Purchased Product Actions -->
                    <div class="purchased-actions">
                        <button class="download-btn" onclick="downloadProduct(<?php echo $productId; ?>)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download Product
                        </button>
                        <?php if (isset($product['status']) && $product['status'] !== 'active'): ?>
                            <div class="status-notice">
                                <p><strong>Note:</strong> This product is no longer available for purchase but you can still access it because you purchased it previously.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (isset($product['status']) && $product['status'] === 'active'): ?>
                    <!-- Regular Add to Cart for Active Products -->
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $productId; ?>)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L6 5H3m4 8v6a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-6" stroke="black" stroke-width="2"/>
                        </svg>
                        Add to Cart
                    </button>
                <?php else: ?>
                    <!-- Product Not Available -->
                    <div class="unavailable-notice">
                        <p>This product is currently unavailable.</p>
                        <a href="<?php echo $baseUrl; ?>catalogue.php" class="browse-more-btn">Browse More Products</a>
                    </div>
                <?php endif; ?>
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

            <div class="reviews-content" id="reviewsTab">                <!-- Rating Summary -->
                <div class="rating-summary">
                    <div class="rating-score" id="averageRating"><?php echo number_format($ratings['average'], 1); ?></div>
                    <div class="rating-stars" id="ratingStars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M8 1l2.09 4.26L14 6l-3 2.96L11.82 13 8 10.74 4.18 13 5 8.96 2 6l3.91-.74L8 1z" fill="<?php echo $i <= round($ratings['average']) ? '#FBBC05' : '#E0E0E0'; ?>"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-count" id="reviewCount"><?php echo $ratings['total']; ?> review<?php echo $ratings['total'] != 1 ? 's' : ''; ?></div>
                </div>

                <!-- Rating Breakdown with better visualization -->
                <div class="rating-breakdown">
                    <?php for ($star = 5; $star >= 1; $star--): 
                        $count = $ratings['breakdown'][$star];
                        $percentage = $ratings['total'] > 0 ? ($count / $ratings['total']) * 100 : 0;
                    ?>
                        <div class="rating-bar" data-star="<?php echo $star; ?>">
                            <div class="star-label">
                                <span class="star-number"><?php echo $star; ?></span>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="#FBBC05">
                                    <path d="M8 1l2.09 4.26L14 6l-3 2.96L11.82 13 8 10.74 4.18 13 5 8.96 2 6l3.91-.74L8 1z"/>
                                </svg>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <span class="star-count"><?php echo $count; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                        </div>
                    <?php endfor; ?>
                </div><!-- Rating Form (for users who purchased the product) -->
                <?php if (isset($_SESSION['user_id']) && $hasPurchased): ?>
                    <div class="rating-form">
                        <h3><?php echo $userRating ? 'Update Your Rating' : 'Rate This Product'; ?></h3>
                        <form id="ratingForm" onsubmit="submitRating(event)">
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
                            <button type="submit" class="submit-rating-btn">
                                <?php echo $userRating ? 'Update Rating' : 'Submit Rating'; ?>
                            </button>
                        </form>
                        <div id="ratingMessage" class="rating-message" style="display: none;"></div>
                    </div>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <div class="login-prompt">
                        <p><a href="auth/auth.html">Login</a> to rate this product.</p>
                    </div>                <?php elseif (!$hasPurchased): ?>
                    <div class="purchase-prompt">
                        <p>Purchase this product to leave a rating.</p>
                    </div>
                <?php endif; ?>

                <!-- Individual Reviews -->
                <?php if (!empty($reviews)): ?>
                    <div class="reviews-list">
                        <h3>Customer Reviews</h3>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div class="reviewer-avatar">
                                            <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                                        </div>
                                        <div class="reviewer-details">
                                            <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></span>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="review-star <?php echo $i <= $review['rating'] ? 'filled' : 'empty'; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-date">
                                        <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                    <div class="review-comment">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>    </main>
    
    <!-- Footer -->
    <?php include 'static/templates/footer_new.php'; ?>    
    <script>
        // Product data for JavaScript - prevent override from external JS
        let productData;
        <?php if ($product): ?>
        productData = {
            id: <?php echo intval($productId); ?>,
            title: <?php echo json_encode($product['title'] ?? ''); ?>,
            price: <?php echo floatval($product['price'] ?? 0); ?>,
            image: <?php echo json_encode($baseUrl . ($product['image_path'] ?? '')); ?>
        };
        <?php else: ?>
        productData = {
            id: 0,
            title: '',
            price: 0,
            image: ''
        };
        <?php endif; ?>
        
        // Prevent the original JS from overriding our data
        let currentImageIndex = 0;
        
        // Go back function
        function goBack() {
            if (document.referrer && document.referrer.includes('Art2Cart')) {
                window.history.back();
            } else {
                window.location.href = window.baseHref + 'catalogue.php';
            }
        }

    // Rating functionality
        function setRating(rating) {
            document.getElementById('ratingInput').value = rating;
            
            // Only select stars within the star-rating container (the interactive ones)
            const stars = document.querySelectorAll('#starRating .rating-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // AJAX rating submission
        async function submitRating(event) {
            event.preventDefault();
            
            const form = event.target;
            const rating = form.rating.value;
            const comment = form.comment.value;
            const messageDiv = document.getElementById('ratingMessage');
            const submitBtn = form.querySelector('.submit-rating-btn');
            
            if (!rating) {
                showRatingMessage('Please select a rating', 'error');
                return;
            }
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            try {
                const response = await fetch(window.baseHref + 'api/ratings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productData.id,
                        rating: parseFloat(rating),
                        comment: comment
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showRatingMessage('Rating submitted successfully!', 'success');
                    
                    // Update rating display
                    if (data.ratings) {
                        updateRatingDisplay(data.ratings);
                    }
                    
                    // Reload page after a short delay to show updated reviews
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showRatingMessage(data.error || 'Failed to submit rating', 'error');
                }
            } catch (error) {
                console.error('Error submitting rating:', error);
                showRatingMessage('An error occurred while submitting your rating', 'error');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = '<?php echo $userRating ? "Update Rating" : "Submit Rating"; ?>';
            }
        }
        
        function showRatingMessage(message, type) {
            const messageDiv = document.getElementById('ratingMessage');
            messageDiv.textContent = message;
            messageDiv.className = `rating-message ${type}`;
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
        
        function updateRatingDisplay(ratings) {
            // Update average rating
            const averageRatingElement = document.getElementById('averageRating');
            const reviewCountElement = document.getElementById('reviewCount');
            
            if (averageRatingElement) {
                averageRatingElement.textContent = ratings.average;
            }
            
            if (reviewCountElement) {
                reviewCountElement.textContent = ratings.total + ' review' + (ratings.total !== 1 ? 's' : '');
            }
            
            // Update rating stars
            const ratingStars = document.querySelectorAll('#ratingStars svg path');
            ratingStars.forEach((star, index) => {
                star.setAttribute('fill', index < Math.round(ratings.average) ? '#FBBC05' : '#E0E0E0');
            });        }
        
        // Tab switching (simplified since only Reviews tab exists)
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
            
            // Show reviews content
            document.getElementById('reviewsTab').style.display = 'flex';
        }

        // Add to cart functionality        
        async function addToCart(productId) {
            try {
                const response = await fetch(window.baseHref + 'api/cart.php', {
                    method: 'POST',                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add&product_id=${productId}&quantity=1`
                });

                const data = await response.json();
                  if (data.success) {
                    // Show success message with notification
                    showNotification('Product added to cart!', 'success');
                    
                    // Update cart count in header if exists
                    updateHeaderCartCount(data.cart_count);                } else {
                    if (data.message.includes('log in')) {
                        // Redirect to login if not logged in
                        window.location.href = 'auth/auth.html';
                    } else {
                        showNotification(data.message || 'Failed to add item to cart', 'error');
                    }
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showNotification('An error occurred while adding the item to cart', 'error');
            }
        }

        // Download product functionality
        async function downloadProduct(productId) {
            try {
                // First, get the order ID for this product
                const response = await fetch(window.baseHref + 'api/get_download_info.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });

                const data = await response.json();

                if (data.success && data.order_id) {
                    // Redirect to download with order_id and product_id
                    window.location.href = window.baseHref + `download.php?order_id=${data.order_id}&product_id=${productId}`;
                } else {
                    showNotification('Unable to download product. Please contact support.', 'error');
                }
            } catch (error) {
                console.error('Error downloading product:', error);
                showNotification('An error occurred while downloading the product', 'error');
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
                from { transform: translateX(0); opacity: 1; }                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        
        document.head.appendChild(style);
        
        // Initialize rating functionality when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Make sure existing rating is displayed correctly
            const existingRating = <?php echo $userRating ? $userRating['rating'] : '0'; ?>;
            if (existingRating > 0) {
                setRating(existingRating);
            }
        });

        // Show success message if rating was submitted
        <?php if (isset($_GET['rated'])): ?>
            alert('Thank you for your rating!');
        <?php endif; ?>
    </script>
</body>
</html>
