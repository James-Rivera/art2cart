<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Cart.php';
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
$db = Database::getInstance();
$cart = new Cart($db);

// Get cart data
$cartItems = $cart->getCartItems($user_id);
$cartTotal = $cart->getCartTotal($user_id);
$cartCount = $cart->getCartCount($user_id);

$pageTitle = "Shopping Cart - Art2Cart";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">      <title><?php echo $pageTitle; ?></title>

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

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" />
    <link rel="stylesheet" href="static/css/template/header.css" />
    <link rel="stylesheet" href="static/css/cart.css" />
</head>
<body>
    <?php include 'static/templates/header_new.php'; ?>

    <!-- Main Cart Content -->
    <main class="cart-container">
        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="cart-empty">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any artworks to your cart yet.</p>
                <a href="catalogue.php">Browse Artworks</a>
            </div>
        <?php else: ?>
            <!-- Cart with Items -->
            <div class="cart-content">                <!-- Cart Items -->
                <div class="cart-items">
                    <!-- Select All Checkbox -->                    <div class="select-all-container">
                        <label class="select-all-label">
                            <input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll()">
                            <span class="checkmark"></span>
                            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                            Select All Items for Checkout
                        </label>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>                        <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <!-- Item Selection Checkbox -->
                            <div class="item-checkbox">
                                <label class="checkbox-label">
                                    <input type="checkbox" class="item-select-checkbox" data-product-id="<?php echo $item['product_id']; ?>" onchange="updateCartSelection()">
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                            <div class="item-image"><?php 
                                $imagePath = $item['file_path'] ?? '';
                                // If it's an uploaded file (starts with 'uploads/') or static file (starts with 'static/')
                                if (strpos($imagePath, 'uploads/') === 0 || strpos($imagePath, 'static/') === 0) {                                $displayPath = $imagePath;
                                } 
                                // If it already has full path, make it relative
                                else if (strpos($imagePath, '/Art2Cart/') === 0) {
                                    $displayPath = substr($imagePath, 10); // Remove '/Art2Cart/' prefix
                                }
                                // If it has base URL, make it relative
                                else if (strpos($imagePath, $baseHref) === 0) {
                                    $displayPath = substr($imagePath, strlen($baseHref));
                                }
                                // Default fallback image
                                else {
                                    $displayPath = 'static/images/products/sample.jpg';
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($displayPath); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>                            <div class="item-details">
                                <h3 class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p class="item-artist">by <?php echo htmlspecialchars($item['seller_name']); ?></p>
                                <div class="item-category <?php echo strtolower(str_replace(' ', '-', $item['category_slug'])); ?>">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </div>
                            </div>
                            <div class="item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                            <button class="delete-btn" onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                <svg width="23" height="23" viewBox="0 0 23 23" fill="none">
                                    <path d="M3 6h17M8 6V4a2 2 0 012-2h2a2 2 0 012 2v2m3 0v11a2 2 0 01-2 2H7a2 2 0 01-2-2V6h12zM10 11v6M14 11v6" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>                    <?php foreach ($cartItems as $item): ?>
                        <div class="summary-line" data-product-id="<?php echo $item['product_id']; ?>">
                            <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                            <span>₱<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="summary-line">
                        <span>Items</span>
                        <span id="total-items"><?php echo $cartCount; ?></span>
                    </div>
                    <hr class="summary-divider">
                    <div class="summary-total">
                        <span>Total</span>
                        <span id="cart-total">₱<?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        <svg width="25" height="25" viewBox="0 0 25 25" fill="none">
                            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5 3H3m4 10v6a1 1 0 001 1h8a1 1 0 001-1v-6m-9 0h10" stroke="black" stroke-width="2"/>
                        </svg>
                        <span>Proceed to Checkout</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>    </main>

    <?php include 'static/templates/footer_new.php'; ?>

    <script>
        // Remove item from cart
        function removeFromCart(productId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }

            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove item from DOM
                    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
                    cartItem.remove();
                    
                    // Remove from summary
                    const summaryLine = document.querySelector(`.summary-line[data-product-id="${productId}"]`);
                    if (summaryLine) {
                        summaryLine.remove();
                    }
                    
                    // Update totals
                    updateCartSummary();
                    
                    // Update header cart count
                    updateHeaderCartCount(data.cart_count);
                    
                    // Check if cart is empty
                    if (data.cart_count === 0) {
                        location.reload(); // Reload to show empty cart message
                    }
                } else {
                    alert(data.message || 'Failed to remove item from cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the item');
            });
        }        // Update cart summary totals
        function updateCartSummary() {
            let totalItems = 0;
            let totalAmount = 0;

            document.querySelectorAll('.cart-item').forEach(item => {
                const priceText = item.querySelector('.item-price').textContent;
                const price = parseFloat(priceText.replace('₱', '').replace(',', ''));
                
                totalItems += 1; // Each item counts as 1 in digital marketplace
                totalAmount += price;
            });

            document.getElementById('total-items').textContent = totalItems;
            document.getElementById('cart-total').textContent = '₱' + totalAmount.toFixed(2);
        }

        // Update header cart count
        function updateHeaderCartCount(count) {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
            }
        }        // Proceed to checkout
        function proceedToCheckout() {
            const selectedItems = getSelectedItems();
            if (selectedItems.length === 0) {
                alert('Please select at least one item to checkout.');
                return;
            }
            
            // Store selected items in sessionStorage
            sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedItems));
            window.location.href = 'checkout.php';
        }

        // Get selected item IDs
        function getSelectedItems() {
            const selectedCheckboxes = document.querySelectorAll('.item-select-checkbox:checked');
            return Array.from(selectedCheckboxes).map(cb => parseInt(cb.dataset.productId));
        }

        // Toggle select all
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const itemCheckboxes = document.querySelectorAll('.item-select-checkbox');
            
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateCartSelection();
        }

        // Update cart selection and visual feedback
        function updateCartSelection() {
            const selectedItems = getSelectedItems();
            const totalItems = document.querySelectorAll('.item-select-checkbox').length;
            
            // Update select all checkbox state
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            selectAllCheckbox.checked = selectedItems.length === totalItems;
            selectAllCheckbox.indeterminate = selectedItems.length > 0 && selectedItems.length < totalItems;
            
            // Update visual feedback for selected items
            document.querySelectorAll('.cart-item').forEach(item => {
                const productId = parseInt(item.dataset.productId);
                const checkbox = item.querySelector('.item-select-checkbox');
                
                if (checkbox.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
            
            // Update order summary to show only selected items
            updateOrderSummarySelection();
        }

        // Update order summary to reflect selected items
        function updateOrderSummarySelection() {
            const selectedItems = getSelectedItems();
            let selectedTotal = 0;
            let selectedCount = 0;
            
            // Show/hide summary lines based on selection
            document.querySelectorAll('.summary-line[data-product-id]').forEach(line => {
                const productId = parseInt(line.dataset.productId);
                if (selectedItems.includes(productId)) {
                    line.style.display = 'flex';
                    // Get price from the line
                    const priceText = line.querySelector('span:last-child').textContent;
                    const price = parseFloat(priceText.replace('₱', '').replace(/,/g, ''));
                    selectedTotal += price;
                    selectedCount += 1;
                } else {
                    line.style.display = 'none';
                }
            });
            
            // Update totals
            document.getElementById('total-items').textContent = selectedCount;
            document.getElementById('cart-total').textContent = '₱' + selectedTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }        // Initialize cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Select all items by default
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = true;
                toggleSelectAll();
            }

            // Add click event listeners to cart items
            document.querySelectorAll('.cart-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Don't trigger if clicking on delete button or checkbox
                    if (e.target.closest('.delete-btn') || e.target.closest('.item-checkbox')) {
                        return;
                    }
                    
                    // Toggle the checkbox
                    const checkbox = this.querySelector('.item-select-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateCartSelection();
                    }
                });
            });
        });
    </script>
</body>
</html>
