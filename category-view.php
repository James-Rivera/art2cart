<?php
session_start();
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

require_once 'config/db.php';
require_once 'includes/products.php';
require_once 'includes/Art2CartConfig.php';
// ...existing code...

// Initialize error reporting
Database::initErrorReporting();

// Get base URL configuration
$baseHref = Art2CartConfig::getBaseUrl();
$baseUrl = Art2CartConfig::getBaseUrl();

// Get category slug from URL
$categorySlug = isset($_GET['category']) ? $_GET['category'] : '';

// Get category details and products
$category = null;
$products = [];

if ($categorySlug) {
    // Get category details
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$categorySlug]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            $products = getProductsByCategory($categorySlug, $currentUserId);
        }
    } catch (PDOException $e) {
        error_log("Error fetching category: " . $e->getMessage());
    }
}

// Handle invalid category
if (!$category) {
    header("Location: catalogue.php");
    exit;
}

// Pagination
$productsPerPage = 12; // Show more products per page in category view
$totalProducts = count($products);
$totalPages = ceil($totalProducts / $productsPerPage);
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages));
$start = ($currentPage - 1) * $productsPerPage;
$displayProducts = array_slice($products, $start, $productsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <title><?php echo htmlspecialchars($category['name']); ?> - Art2Cart</title>

    <!-- Base URL configuration -->
    <base href="<?php echo htmlspecialchars($baseHref); ?>">    <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/catalogue/cata.css">
    <link rel="stylesheet" href="static/css/template/header.css">
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" /><!-- Favicons (same as catalogue.php) -->
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
</head>
<body>
    <!-- Container for header -->
    <div id="header"></div>

    <main>
        <!-- Header Hero but with category name -->
        <section class="header-hero">
            <img class="header-image" src="static/images/catalogue/image.png">
            <div class="cata-head-text">
                <div class="products1"><?php echo htmlspecialchars($category['name']); ?></div>
                <div class="desc-contianer">
                    <h1 class="home"><a href="catalogue.php" style="color: white; text-decoration: none;">Back</a></h1>
                    <img class="arrow" src="static/images/catalogue/arrow0.svg"/>
                    <div class="products2"><?php echo htmlspecialchars($category['name']); ?></div>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section class="products">
            <div class="category-section">
                <div class="category-header">
                    <h2 class="category-title"><?php echo strtoupper($category['name']); ?></h2>
                    <a href="catalogue.php" class="back-button">← Back to All Categories</a>
                </div>                <div class="product-grid">
                    <?php foreach ($displayProducts as $product): ?>
                        <article class="product-card" onclick="viewProduct(<?php echo $product['id']; ?>)" style="cursor: pointer;">
                            <img class="product-image" 
                                 src="<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <div class="hover-content">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <div class="rating">
                                    <img src="static/images/star.svg" alt="star" class="star-icon">
                                    <span class="rating-score"><?php echo number_format($product['rating'], 1); ?></span>
                                    <span class="downloads">(<?php echo $product['downloads']; ?> downloads)</span>
                                </div>
                                <div class="price-row">
                                    <span class="price">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>" onclick="event.stopPropagation();">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button 
                            onclick="changePage(<?php echo $i; ?>)"
                            <?php echo $i === $currentPage ? 'class="active"' : ''; ?>
                        >
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>    <div id="footer"></div>
    <script>
        // Pass PHP base URL to JavaScript
        window.baseHref = '<?php echo $baseHref; ?>';
    </script>
    <script src="static/js/load.js"></script>

    <script>        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    addToCart(productId);
                });
            });
        });
        
        function changePage(pageNumber) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', pageNumber);
            window.location.href = url.toString();
        }        // Function to redirect to product preview page
        function viewProduct(productId) {
            window.location.href = `product_preview.php?id=${productId}`;
        }

        function addToCart(productId) {
            // Check if user is logged in
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('Product added to cart!', 'success');
                    
                    // Update cart count in header if exists
                    updateHeaderCartCount(data.cart_count);
                } else {                    if (data.message.includes('log in')) {
                        // Redirect to login if not logged in
                        window.location.href = 'auth/auth.html';
                    } else {
                        showNotification(data.message || 'Failed to add item to cart', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while adding the item to cart', 'error');
            });
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
                background: ${type === 'success' ? '#10B981' : '#EF4444'};
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

        // Add CSS animations
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
        document.head.appendChild(style);
    </script>
</body>
</html>
