<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Cart.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
if (!$loggedIn) {
    header('Location: /Art2Cart/auth/auth.html');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title><?php echo $pageTitle; ?></title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" />
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
                <a href="/Art2Cart/catalogue.php">Browse Artworks</a>
            </div>
        <?php else: ?>
            <!-- Cart with Items -->
            <div class="cart-content">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>                        <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="item-image">
                                <?php 
                                $imagePath = $item['file_path'] ?? '';
                                // If it's an uploaded file (starts with 'uploads/') or static file (starts with 'static/')
                                if (strpos($imagePath, 'uploads/') === 0 || strpos($imagePath, 'static/') === 0) {
                                    $displayPath = '/Art2Cart/' . $imagePath;
                                } 
                                // If it already starts with /Art2Cart/
                                else if (strpos($imagePath, '/Art2Cart/') === 0) {
                                    $displayPath = $imagePath;
                                }
                                // Default fallback image
                                else {
                                    $displayPath = '/Art2Cart/static/images/products/sample.jpg';
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($displayPath); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>
                            <div class="item-details">
                                <h3 class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p class="item-artist">by <?php echo htmlspecialchars($item['seller_name']); ?></p>
                                <div class="item-category <?php echo strtolower(str_replace(' ', '-', $item['category_slug'])); ?>">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </div>
                                <div class="item-controls">
                                    <div class="quantity-controls">
                                        <button class="quantity-btn minus" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                            <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                                                <rect x="6" y="12" width="14" height="2" fill="#5A607F"/>
                                            </svg>
                                        </button>
                                        <span class="quantity"><?php echo $item['quantity']; ?></span>
                                        <button class="quantity-btn plus" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                            <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                                                <rect x="6" y="12" width="14" height="2" fill="#5A607F"/>
                                                <rect x="12" y="6" width="2" height="14" fill="#5A607F"/>
                                            </svg>
                                        </button>
                                    </div>
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
                    <h2>Order Summary</h2>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="summary-line" data-product-id="<?php echo $item['product_id']; ?>">
                            <span><?php echo htmlspecialchars($item['product_name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
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
        <?php endif; ?>
    </main>

    <?php include 'static/templates/footer_new.html'; ?>

    <script>
        // Update item quantity
        function updateQuantity(productId, newQuantity) {
            if (newQuantity < 1) {
                removeFromCart(productId);
                return;
            }

            fetch('/Art2Cart/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&product_id=${productId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update quantity display
                    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
                    const quantitySpan = cartItem.querySelector('.quantity');
                    quantitySpan.textContent = newQuantity;
                    
                    // Update summary
                    updateCartSummary();
                    
                    // Update header cart count if exists
                    updateHeaderCartCount(data.cart_count);
                } else {
                    alert(data.message || 'Failed to update cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the cart');
            });
        }

        // Remove item from cart
        function removeFromCart(productId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }

            fetch('/Art2Cart/api/cart.php', {
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
        }

        // Update cart summary totals
        function updateCartSummary() {
            let totalItems = 0;
            let totalAmount = 0;

            document.querySelectorAll('.cart-item').forEach(item => {
                const productId = item.dataset.productId;
                const quantity = parseInt(item.querySelector('.quantity').textContent);
                const priceText = item.querySelector('.item-price').textContent;
                const price = parseFloat(priceText.replace('₱', '').replace(',', ''));
                
                totalItems += quantity;
                totalAmount += price * quantity;
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
        }

        // Proceed to checkout
        function proceedToCheckout() {
            window.location.href = '/Art2Cart/checkout.php';
        }

        // Initialize cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Any additional initialization code
        });
    </script>
</body>
</html>
