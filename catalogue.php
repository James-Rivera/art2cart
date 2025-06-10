<?php
session_start();
require_once 'config/db.php';
require_once 'includes/products.php';

// Initialize error reporting
Database::initErrorReporting();

// Get current user ID if logged in
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get all categories with their products
$categories = getAllCategories();

// Store products for each category (excluding purchased products for logged-in users)
$categoryProducts = [];
foreach ($categories as $category) {
    $categoryProducts[$category['slug']] = getProductsByCategory($category['slug'], $currentUserId);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />    <meta name="description" content="Art2Cart - Digital Art Marketplace" />
    <title>Art 2 Cart - Catalogue</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/catalogue/cata.css" />
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" />

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

</head>

<body>

    <!-- Container for header -->
    <div id="header"></div>

    <main>

        <!-- Header Hero-->        <section class="header-hero"> <img class="header-image" src="/Art2Cart/static/images/catalogue/image.png" />
            <div class="cata-head-text">
                <div class="products1">Products</div>
                <div class="desc-contianer">
                    <h1 class="home">Home</h1>
                    <img class="arrow" src="/Art2Cart/static/images/catalogue/arrow0.svg" />
                    <div class="products2">Products</div>
                </div>
            </div>
        </section> <!-- Products Section -->
        <section class="products"> 
            <?php foreach ($categories as $category):
                                        $displayProducts = array_slice($categoryProducts[$category['slug']], 0, 4);
                                        $totalProducts = count($categoryProducts[$category['slug']]);
                                    ?>
                <div class="category-section">
                    <div class="category-header">
                        <h2 class="category-title"><?php echo strtoupper($category['name']); ?></h2>
                        <?php if ($totalProducts > 4): ?>
                            <a href="category-view.php?category=<?php echo $category['slug']; ?>" class="view-all">View All</a>
                        <?php endif; ?>
                    </div>                    <div class="product-grid">
                        <?php foreach ($displayProducts as $product): ?>
                            <article class="product-card" onclick="viewProduct(<?php echo $product['id']; ?>)" style="cursor: pointer;">
                                <img class="product-image"
                                    src="<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['title']); ?>" />
                                <div class="hover-content">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                    <div class="rating">                                        <img src="/Art2Cart/static/images/star.svg" alt="star" class="star-icon" />
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
                            </article><?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <div id="footer"></div>
    <script src="static/js/load.js"></script>    <!-- Add cart functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listeners to all "Add to Cart" buttons
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    addToCart(productId);
                });
            });
        });

        // Function to redirect to product preview page
        function viewProduct(productId) {
            window.location.href = `/Art2Cart/product_preview.php?id=${productId}`;
        }

        function addToCart(productId) {
            // Check if user is logged in
            fetch('/Art2Cart/api/cart.php', {
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
                } else {
                    if (data.message.includes('log in')) {
                        // Redirect to login if not logged in
                        window.location.href = '/Art2Cart/auth/auth.html';
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