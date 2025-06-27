<?php
// Include the base href functionality if not already included
if (!function_exists('art2cart_base_url')) {
    require_once __DIR__ . '/../../includes/Art2CartConfig.php';
}

// Get base href for use in links
$footerBaseHref = Art2CartConfig::getBaseUrl();
?>
<footer class="footer">
    <!-- Include CSS files with dynamic base URL -->
    <link rel="stylesheet" href="<?php echo $footerBaseHref; ?>static/css/template/footer.css" />
    <link rel="stylesheet" href="<?php echo $footerBaseHref; ?>static/css/var.css" />
    <link rel="stylesheet" href="<?php echo $footerBaseHref; ?>static/css/fonts.css" />
    
    <div class="frame-52">
        <!-- Company Info -->
        <div class="frame-51">
            <div class="frame-50">
                <a href="<?php echo $footerBaseHref; ?>" class="art-2-cart">
                    <img
                        class="image-1"
                        src="<?php echo $footerBaseHref; ?>static/images/Logo.png"
                        alt="Art2Cart Logo"
                    />
                    <span>
                        <span class="art-2-cart-span">art 2</span>
                        <span class="art-2-cart-span3">cart</span>
                    </span>
                </a>
                <p class="we-have-clothes-that-suits-your-style-and-which-you-re-proud-to-wear-from-women-to-men">
                    We bring digital creations that reflect your style and fuel your projects—from eye-
                    catching vectors to polished design assets you'll be proud to own and use.
                </p>
            </div>
            <div class="social">
                <a href="#" class="_1" aria-label="Twitter">
                    <img class="logo-twitter-2" src="<?php echo $footerBaseHref; ?>static/images/twitter.svg" alt="" />
                </a>
                <a href="#" class="_2" aria-label="Facebook">
                    <img class="logo-fb-simple-2" src="<?php echo $footerBaseHref; ?>static/images/facebook.svg" alt="" />
                </a>
                <a href="#" class="_3" aria-label="Instagram">
                    <img class="logo-instagram-1" src="<?php echo $footerBaseHref; ?>static/images/instagram.svg" alt="" />
                </a>
                <a href="#" class="_4" aria-label="GitHub">
                    <img class="logo-github-1" src="<?php echo $footerBaseHref; ?>static/images/github.svg" alt="" />
                </a>
            </div>
        </div>

        <!-- Company Links -->
        <div class="frame-47">
            <h3 class="help-menu">COMPANY</h3>
            <ul class="about-features-works">
                <li><a href="<?php echo $footerBaseHref; ?>about">About</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>catalogue.php">Products</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>">Home</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>creator">Creator</a></li>
            </ul>
        </div>

        <!-- Help Links -->
        <div class="frame-48">
            <h3 class="help-menu">HELP</h3>
            <ul class="about-features-works2">
                <li><a href="<?php echo $footerBaseHref; ?>support">Customer Support</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>delivery">Delivery Details</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>terms">Terms & Conditions</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>privacy">Privacy Policy</a></li>
            </ul>
        </div>

        <!-- FAQ Links -->
        <div class="frame-522">
            <h3 class="help-menu">FAQ</h3>
            <ul class="about-features-works3">
                <li><a href="<?php echo $footerBaseHref; ?>account">Account</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>deliveries">Manage Deliveries</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>orders">Orders</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>payments">Payments</a></li>
            </ul>
        </div>

        <!-- Resources Links -->
        <div class="frame-49">
            <h3 class="help-menu">RESOURCES</h3>
            <ul class="about-features-works4">
                <li>
                    <a href="https://youtube.com" target="_blank" rel="noopener">Youtube Channel</a>
                </li>
                <li>
                    <a href="https://figma.com" target="_blank" rel="noopener">Figmal</a>
                </li>
                <li><a href="<?php echo $footerBaseHref; ?>how-to-sell">How to Sell</a></li>
                <li><a href="<?php echo $footerBaseHref; ?>guidelines">Guidelines</a></li>
            </ul>
        </div>
    </div>

    <div class="frame-129">
        <p class="_2025-art-2-cart-all-rights-reverved">
            2025 art2cart. All rights reverved
        </p>
    </div>
</footer>
