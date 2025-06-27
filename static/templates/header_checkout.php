<?php
// Include the base href functionality
require_once __DIR__ . '/../../includes/Art2CartConfig.php';

// Get base href for use in links
$baseHref = Art2CartConfig::getBaseUrl();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart count if user is logged in
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../../config/db.php';
        require_once __DIR__ . '/../../includes/Cart.php';
        $db = Database::getInstance();
        $cart = new Cart($db);
        $cartCount = $cart->getCartCount($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Cart count error in checkout header: " . $e->getMessage());
        $cartCount = 0;
    }
}
?>
<style>
    @import url('<?php echo $baseHref; ?>static/css/var.css');
    @import url('<?php echo $baseHref; ?>static/css/fonts.css');
    
    .checkout-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        padding: 16px 28px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .checkout-header-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .checkout-logo {
        display: flex;
        align-items: center;
        text-decoration: none;
        gap: 12px;
    }
    
    .checkout-logo img {
        width: 44px;
        height: 44px;
    }
    
    .checkout-logo-text {
        font-family: var(--font-karla);
        font-weight: var(--font-weight-medium);
        font-style: var(--font-style-italic, italic);
        font-size: 20px;
        color: #000;
    }
    
    .checkout-logo-text .accent {
        color: #FFD700;
    }
    
    .checkout-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .back-to-cart {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: transparent;
        border: 2px solid #E5E7EB;
        border-radius: 8px;
        text-decoration: none;
        color: #374151;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .back-to-cart:hover {
        border-color: #FFD700;
        background: rgba(255, 215, 0, 0.05);
        color: #000;
    }
    
    .cart-icon-wrapper {
        position: relative;
        padding: 8px;
    }
    
    .cart-icon {
        width: 24px;
        height: 24px;
        opacity: 0.7;
    }
    
    .cart-count-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #FFD700;
        color: #000;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        font-weight: 600;
    }
    
    .secure-checkout {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #059669;
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        font-weight: 500;
    }
      .secure-icon {
        width: 16px;
        height: 16px;
    }
    
    .dark-mode-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: transparent;
        border: 2px solid #E5E7EB;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .dark-mode-toggle:hover {
        border-color: #FFD700;
        background: rgba(255, 215, 0, 0.05);
    }
    
    .dark-mode-toggle svg {
        width: 18px;
        height: 18px;
        position: absolute;
        transition: all 0.3s ease;
    }
    
    .dark-mode-toggle .sun-icon {
        opacity: 1;
        transform: rotate(0deg);
    }
    
    .dark-mode-toggle .moon-icon {
        opacity: 0;
        transform: rotate(90deg);
    }
    
    /* Dark mode styles */
    [data-theme="dark"] .dark-mode-toggle .sun-icon {
        opacity: 0;
        transform: rotate(90deg);
    }
    
    [data-theme="dark"] .dark-mode-toggle .moon-icon {
        opacity: 1;
        transform: rotate(0deg);
    }
    
    [data-theme="dark"] {
        color-scheme: dark;
    }
    
    [data-theme="dark"] .checkout-header {
        background: rgba(17, 24, 39, 0.95);
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }
    
    [data-theme="dark"] .checkout-logo-text {
        color: #F9FAFB;
    }
    
    [data-theme="dark"] .back-to-cart {
        border-color: rgba(255, 255, 255, 0.2);
        color: #D1D5DB;
    }
    
    [data-theme="dark"] .back-to-cart:hover {
        border-color: #FFD700;
        background: rgba(255, 215, 0, 0.1);
        color: #FFD700;
    }
    
    [data-theme="dark"] .dark-mode-toggle {
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    [data-theme="dark"] .dark-mode-toggle:hover {
        border-color: #FFD700;
        background: rgba(255, 215, 0, 0.1);
    }
    
    [data-theme="dark"] .secure-checkout {
        color: #10B981;
    }
    
    @media (max-width: 768px) {
        .checkout-header {
            padding: 12px 20px;
        }
        
        .checkout-logo-text {
            font-size: 20px;
        }
        
        .back-to-cart span {
            display: none;
        }
        
        .secure-checkout {
            display: none;
        }
    }
</style>

<header class="checkout-header">
    <div class="checkout-header-content">
        <!-- Logo -->
        <a href="<?php echo $baseHref; ?>" class="checkout-logo">
            <img src="<?php echo $baseHref; ?>static/images/Logo.png" alt="Art2Cart Logo" />
            <div class="checkout-logo-text">
                art 2<span class="accent"> cart</span>
            </div>
        </a>
        
        <!-- Actions -->
        <div class="checkout-actions">
            <!-- Back to Cart -->
            <a href="<?php echo $baseHref; ?>cart.php" class="back-to-cart">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                <span>Back to Cart</span>
            </a>
            
            <!-- Cart Icon -->
            <div class="cart-icon-wrapper">
                <img class="cart-icon" src="<?php echo $baseHref; ?>static/images/header/cart_checkout.svg" alt="Shopping Cart" />
                <?php if ($cartCount > 0): ?>
                    <span class="cart-count-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </div>            <!-- Dark Mode Toggle -->
            <button class="dark-mode-toggle" onclick="if(typeof window.toggleDarkMode==='function')window.toggleDarkMode();else console.error('toggleDarkMode not available');" aria-label="Toggle dark mode" data-toggle="dark-mode">
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/>
                    <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                </svg>
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>

            <!-- Secure Checkout Indicator -->
            <div class="secure-checkout">
                <svg class="secure-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <circle cx="12" cy="16" r="1"/>
                    <path d="m7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span>Secure Checkout</span>
            </div>        </div>
    </div>
</header>

<script>
// Initialize dark mode immediately to prevent flash
(function() {
    // Check if theme has already been initialized by load.js
    if (document.documentElement.hasAttribute('data-theme-initialized')) {
        console.log('checkout header: Theme already initialized by load.js, skipping');
        return;
    }
    
    console.log('checkout header: Starting theme initialization');
    const savedTheme = localStorage.getItem('art2cart-theme');
    console.log('checkout header: Found saved theme:', savedTheme);
    
    // ALWAYS set theme explicitly - default to light
    const themeToApply = savedTheme || 'light';
    console.log('checkout header: Applying theme:', themeToApply);
    document.documentElement.setAttribute('data-theme', themeToApply);
    document.documentElement.setAttribute('data-theme-initialized', 'true');
    
    // If no theme was saved, save light as default
    if (!savedTheme) {
        localStorage.setItem('art2cart-theme', 'light');
        console.log('checkout header: Saved default light theme to localStorage');
    }
})();

// Global Dark Mode Functionality - Only define if not already defined
if (typeof window.toggleDarkMode !== 'function') {
    function toggleDarkMode() {
        console.log('toggleDarkMode: Function called from checkout header');
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        console.log('toggleDarkMode: Switching from', currentTheme, 'to', newTheme);
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('art2cart-theme', newTheme);
        
        // Dispatch custom event for other components to listen to
        const event = new CustomEvent('themeChanged', { detail: { theme: newTheme } });
        document.dispatchEvent(event);
        
        console.log('toggleDarkMode: Theme changed successfully to', newTheme);
    }
    
    // Make function globally available
    window.toggleDarkMode = toggleDarkMode;
    console.log('checkout header: toggleDarkMode function defined and made globally available');
}
</script>
