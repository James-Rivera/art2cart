<?php
require_once 'config/db.php';
require_once 'includes/User.php';
require_once 'includes/products.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Art2Cart/auth/auth.html');
    exit;
}

$user = new User($_SESSION['user_id']);
$userInfo = $user->getProfileInfo();

// Get purchased products
$purchasedProducts = getPurchasedProducts($_SESSION['user_id']);

// Get database connection
$db = Database::getInstance();
$pdo = Database::getInstance()->getConnection();

// Redirect if user info not found
if (!$userInfo) {
    header('Location: /Art2Cart/auth/logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Art2Cart</title>
    <link rel="stylesheet" href="/Art2Cart/static/css/var.css">
    <link rel="stylesheet" href="/Art2Cart/static/css/fonts.css">
    <link rel="stylesheet" href="/Art2Cart/static/css/account.css">
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
                    <a href="auth/become_seller.html" class="btn btn-primary">Apply Again</a>
                </div>
            <?php elseif (!in_array('seller', $userInfo['roles'])): ?>
                <div class="alert alert-secondary">
                    <p>Want to sell your art? Become a seller today!</p>
                    <a href="auth/become_seller.html" class="btn btn-primary">Apply to Become a Seller</a>
                </div>            <?php endif; ?>

            <!-- My Purchases Section -->
            <section class="purchases-section">
                <h2>My Purchases</h2>
                <?php if (empty($purchasedProducts)): ?>
                    <div class="empty-state">
                        <p>You haven't made any purchases yet.</p>
                        <a href="/Art2Cart/catalogue.php" class="btn btn-primary">Browse Products</a>
                    </div>
                <?php else: ?>
                    <div class="purchases-grid">
                        <?php foreach ($purchasedProducts as $product): ?>
                            <div class="purchase-card">
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
                                </div>
                                <div class="purchase-actions">
                                    <a href="/Art2Cart/download.php?order_id=<?php echo $product['order_id']; ?>&product_id=<?php echo $product['id']; ?>" 
                                       class="download-btn" title="Download">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="7,10 12,15 17,10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="action-buttons">
                <?php if (!in_array('seller', $userInfo['roles']) && (!$application || $application['status'] === 'rejected')): ?>
                    <a href="/Art2Cart/auth/become_seller.html" class="action-btn primary-btn">Become a Seller</a>
                <?php elseif (in_array('seller', $userInfo['roles'])): ?>
                    <a href="/Art2Cart/seller/dashboard.php" class="action-btn primary-btn">Seller Dashboard</a>
                <?php endif; ?>
                <button onclick="location.href='/Art2Cart/auth/logout.php'" class="action-btn secondary-btn">Logout</button>
            </div>
        </section>
    </div>
</body>
</html>
