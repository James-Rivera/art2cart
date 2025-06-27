<?php
require_once 'config/db.php';
require_once 'includes/User.php';
require_once 'includes/products.php';
require_once 'includes/Art2CartConfig.php';

session_start();

// Get base URL configuration
$baseHref = Art2CartConfig::getBaseUrl();
$baseUrl = Art2CartConfig::getBaseUrl();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $baseUrl . 'auth/auth.html');
    exit;
}

$user = new User($_SESSION['user_id']);
$userInfo = $user->getProfileInfo();

// Get purchased products
$purchasedProducts = getPurchasedProducts($_SESSION['user_id']);

// Get user's ratings and reviews
$productService = new ProductService();
$userRatings = $productService->getUserRatings($_SESSION['user_id']);

// Get database connection
$db = Database::getInstance();
$pdo = Database::getInstance()->getConnection();

// Redirect if user info not found
if (!$userInfo) {
    header('Location: ' . $baseUrl . 'auth/logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Art2Cart</title>
    
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
    <link rel="icon" type="image/png" sizes="512x512" href="static/images/favicon/android-chrome-512x512.png">    <!-- Web Manifest for PWA support -->
    <link rel="manifest" href="static/images/favicon/site.webmanifest">

    <!-- Optional theme color -->
    <meta name="theme-color" content="#ffffff">
      <link rel="stylesheet" href="static/css/var.css">
    <link rel="stylesheet" href="static/css/fonts.css">
    <link rel="stylesheet" href="static/css/template/header.css">
    <link rel="stylesheet" href="static/css/account.css">
</head>
<body>
    <?php include 'static/templates/header_new.php'; ?>
    
    <div class="account-container">
        <section class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo htmlspecialchars($userInfo['avatar_letter']); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($userInfo['username']); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($userInfo['email']); ?></p>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">Account Type</div>
                    <div class="info-value">
                        <?php echo implode(' & ', array_map('ucfirst', $userInfo['roles'])); ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-label">Member Since</div>
                    <div class="info-value">
                        <?php echo date('F j, Y', strtotime($userInfo['created_at'])); ?>
                    </div>
                </div>
            </div>              <?php
            // Initialize application variable
            $application = null;
              // Check for pending seller application
            if (!in_array('seller', $userInfo['roles'])) {
                $stmt = $pdo->prepare("
                    SELECT status, rejection_reason 
                    FROM seller_applications 
                    WHERE user_id = ? 
                    ORDER BY application_date DESC 
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            ?>

            <?php if ($application && $application['status'] === 'pending'): ?>
                <div class="alert alert-info">
                    <p>Your seller application is currently under review. We'll notify you once a decision has been made.</p>
                </div>
            <?php elseif ($application && $application['status'] === 'rejected'): ?>
                <div class="alert alert-danger">
                    <p>Your seller application was not approved.</p>
                    <?php if (!empty($application['rejection_reason'])): ?>
                        <p>Reason: <?php echo htmlspecialchars($application['rejection_reason']); ?></p>
                    <?php endif; ?>
                    <a href="auth/become_seller.php" class="btn btn-primary">Apply Again</a>
                </div>
            <?php elseif (!in_array('seller', $userInfo['roles'])): ?>
                <div class="alert alert-secondary">
                    <p>Want to sell your art? Become a seller today!</p>
                    <a href="auth/become_seller.php" class="btn btn-primary">Apply to Become a Seller</a>
                </div>            <?php endif; ?>

            <!-- My Purchases Section -->
            <section class="purchases-section">
                <h2>My Purchases</h2>
                <?php if (empty($purchasedProducts)): ?>                    <div class="empty-state">
                        <p>You haven't made any purchases yet.</p>
                        <a href="catalogue.php" class="btn btn-primary">Browse Products</a>
                    </div>
                <?php else: ?>                    <div class="purchases-grid">
                        <?php foreach ($purchasedProducts as $product): ?>
                            <div class="purchase-card" onclick="viewPurchasedProduct(<?php echo $product['id']; ?>)">
                                <div class="product-image">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>" />
                                </div>
                                <div class="product-details">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                    <p class="product-seller">by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                                    <div class="product-meta">
                                        <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                        <span class="purchase-date">
                                            Purchased: <?php echo date('M j, Y', strtotime($product['purchase_date'])); ?>
                                        </span>
                                    </div>
                                    <div class="rating">
                                        <?php 
                                        $rating = floatval($product['rating']);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                        <span class="rating-value">(<?php echo number_format($rating, 1); ?>)</span>
                                    </div>
                                </div>                                <div class="purchase-actions">
                                    <button class="view-product-btn" onclick="event.stopPropagation(); viewPurchasedProduct(<?php echo $product['id']; ?>)" title="View Product">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        View
                                    </button>
                                    <button class="download-btn" onclick="event.stopPropagation(); downloadFromAccount(<?php echo $product['order_id']; ?>, <?php echo $product['id']; ?>)" title="Download">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7,10 12,15 17,10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        Download                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- My Reviews Section -->
            <section class="reviews-section">
                <h2>My Reviews</h2>
                <?php if (empty($userRatings)): ?>
                    <div class="empty-state">
                        <p>You haven't left any reviews yet.</p>
                        <a href="catalogue.php" class="btn btn-primary">Browse Products to Review</a>
                    </div>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($userRatings as $rating): ?>
                            <div class="review-card" data-rating-id="<?php echo $rating['id']; ?>">
                                <div class="review-product-info">
                                    <img src="<?php echo htmlspecialchars($baseUrl . $rating['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($rating['product_title']); ?>" 
                                         class="review-product-image" />
                                    <div class="review-product-details">
                                        <h4 class="review-product-title">
                                            <a href="product_preview.php?id=<?php echo $rating['product_id']; ?>">
                                                <?php echo htmlspecialchars($rating['product_title']); ?>
                                            </a>
                                        </h4>
                                        <p class="review-product-price">₱<?php echo number_format($rating['price'], 2); ?></p>
                                    </div>
                                </div>
                                <div class="review-content">
                                    <div class="review-rating">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $rating['rating'] ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-value"><?php echo number_format($rating['rating'], 1); ?>/5</span>
                                        <span class="review-date">
                                            <?php echo date('M j, Y', strtotime($rating['created_at'])); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($rating['comment'])): ?>
                                        <div class="review-comment">
                                            <?php echo nl2br(htmlspecialchars($rating['comment'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="review-actions">
                                        <button class="edit-review-btn" onclick="editReview(<?php echo $rating['id']; ?>, <?php echo $rating['product_id']; ?>, <?php echo $rating['rating']; ?>, '<?php echo addslashes($rating['comment'] ?? ''); ?>')">
                                            Edit
                                        </button>
                                        <button class="delete-review-btn" onclick="deleteReview(<?php echo $rating['id']; ?>)">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>            <div class="action-buttons">
                <?php if (!in_array('seller', $userInfo['roles']) && (!$application || $application['status'] === 'rejected')): ?>
                    <a href="<?php echo $baseUrl; ?>auth/become_seller.php" class="action-btn primary-btn">Become a Seller</a>
                <?php elseif (in_array('seller', $userInfo['roles'])): ?>
                    <a href="<?php echo $baseUrl; ?>seller/dashboard.php" class="action-btn primary-btn">Seller Dashboard</a>
                <?php endif; ?>
                <button onclick="location.href='<?php echo $baseUrl; ?>auth/logout.php'" class="action-btn secondary-btn">Logout</button>
            </div>        </section>
    </div>

    <!-- Edit Review Modal -->
    <div id="editReviewModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeEditReviewModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Review</h3>
                <button class="close-btn" onclick="closeEditReviewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editReviewForm">
                    <input type="hidden" id="editRatingId">
                    <input type="hidden" id="editProductId">
                    
                    <div class="form-group">
                        <label>Rating:</label>
                        <div class="star-rating" id="editStarRating">
                            <span class="rating-star" data-rating="1" onclick="setEditRating(1)">★</span>
                            <span class="rating-star" data-rating="2" onclick="setEditRating(2)">★</span>
                            <span class="rating-star" data-rating="3" onclick="setEditRating(3)">★</span>
                            <span class="rating-star" data-rating="4" onclick="setEditRating(4)">★</span>
                            <span class="rating-star" data-rating="5" onclick="setEditRating(5)">★</span>
                        </div>
                        <input type="hidden" id="editRatingValue">
                    </div>
                    
                    <div class="form-group">
                        <label for="editComment">Comment (optional):</label>
                        <textarea id="editComment" rows="4" placeholder="Share your thoughts about this product..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="updateReview()" class="btn btn-primary">Update Review</button>
                        <button type="button" onclick="closeEditReviewModal()" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';

        function editReview(ratingId, productId, rating, comment) {
            document.getElementById('editRatingId').value = ratingId;
            document.getElementById('editProductId').value = productId;
            document.getElementById('editRatingValue').value = rating;
            document.getElementById('editComment').value = comment;
            
            // Set star rating
            setEditRating(rating);
            
            document.getElementById('editReviewModal').style.display = 'flex';
        }

        function setEditRating(rating) {
            const stars = document.querySelectorAll('#editStarRating .rating-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
            document.getElementById('editRatingValue').value = rating;
        }

        async function updateReview() {
            const ratingId = document.getElementById('editRatingId').value;
            const productId = document.getElementById('editProductId').value;
            const rating = document.getElementById('editRatingValue').value;
            const comment = document.getElementById('editComment').value;

            if (!rating) {
                alert('Please select a rating');
                return;
            }

            try {
                const response = await fetch(baseUrl + 'api/ratings.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rating_id: parseInt(ratingId),
                        product_id: parseInt(productId),
                        rating: parseFloat(rating),
                        comment: comment.trim()
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Review updated successfully!');
                    location.reload();
                } else {
                    alert(data.error || 'Failed to update review');
                }
            } catch (error) {
                console.error('Error updating review:', error);
                alert('An error occurred while updating your review');
            }
        }

        async function deleteReview(ratingId) {
            if (!confirm('Are you sure you want to delete this review?')) {
                return;
            }

            try {
                const response = await fetch(baseUrl + 'api/ratings.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rating_id: ratingId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Review deleted successfully!');
                    document.querySelector(`[data-rating-id="${ratingId}"]`).remove();
                    
                    // Check if no reviews left
                    const reviewsList = document.querySelector('.reviews-list');
                    if (reviewsList && reviewsList.children.length === 0) {
                        location.reload();
                    }
                } else {
                    alert(data.error || 'Failed to delete review');
                }
            } catch (error) {
                console.error('Error deleting review:', error);
                alert('An error occurred while deleting your review');
            }
        }        function closeEditReviewModal() {
            document.getElementById('editReviewModal').style.display = 'none';
        }

        // Functions for purchased products
        function viewPurchasedProduct(productId) {
            window.location.href = baseUrl + 'product_preview.php?id=' + productId + '&purchased=1';
        }

        function downloadFromAccount(orderId, productId) {
            window.location.href = baseUrl + 'download.php?order_id=' + orderId + '&product_id=' + productId;
        }
    </script>    <style>
        /* Purchase Cards Styles */
        .purchase-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .purchase-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #007bff;
        }

        .purchase-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .purchase-card:hover:before {
            transform: scaleX(1);
        }

        .product-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 15px 0 8px 0;
            line-height: 1.3;
        }

        .product-seller {
            color: #666;
            font-size: 14px;
            margin: 0 0 12px 0;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .product-price {
            font-size: 18px;
            font-weight: 600;
            color: #007bff;
        }

        .purchase-date {
            font-size: 12px;
            color: #888;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .rating .star {
            color: #ddd;
            font-size: 16px;
        }

        .rating .star.filled {
            color: #FBBC05;
        }

        .rating-value {
            font-size: 14px;
            color: #666;
        }

        .purchase-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .view-product-btn, .download-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .view-product-btn {
            background: #f8f9fa;
            color: #007bff;
            border: 1px solid #007bff;
        }

        .view-product-btn:hover {
            background: #007bff;
            color: white;
            transform: translateY(-1px);
        }

        .download-btn {
            background: #28a745;
            color: white;
        }

        .download-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        /* Reviews Section Styles */
        .reviews-section {
            margin-top: 30px;
        }

        .reviews-section h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .review-card {
            display: flex;
            gap: 20px;
            padding: 20px;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .review-product-info {
            display: flex;
            gap: 15px;
            min-width: 200px;
        }

        .review-product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .review-product-details h4 {
            margin: 0 0 5px 0;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 16px;
        }

        .review-product-details h4 a {
            color: #333;
            text-decoration: none;
        }

        .review-product-details h4 a:hover {
            color: #007bff;
        }

        .review-product-price {
            margin: 0;
            color: #666;
            font-weight: 500;
        }

        .review-content {
            flex: 1;
        }

        .review-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .review-rating .stars {
            display: flex;
            gap: 2px;
        }

        .review-rating .star {
            color: #ddd;
            font-size: 16px;
        }

        .review-rating .star.filled {
            color: #FBBC05;
        }

        .rating-value {
            font-weight: 500;
            color: #333;
        }

        .review-date {
            color: #666;
            font-size: 14px;
        }

        .review-comment {
            margin: 10px 0;
            line-height: 1.5;
            color: #555;
        }

        .review-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .edit-review-btn, .delete-review-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .edit-review-btn {
            background: #f8f9fa;
            color: #007bff;
            border: 1px solid #007bff;
        }

        .edit-review-btn:hover {
            background: #007bff;
            color: white;
        }

        .delete-review-btn {
            background: #f8f9fa;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .delete-review-btn:hover {
            background: #dc3545;
            color: white;
        }

        /* Modal Styles */
        .modal {
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
            max-width: 500px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }

        .star-rating .rating-star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating .rating-star:hover,
        .star-rating .rating-star.active {
            color: #FBBC05;
        }

        #editComment {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        @media (max-width: 768px) {
            .review-card {
                flex-direction: column;
                gap: 15px;
            }
            
            .review-product-info {
                min-width: auto;
            }
        }
    </style>
</body>
</html>
