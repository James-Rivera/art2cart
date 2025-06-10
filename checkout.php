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
$cartItems = $cart->getCartItems($user_id);
$cartTotal = $cart->getCartTotal($user_id);
$cartCount = $cart->getCartCount($user_id);

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: /Art2Cart/cart.php');
    exit;
}

$pageTitle = "Checkout - Art2Cart";
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
    </style>
</head>
<body>
    <?php include 'static/templates/header_new.php'; ?>

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
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="postal_code">Postal Code *</label>
                                <input type="text" id="postal_code" name="postal_code" required>
                            </div>
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

                    <!-- Payment Method -->
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
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary-checkout">
                <h3 style="font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 20px; margin-bottom: 24px; color: #000;">Order Summary</h3>
                
                <?php foreach ($cartItems as $item): ?>
                    <div class="summary-item">
                        <div class="summary-item-name">
                            <?php echo htmlspecialchars($item['product_name']); ?> 
                            <span style="color: #6B7280;">(x<?php echo $item['quantity']; ?>)</span>
                        </div>
                        <div class="summary-item-price">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
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
        </div>
    </main>

    <?php include 'static/templates/footer_new.html'; ?>

    <script>
        function selectPayment(method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            document.querySelector(`#${method}`).closest('.payment-method').classList.add('selected');
            
            // Check the radio button
            document.querySelector(`#${method}`).checked = true;
        }

        function placeOrder() {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Disable button
            const btn = document.querySelector('.place-order-btn');
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            // Add action to form data
            formData.append('action', 'place_order');
            
            fetch('/Art2Cart/api/checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order placed successfully!');
                    window.location.href = '/Art2Cart/order-confirmation.php?order_id=' + data.order_id;
                } else {
                    alert(data.message || 'Failed to place order');
                    btn.disabled = false;
                    btn.textContent = 'Place Order';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while placing the order');
                btn.disabled = false;
                btn.textContent = 'Place Order';
            });
        }
    </script>
</body>
</html>
