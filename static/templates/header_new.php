<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/User.php';

// Get user information if logged in
$user = null;
$userInfo = null;
$cartCount = 0;

if (isset($_SESSION['user_id'])) {
    $user = new User($_SESSION['user_id']);
    $userInfo = $user->getProfileInfo();
      // Get cart count if user is logged in
    try {
        require_once __DIR__ . '/../../config/db.php';
        require_once __DIR__ . '/../../includes/Cart.php';
        $db = Database::getInstance();
        $cart = new Cart($db);
        $cartCount = $cart->getCartCount($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Cart count error in header: " . $e->getMessage());
        $cartCount = 0;
    }
}
?>
<style>
    @import url('/Art2Cart/static/css/template/header.css');
    @import url('/Art2Cart/static/css/var.css');
    @import url('/Art2Cart/static/css/fonts.css');
    @import url('/Art2Cart/static/css/index/animation.css');
</style>

<header class="header-bar">
    <!-- Logo -->
    <a href="/Art2Cart/" class="logo2">
        <div class="logo-option-1">
            <img class="web-logo" src="/Art2Cart/static/images/Logo.png" alt="Art2Cart Logo" />
        </div>
        <div class="cultured-kid">
            <span class="cultured-kid-span">art 2</span>
            <span class="cultured-kid-span3">cart</span>
            <span class="cultured-kid-span2">.</span>
        </div>
    </a>

    <!-- Navigation -->
    <nav class="nav">
        <ul class="stacked-group">
            <li><a href="/Art2Cart/catalogue.php" class="paintings">PRODUCTS</a></li>
            <li><a href="/Art2Cart/#creators" class="sculpture-copy-2">CREATORS</a></li>
            <li><a href="/Art2Cart/#about" class="artists">ABOUT</a></li>
        </ul>
        <div class="icons">
            <?php if (isset($_SESSION['user_id']) && $userInfo): ?>
                <div class="account-wrapper" id="accountWrapper" role="button" aria-haspopup="true" aria-expanded="false" tabindex="0">
                    <?php if ($userInfo['profile_image']): ?>
                        <img class="account_icon" src="<?php echo htmlspecialchars($userInfo['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($userInfo['username']); ?>'s profile" />
                    <?php else: ?>
                        <img class="account_icon" src="/Art2Cart/static/images/header/account.svg" alt="Account menu" />
                    <?php endif; ?>
                    
                    <div class="account-dropdown" role="menu" aria-labelledby="accountWrapper">
                        <!-- User Profile Section -->
                        <div class="user-profile">
                            <div class="user-avatar">
                                <?php echo htmlspecialchars($userInfo['avatar_letter']); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($userInfo['username']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($userInfo['email']); ?></div>
                                <div class="user-role">
                                    <?php echo implode(' & ', array_map('ucfirst', $userInfo['roles'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Menu Items -->
                        <a href="/Art2Cart/account.php" class="dropdown-item" role="menuitem">
                            <img src="/Art2Cart/static/images/icons/user.svg" alt="" style="width: 16px; margin-right: 8px;">
                            My Account
                        </a>
                        
                        <?php if (in_array('seller', $userInfo['roles'])): ?>
                            <a href="/Art2Cart/seller/dashboard.php" class="dropdown-item" role="menuitem">
                                <img src="/Art2Cart/static/images/icons/shop.svg" alt="" style="width: 16px; margin-right: 8px;">
                                Seller Dashboard
                            </a>
                        <?php endif; ?>
                        
                        <hr class="dropdown-divider" role="separator">
                        
                        <a href="/Art2Cart/auth/logout.php" class="dropdown-item" role="menuitem" id="logoutButton">
                            <img src="/Art2Cart/static/images/icons/logout.svg" alt="" style="width: 16px; margin-right: 8px;">
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/Art2Cart/auth/auth.html">
                    <img class="account_icon" src="/Art2Cart/static/images/header/account.svg" alt="Account" />
                </a>
            <?php endif; ?>            
            <a href="/Art2Cart/cart.php" class="cart-wrapper">
                <img class="group-124" src="/Art2Cart/static/images/header/cart.svg" alt="Shopping Cart" />
                <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <?php if (isset($_SESSION['user_id']) && $userInfo) {
            if (!in_array('seller', $userInfo['roles'])) {
                echo '<a href="/Art2Cart/auth/become_seller.html" class="button2">
                        <div class="s-ell-products">BECOME A SELLER</div>
                      </a>';
            }
        } else {
            echo '<a href="/Art2Cart/auth/auth.html" class="button2">
                    <div class="s-ell-products">LOGIN</div>
                  </a>';
        }
        ?>
    </nav>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const accountWrapper = document.getElementById('accountWrapper');
    const dropdown = accountWrapper?.querySelector('.account-dropdown');
    const logoutButton = document.getElementById('logoutButton');
    const menuItems = dropdown?.querySelectorAll('.dropdown-item');

    // Toggle dropdown on click
    accountWrapper?.addEventListener('click', function(e) {
        const isMenuItem = e.target.closest('.dropdown-item');
        if (!isMenuItem) {
            e.stopPropagation();
            e.preventDefault();
            dropdown?.classList.toggle('show');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!accountWrapper?.contains(e.target)) {
            dropdown?.classList.remove('show');
        }
    });

    // Handle keyboard navigation
    accountWrapper?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            dropdown?.classList.toggle('show');
        } else if (e.key === 'Escape') {
            dropdown?.classList.remove('show');
        }
    });

    // Optional: confirm logout
    logoutButton?.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to log out?')) {
            e.preventDefault();
        }
    });
});
</script>