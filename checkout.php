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
$cartItems = $cart->getCartItems($user_id);
$cartTotal = $cart->getCartTotal($user_id);
$cartCount = $cart->getCartCount($user_id);

// Filter cart items based on selected items from sessionStorage (will be handled by JavaScript)
// For server-side, we'll work with all cart items and filter on frontend
$selectedItems = $cartItems; // Default to all items
$selectedTotal = $cartTotal; // Default to total

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: ' . $baseUrl . 'cart.php');
    exit;
}

$pageTitle = "Checkout - Art2Cart";
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
    <link rel="manifest" href="static/images/favicon/site.webmanifest">

    <!-- Optional theme color -->
    <meta name="theme-color" content="#ffffff">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" />    <link rel="stylesheet" href="static/css/cart.css" />
      <script>
        window.baseHref = '<?php echo $baseHref; ?>';
    </script>
    
    <!-- Load global dark mode functionality -->
    <script src="static/js/load.js?v=<?php echo time(); ?>"></script>
      <style>
        .checkout-container {
            margin-top: 89px;
            padding: 48px 28px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            background: #FFFFFF;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 60px;
            align-items: start;
        }
        
        .checkout-form {
            background: #FFFFFF;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .form-section h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 20px;
            color: #000;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #FFD700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .payment-methods {
            display: grid;
            gap: 12px;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.05);
        }
        
        .payment-method.selected {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.1);
        }
        
        .payment-method input[type="radio"] {
            margin-right: 12px;
            width: auto;
        }
        
        .payment-method-info {
            flex: 1;
        }
        
        .payment-method-name {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 16px;
            color: #000;
            margin-bottom: 4px;
        }
        
        .payment-method-desc {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #6B7280;
        }
        
        .order-summary-checkout {
            background: #FFFFFF;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 120px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-item-name {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #374151;
            flex: 1;
        }
        
        .summary-item-price {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 14px;
            color: #000;
        }
        
        .summary-total-checkout {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-top: 20px;
            border-top: 2px solid #FFD700;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 20px;
            color: #000;
        }
        
        .place-order-btn {
            width: 100%;
            padding: 16px;
            background: #FFD700;
            border: none;
            border-radius: 12px;
            font-family: 'Karla', sans-serif;
            font-weight: 600;
            font-size: 18px;
            color: #000;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .place-order-btn:hover {
            background: #E6C200;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }
        
        .place-order-btn:disabled {
            background: #D1D5DB;
            color: #9CA3AF;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        @media (max-width: 1024px) {
            .checkout-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .order-summary-checkout {
                position: static;
            }
        }
          @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .checkout-container {
                padding: 32px 20px;
            }
        }
        
        /* Dark Mode Styles */
        [data-theme="dark"] {
            color-scheme: dark;
        }
        
        [data-theme="dark"] body {
            background: #0F172A;
            color: #F1F5F9;
        }
        
        [data-theme="dark"] .checkout-container {
            background: #0F172A;
        }
        
        [data-theme="dark"] .checkout-form {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }
        
        [data-theme="dark"] .order-summary-checkout {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }
        
        [data-theme="dark"] .form-section h3 {
            color: #F1F5F9;
        }
        
        [data-theme="dark"] .form-group label {
            color: #CBD5E1;
        }
        
        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group select {
            background: #334155;
            border-color: rgba(255, 255, 255, 0.2);
            color: #F1F5F9;
        }
        
        [data-theme="dark"] .form-group input:focus,
        [data-theme="dark"] .form-group select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
        }
        
        [data-theme="dark"] .payment-method {
            background: #334155;
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        [data-theme="dark"] .payment-method:hover {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.1);
        }
        
        [data-theme="dark"] .payment-method.selected {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.15);
        }
        
        [data-theme="dark"] .payment-method-name {
            color: #F1F5F9;
        }
        
        [data-theme="dark"] .payment-method-desc {
            color: #94A3B8;
        }
        
        [data-theme="dark"] .summary-item {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-theme="dark"] .summary-item-name {
            color: #CBD5E1;
        }
        
        [data-theme="dark"] .summary-item-price {
            color: #F1F5F9;
        }
        
        [data-theme="dark"] .summary-total-checkout {
            color: #F1F5F9;
        }
        
        [data-theme="dark"] h1 {
            color: #F1F5F9 !important;
        }
        
        [data-theme="dark"] h3 {
            color: #F1F5F9 !important;
        }
          [data-theme="dark"] .place-order-btn:disabled {
            background: #475569;
            color: #64748B;
        }

        /* Payment Modal Styles */
        .payment-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #FFFFFF;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 24px 0 24px;
            border-bottom: 1px solid #E5E7EB;
            margin-bottom: 24px;
        }

        .modal-header h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 20px;
            color: #000;
            margin: 0;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            color: #9CA3AF;
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #374151;
        }

        .modal-body {
            padding: 0 24px 24px 24px;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding: 0 24px 24px 24px;
            border-top: 1px solid #E5E7EB;
            margin-top: 24px;
            padding-top: 24px;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: #FFD700;
            color: #000;
        }

        .btn-primary:hover {
            background: #E6C200;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #F3F4F6;
            color: #374151;
            border: 1px solid #D1D5DB;
        }

        .btn-secondary:hover {
            background: #E5E7EB;
        }

        .paypal-info, .gcash-info {
            text-align: center;
            padding: 20px;
            background: #F9FAFB;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .paypal-logo h2 {
            font-family: 'Poppins', sans-serif;
            color: #003087;
            font-size: 32px;
            margin-bottom: 16px;
        }

        .paypal-agreement {
            margin-top: 20px;
            text-align: left;
        }

        .paypal-agreement label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
        }

        .paypal-agreement input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        /* Dark mode for modals */
        [data-theme="dark"] .modal-content {
            background: #1E293B;
            color: #F1F5F9;
        }

        [data-theme="dark"] .modal-header {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .modal-header h3 {
            color: #F1F5F9;
        }

        [data-theme="dark"] .modal-footer {
            border-top-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .paypal-info,
        [data-theme="dark"] .gcash-info {
            background: #334155;
        }

        [data-theme="dark"] .btn-secondary {
            background: #475569;
            color: #F1F5F9;
            border-color: #64748B;
        }

        [data-theme="dark"] .btn-secondary:hover {
            background: #64748B;
        }
    </style>
</head>
<body>
    <?php include 'static/templates/header_checkout.php'; ?>

    <main class="checkout-container">
        <h1 style="font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 32px; margin-bottom: 40px; color: #000;">Checkout</h1>
        
        <div class="checkout-content">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <form id="checkout-form">
                    <!-- Billing Information -->
                    <div class="form-section">
                        <h3>Billing Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="address">Address *</label>
                            <input type="text" id="address" name="address" required>
                        </div>                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="state_province">State/Province</label>
                                <input type="text" id="state_province" name="state_province">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="postal_code">Postal Code *</label>
                                <input type="text" id="postal_code" name="postal_code" required>
                            </div>
                            <div class="form-group">
                                <label for="country">Country *</label>
                                <select id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="Philippines">Philippines</option>
                                    <option value="United States">United States</option>
                                    <option value="Canada">Canada</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="Australia">Australia</option>
                                </select>
                            </div>
                        </div>
                    </div>                    <!-- Payment Method -->
                    <div class="form-section">
                        <h3>Payment Method</h3>
                        <div class="payment-methods">
                            <div class="payment-method" onclick="selectPayment('paypal')">
                                <input type="radio" id="paypal" name="payment_method" value="paypal" required>
                                <div class="payment-method-info">
                                    <div class="payment-method-name">PayPal</div>
                                    <div class="payment-method-desc">Pay securely with your PayPal account</div>
                                </div>
                            </div>
                            <div class="payment-method" onclick="selectPayment('card')">
                                <input type="radio" id="card" name="payment_method" value="card" required>
                                <div class="payment-method-info">
                                    <div class="payment-method-name">Credit/Debit Card</div>
                                    <div class="payment-method-desc">Visa, Mastercard, American Express</div>
                                </div>
                            </div>
                            <div class="payment-method" onclick="selectPayment('gcash')">
                                <input type="radio" id="gcash" name="payment_method" value="gcash" required>
                                <div class="payment-method-info">
                                    <div class="payment-method-name">GCash</div>
                                    <div class="payment-method-desc">Pay with GCash mobile wallet</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details Section (Initially Hidden) -->
                    <div class="form-section" id="payment-details-section" style="display: none;">
                        <h3 id="payment-details-title">Payment Details</h3>
                        <div id="payment-details-content">
                            <!-- Payment details will be injected here -->
                        </div>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary-checkout">
                <h3 style="font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 20px; margin-bottom: 24px; color: #000;">Order Summary</h3>                  <?php foreach ($cartItems as $item): ?>
                    <div class="summary-item" data-product-id="<?php echo $item['product_id']; ?>">
                        <div class="summary-item-name">
                            <?php echo htmlspecialchars($item['product_name']); ?>
                        </div>
                        <div class="summary-item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-total-checkout">
                    <span>Total</span>
                    <span>₱<?php echo number_format($cartTotal, 2); ?></span>
                </div>
                
                <button type="button" class="place-order-btn" onclick="placeOrder()">
                    Place Order
                </button>
            </div>
        </div>    </main>

    <!-- Payment Modals -->
    <!-- Card Payment Modal -->
    <div id="card-payment-modal" class="payment-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Credit/Debit Card Payment</h3>
                <span class="close-modal" onclick="closePaymentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="card-number">Card Number *</label>
                    <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="card-expiry">Expiry Date *</label>
                        <input type="text" id="card-expiry" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label for="card-cvv">CVV *</label>
                        <input type="text" id="card-cvv" placeholder="123" maxlength="4" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="card-name">Cardholder Name *</label>
                    <input type="text" id="card-name" placeholder="John Doe" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closePaymentModal()">Cancel</button>
                <button type="button" class="btn-primary" onclick="confirmCardPayment()">Confirm Payment</button>
            </div>
        </div>
    </div>

    <!-- GCash Payment Modal -->
    <div id="gcash-payment-modal" class="payment-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>GCash Payment</h3>
                <span class="close-modal" onclick="closePaymentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="gcash-number">GCash Mobile Number *</label>
                    <input type="tel" id="gcash-number" placeholder="09123456789" maxlength="11" required>
                </div>
                <div class="gcash-info">
                    <p><strong>Payment Amount:</strong> <span id="gcash-amount"></span></p>
                    <p><small>You will receive an SMS confirmation after payment verification.</small></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closePaymentModal()">Cancel</button>
                <button type="button" class="btn-primary" onclick="confirmGCashPayment()">Send Payment Request</button>
            </div>
        </div>
    </div>

    <!-- PayPal Payment Modal -->
    <div id="paypal-payment-modal" class="payment-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>PayPal Payment</h3>
                <span class="close-modal" onclick="closePaymentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="paypal-info">
                    <div class="paypal-logo">
                        <h2>PayPal</h2>
                    </div>
                    <p><strong>Payment Amount:</strong> <span id="paypal-amount"></span></p>
                    <p>You will be redirected to PayPal to complete your payment securely.</p>
                    <div class="paypal-agreement">
                        <label>
                            <input type="checkbox" id="paypal-agree" required>
                            I agree to proceed with PayPal payment processing
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closePaymentModal()">Cancel</button>
                <button type="button" class="btn-primary" onclick="confirmPayPalPayment()">Continue with PayPal</button>
            </div>
        </div>
    </div>    <script>
        let selectedPaymentMethod = null;
        let selectedCartItems = [];
        let paymentConfirmed = false;

        // Initialize checkout page
        document.addEventListener('DOMContentLoaded', function() {
            // Get selected items from sessionStorage
            const storedItems = sessionStorage.getItem('selectedCartItems');
            if (storedItems) {
                selectedCartItems = JSON.parse(storedItems);
                console.log('Selected cart items:', selectedCartItems);
                
                // Filter and update order summary
                updateOrderSummary();
            } else {
                // If no selected items, redirect back to cart
                alert('No items selected for checkout. Redirecting to cart.');
                window.location.href = 'cart.php';
            }
        });

        function updateOrderSummary() {
            // Hide all summary items first
            const summaryItems = document.querySelectorAll('.summary-item');
            let total = 0;
            let visibleCount = 0;

            summaryItems.forEach(item => {
                const productId = parseInt(item.dataset.productId);
                if (selectedCartItems.includes(productId)) {
                    item.style.display = 'flex';
                    const priceText = item.querySelector('.summary-item-price').textContent;
                    const price = parseFloat(priceText.replace('₱', '').replace(/,/g, ''));
                    total += price;
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Update total
            document.querySelector('.summary-total-checkout span:last-child').textContent = 
                '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Update modal amounts
            document.getElementById('gcash-amount').textContent = '₱' + total.toFixed(2);
            document.getElementById('paypal-amount').textContent = '₱' + total.toFixed(2);
        }

        function selectPayment(method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            document.querySelector(`#${method}`).closest('.payment-method').classList.add('selected');
            
            // Check the radio button
            document.querySelector(`#${method}`).checked = true;
            
            selectedPaymentMethod = method;
            
            // Show payment modal immediately
            showPaymentModal(method);
        }

        function showPaymentModal(method) {
            const modalId = method + '-payment-modal';
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                paymentConfirmed = false;
            }
        }

        function closePaymentModal() {
            document.querySelectorAll('.payment-modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }

        function confirmCardPayment() {
            const cardNumber = document.getElementById('card-number').value;
            const cardExpiry = document.getElementById('card-expiry').value;
            const cardCvv = document.getElementById('card-cvv').value;
            const cardName = document.getElementById('card-name').value;

            if (!cardNumber || !cardExpiry || !cardCvv || !cardName) {
                alert('Please fill in all card details.');
                return;
            }

            // Mock validation
            if (cardNumber.replace(/\s/g, '').length < 16) {
                alert('Please enter a valid card number.');
                return;
            }

            paymentConfirmed = true;
            closePaymentModal();
            alert('Card payment details confirmed! You can now place your order.');
        }

        function confirmGCashPayment() {
            const gcashNumber = document.getElementById('gcash-number').value;

            if (!gcashNumber) {
                alert('Please enter your GCash mobile number.');
                return;
            }

            if (gcashNumber.length !== 11 || !gcashNumber.startsWith('09')) {
                alert('Please enter a valid GCash mobile number (e.g., 09123456789).');
                return;
            }

            paymentConfirmed = true;
            closePaymentModal();
            alert('GCash payment details confirmed! You can now place your order.');
        }

        function confirmPayPalPayment() {
            const paypalAgree = document.getElementById('paypal-agree').checked;

            if (!paypalAgree) {
                alert('Please confirm that you agree to proceed with PayPal payment.');
                return;
            }

            paymentConfirmed = true;
            closePaymentModal();
            alert('PayPal payment confirmed! You can now place your order.');
        }

        function placeOrder() {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Check if payment method is selected and confirmed
            if (!selectedPaymentMethod) {
                alert('Please select a payment method.');
                return;
            }

            if (!paymentConfirmed) {
                alert('Please complete the payment details first.');
                showPaymentModal(selectedPaymentMethod);
                return;
            }
            
            // Disable button
            const btn = document.querySelector('.place-order-btn');
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            // Add action and selected items to form data
            formData.append('action', 'place_order');
            formData.append('selected_items', JSON.stringify(selectedCartItems));
            
            // Debug: Log form data
            console.log('Submitting order with data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            
            fetch('api/checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    // Clear selected items from sessionStorage
                    sessionStorage.removeItem('selectedCartItems');
                    alert('Order placed successfully!');
                    window.location.href = window.baseHref + 'order-confirmation.php?order_id=' + data.order_id;
                } else {
                    alert(data.message || 'Failed to place order');
                    btn.disabled = false;
                    btn.textContent = 'Place Order';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while placing the order: ' + error.message);
                btn.disabled = false;
                btn.textContent = 'Place Order';
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('payment-modal')) {
                closePaymentModal();
            }
        }

        // Format card number input
        document.addEventListener('input', function(e) {
            if (e.target.id === 'card-number') {
                let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
            }
            
            if (e.target.id === 'card-expiry') {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
