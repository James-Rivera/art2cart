<?php
// Include the base href functionality
require_once __DIR__ . '/../../includes/Art2CartConfig.php';

// Get base href for use in links
$baseHref = Art2CartConfig::getBaseUrl();

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
    $userInfo = $user->getProfileInfo();    // Get cart count if user is logged in
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
<!-- Header CSS is loaded by the parent page to ensure proper styling -->

<header class="header-bar"><!-- Logo -->
    <a href="<?php echo $baseHref; ?>" class="logo2">
        <div class="logo-option-1">
            <img class="web-logo" src="<?php echo $baseHref; ?>static/images/Logo.png" alt="Art2Cart Logo" />
        </div>
        <div class="cultured-kid">
            <span class="cultured-kid-span">art 2</span>
            <span class="cultured-kid-span3">cart</span>
        </div>
    </a>

    <!-- Navigation -->
    <nav class="nav">        <ul class="stacked-group">
            <li><a href="<?php echo $baseHref; ?>catalogue.php" class="paintings">PRODUCTS</a></li>
            <li><a href="<?php echo $baseHref; ?>#creators" class="sculpture-copy-2">CREATORS</a></li>
            <li><a href="<?php echo $baseHref; ?>#about" class="artists">ABOUT</a></li>
        </ul>
        <div class="icons">
            <?php if (isset($_SESSION['user_id']) && $userInfo): ?>
                <div class="account-wrapper" id="accountWrapper" role="button" aria-haspopup="true" aria-expanded="false" tabindex="0">
                    <?php if ($userInfo['profile_image']): ?>
                        <img class="account_icon" src="<?php echo htmlspecialchars($userInfo['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($userInfo['username']); ?>'s profile" />                    <?php else: ?>
                        <img class="account_icon" src="<?php echo $baseHref; ?>static/images/header/account.svg" alt="Account menu" />
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
                        <a href="<?php echo $baseHref; ?>account.php" class="dropdown-item" role="menuitem">
                            <img src="<?php echo $baseHref; ?>static/images/icons/user.svg" alt="" style="width: 16px; margin-right: 8px;">
                            My Account
                        </a>
                          <?php if (in_array('seller', $userInfo['roles'])): ?>
                            <a href="<?php echo $baseHref; ?>seller/dashboard.php" class="dropdown-item" role="menuitem">
                                <img src="<?php echo $baseHref; ?>static/images/icons/shop.svg" alt="" style="width: 16px; margin-right: 8px;">
                                Seller Dashboard
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array('admin', $userInfo['roles'])): ?>
                            <a href="<?php echo $baseHref; ?>admin/admin_dashboard.php" class="dropdown-item" role="menuitem">
                                <img src="<?php echo $baseHref; ?>static/images/icons/settings.svg" alt="" style="width: 16px; margin-right: 8px;">
                                Admin Dashboard
                            </a>
                        <?php endif; ?>
                        
                        <hr class="dropdown-divider" role="separator">
                        
                        <a href="<?php echo $baseHref; ?>auth/logout.php" class="dropdown-item" role="menuitem" id="logoutButton">
                            <img src="<?php echo $baseHref; ?>static/images/icons/logout.svg" alt="" style="width: 16px; margin-right: 8px;">
                            Logout
                        </a>
                    </div>
                </div>            <?php else: ?>
                <a href="<?php echo $baseHref; ?>auth/auth.html">
                    <img class="account_icon" src="<?php echo $baseHref; ?>static/images/header/account.svg" alt="Account" />
                </a>
            <?php endif; ?>            
            <a href="<?php echo $baseHref; ?>cart.php" class="cart-wrapper">
                <img class="group-124" src="<?php echo $baseHref; ?>static/images/header/cart.svg" alt="Shopping Cart" />
                <?php if ($cartCount > 0): ?>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                <?php endif; ?>            </a>            <!-- Dark Mode Toggle -->
            <button class="dark-mode-toggle" onclick="if(typeof window.toggleDarkMode==='function')window.toggleDarkMode();else console.error('toggleDarkMode not available');" aria-label="Toggle dark mode" data-toggle="dark-mode">
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/>
                    <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                </svg>
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
        </div>        <?php if (isset($_SESSION['user_id']) && $userInfo) {
            if (!in_array('seller', $userInfo['roles'])) {
                echo '<a href="' . $baseHref . 'auth/become_seller.php" class="button2">
                        <div class="s-ell-products">BECOME A SELLER</div>
                      </a>';
            }
        } else {
            echo '<a href="' . $baseHref . 'auth/auth.html" class="button2">
                    <div class="s-ell-products">LOGIN</div>
                  </a>';
        }
        ?>    </nav>
</header>

<?php
// Always load pending ratings JavaScript functions to prevent "function not defined" errors
require_once __DIR__ . '/../../includes/pending_ratings.php';
addPendingRatingsGlobalScript($baseHref);

// Show pending ratings notification if user is logged in and not on specific pages
if (isset($_SESSION['user_id'])) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $excludePages = ['rating_system_test.html', 'order-confirmation.php', 'logout.php'];
    
    if (!in_array($currentPage, $excludePages)) {
        $pendingRatings = checkPendingRatings($_SESSION['user_id']);
        showPendingRatingsNotification($pendingRatings, $baseHref);
    }
}
?>

<script>
// Initialize dark mode immediately (before DOMContentLoaded to prevent flash)
(function() {
    console.log('header_new.php: Starting theme initialization');
    const savedTheme = localStorage.getItem('art2cart-theme');
    console.log('header_new.php: Found saved theme:', savedTheme);
    
    // ALWAYS set theme explicitly - default to light
    const themeToApply = savedTheme || 'light';
    console.log('header_new.php: Applying theme:', themeToApply);
    document.documentElement.setAttribute('data-theme', themeToApply);
    document.documentElement.setAttribute('data-theme-initialized', 'true');
    
    // If no theme was saved, save light as default
    if (!savedTheme) {
        localStorage.setItem('art2cart-theme', 'light');
        console.log('header_new.php: Saved default light theme to localStorage');
    }
})();

// Global Dark Mode Functionality - Only define if not already defined
if (typeof window.toggleDarkMode !== 'function') {
    function toggleDarkMode() {
        console.log('toggleDarkMode: Function called from header_new.php');
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
}

document.addEventListener('DOMContentLoaded', function() {
    const accountWrapper = document.getElementById('accountWrapper');
    const dropdown = accountWrapper?.querySelector('.account-dropdown');
    const logoutButton = document.getElementById('logoutButton');

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