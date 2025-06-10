<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Art2Cart - Digital Art Marketplace" />
    <title>Art 2 Cart</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="static/css/index/style.css" />
    <link rel="stylesheet" href="static/css/var.css" />
    <link rel="stylesheet" href="static/css/fonts.css" />
    <link rel="stylesheet" href="static/css/template/header.css" />
    <link rel="stylesheet" href="static/css/index/sample.css" />
    <link rel="stylesheet" href="static/css/index/animation.css"/>
    <link rel="stylesheet" href="static/css/responsive.css" />

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
      <!-- Header Section -->
      <div id="header"></div>

      <!-- Load Scripts -->
      <script>
        window.isIndexPage = true;
      </script>
      <script src="static/js/load.js"></script>
      <script src="static/js/main.js"></script>

      <!-- Hero Section -->
      <section class="hero">
        <div class="text8">
          <div class="text9">
            <p class="earth-without-art-is-just-eh">
              earth without art is just 'eh'
            </p>
            <h1 class="painting">PAINTING</h1>
            <h2 class="never-stop">Never Stop</h2>
            <div class="detail">
              <div class="rectangle-62"></div>
              <p class="premium-digital-products">Premium Digital Products</p>
            </div>
          </div>
          <div class="button">
            <a href="catalogue.php" class="buttons">
              <span class="large">Explore Products</span>
            </a>
            <a href="/sell" class="buttons2">
              <span class="large">Start Selling</span>
            </a>
          </div>
        </div>
      </section>

      <main>
        <!-- Featured Products Section -->
        <section class="featured" id="featured">
          <div class="container2">
            <header class="text10">
              <h2 class="featured-products">Featured Products</h2>
              <p class="treating-all-skin-co2">
                Handpicked premium digital products from our top creators
              </p>
            </header>

            <div class="featured-wrapper">
              <div class="container3">
                <!-- Template for a single featured product card -->
                <!-- This will be dynamically populated from the backend -->
                <article class="product-card">
                  <img
                    class="product-image"
                    src="static/images/rectangle-430.png"
                    alt="Product Image"
                  />
                  <div class="hover-content">
                    <h3 class="product-title">Product Title</h3>
                    <div class="rating">
                      <img
                        src="static/images/star.svg"
                        alt="star"
                        class="star-icon"
                      />
                      <span class="rating-score">0.0</span>
                      <span class="downloads">(0 downloads)</span>
                    </div>
                    <div class="price-row">
                      <span class="price">₱0.00</span>
                      <button class="add-to-cart">Add to Cart</button>
                    </div>
                  </div>
                </article>
              </div>

              <!-- Slider Navigation -->
              <div class="carousel-slider">
                <input
                  type="range"
                  min="0"
                  max="100"
                  value="0"
                  class="slider"
                  id="carouselSlider"
                />
                <div class="slider-progress"></div>
              </div>
            </div>
            <div class="line-4" role="separator"></div>
          </div>
        </section>

        <!-- Artists Section -->
        <section class="artists-section" id="creators">
          <div class="artists-content">
            <!-- Left side text content -->
            <div class="artists-header">
              <h2 class="section-title">Top Creators</h2>
              <p class="section-description">
                Discover the talented artists behind our most popular digital
                artworks. Each creator brings their unique vision and style to
                our platform.
              </p>
              <button class="explore-more-btn">Explore More</button>
            </div>

            <!-- Right side artist cards -->
            <div class="artists-grid">
              <!-- Artist Card 1 -->
              <div class="artist-card active">
                <div class="artist-image">
                  <img
                    src="https://images.unsplash.com/photo-1541961017774-22349e4a1262?w=800&h=600&fit=crop"
                    alt="Digital Art"
                  />
                </div>
                <div class="artist-info">
                  <div class="artist-details">
                    <h3>Digital Artist</h3>
                    <p>Sarah Anderson</p>
                  </div>
                </div>
              </div>


              <!-- Artist Card 2 -->
              <div class="artist-card">
                <div class="artist-image">
                  <img
                    src="https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&h=600&fit=crop"
                    alt="Ocean Art"
                  />
                </div>
                <div class="artist-info">
                  <div class="artist-details">
                    <h3>Digital Artist</h3>
                    <p>Michael Chen</p>
                  </div>
                </div>
              </div>

              <!-- Artist Card 3 -->
              <div class="artist-card">
                <div class="artist-image">
                  <img
                    src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=600&fit=crop"
                    alt="Landscape"
                  />
                </div>
                <div class="artist-info">
                  <div class="artist-details">
                    <h3>Digital Artist</h3>
                    <p>Elena Rodriguez</p>
                  </div>
                </div>
              </div>

              <!-- Navigation dots -->
              <div class="nav-dots">
                <div class="nav-dot active"></div>
                <div class="nav-dot"></div>
                <div class="nav-dot"></div>
              </div>
            </div>
          </div>
        </section>

        <!-- Product Categories Section -->
        <section class="products" id="products">
          <div class="container">
            <header class="text">
              <h2 class="our-digital-products">Our Digital Products</h2>
              <p class="treating-all-skin-co">
                Explore our diverse collection of digital assets
              </p>
            </header>

            <div class="products-container">
              <article class="digital-art">
                <div class="logo">
                  <div class="ellipse-12"></div>
                  <img
                    class="palette-1"
                    src="static/images/icons/palette.png"
                    alt="Digital Art Icon"
                  />
                </div>
                <div class="text2">
                  <h3 class="digital-art2">Digital Art</h3>
                  <p class="_1250-products">1250 Products</p>
                </div>
              </article>

              <article class="photography">
                <div class="logo">
                  <div class="ellipse-122"></div>
                  <img
                    class="palette-1"
                    src="static/images/icons/camera.png"
                    alt="Photography Icon"
                  />
                </div>
                <div class="text3">
                  <h3 class="photography2">Photography</h3>
                  <p class="_458-products">458 Products</p>
                </div>
              </article>

              <article class="illustrations">
                <div class="logo">
                  <div class="ellipse-123"></div>
                  <img
                    class="palette-1"
                    src="static/images/icons/brush.png"
                    alt="Illustrations Icon"
                  />
                </div>
                <div class="text4">
                  <h3 class="illustrations2">Illustrations</h3>
                  <p class="_324-products">324 Products</p>
                </div>
              </article>

              <article class="templates">
                <div class="logo">
                  <div class="ellipse-124"></div>
                  <img
                    class="palette-1"
                    src="static/images/icons/layout-template.png"
                    alt="Templates Icon"
                  />
                </div>
                <div class="text5">
                  <h3 class="templates2">Templates</h3>
                  <p class="_234-products">234 Products</p>
                </div>
              </article>

              <article class="_3-d-models">
                <div class="logo">
                  <div class="ellipse-125"></div>
                  <img
                    class="palette-1"
                    src="static/images/icons/box.png"
                    alt="3D Models Icon"
                  />
                </div>
                <div class="text6">
                  <h3 class="_3-d-models2">3D Models</h3>
                  <p class="_1250-products">1250 Products</p>
                </div>
              </article>

              <article class="assets">
                <div class="logo">
                  <div class="ellipse-126"></div>
                  <img
                    class="palette-1"
                    src="static/images/icons/layers.png"
                    alt="Assets Icon"
                  />
                </div>
                <div class="text7">
                  <h3 class="assets2">Assets</h3>
                  <p class="_89-943-products">89,943 Products</p>
                </div>
              </article>
            </div>
          </div>
        </section>
      </main>

      <!-- Footer -->
      <footer class="footer">
        <div class="frame-52">
          <!-- Company Info -->
          <div class="frame-51">
            <div class="frame-50">
              <a href="/" class="art-2-cart">
                <img
                  class="image-1"
                  src="static/images/logo.png"
                  alt="Art2Cart Logo"
                />
                <span>
                  <span class="art-2-cart-span">art 2</span>
                  <span class="art-2-cart-span3">cart</span>
                </span>
              </a>
              <p
                class="we-have-clothes-that-suits-your-style-and-which-you-re-proud-to-wear-from-women-to-men"
              >
                We bring digital creations that reflect your style and fuel your projects—from eye-
                catching vectors to polished design assets you’ll be proud to own and use.
              </p>
            </div>
            <div class="social">
              <a href="#" class="_1" aria-label="Twitter">
                <img
                  class="logo-twitter-2"
                  src="static/images/twitter.svg"
                  alt=""
                />
              </a>
              <a href="#" class="_2" aria-label="Facebook">
                <img
                  class="logo-fb-simple-2"
                  src="static/images/facebook.svg"
                  alt=""
                />
              </a>
              <a href="#" class="_3" aria-label="Instagram">
                <img
                  class="logo-instagram-1"
                  src="static/images/instagram.svg"
                  alt=""
                />
              </a>
              <a href="#" class="_4" aria-label="GitHub">
                <img
                  class="logo-github-1"
                  src="static/images/github.svg"
                  alt=""
                />
              </a>
            </div>
          </div>

          <!-- Company Links -->
          <div class="frame-47">
            <h3 class="help-menu">COMPANY</h3>
            <ul class="about-features-works">
              <li><a href="/about">About</a></li>
              <li><a href="/products">Products</a></li>
              <li><a href="/">Home</a></li>
              <li><a href="/creator">Creator</a></li>
            </ul>
          </div>

          <!-- Help Links -->
          <div class="frame-48">
            <h3 class="help-menu">HELP</h3>
            <ul class="about-features-works2">
              <li><a href="/support">Customer Support</a></li>
              <li><a href="/delivery">Delivery Details</a></li>
              <li><a href="/terms">Terms & Conditions</a></li>
              <li><a href="/privacy">Privacy Policy</a></li>
            </ul>
          </div>

          <!-- FAQ Links -->
          <div class="frame-522">
            <h3 class="help-menu">FAQ</h3>
            <ul class="about-features-works3">
              <li><a href="/account">Account</a></li>
              <li><a href="/deliveries">Manage Deliveries</a></li>
              <li><a href="/orders">Orders</a></li>
              <li><a href="/payments">Payments</a></li>
            </ul>
          </div>

          <!-- Resources Links -->
          <div class="frame-49">
            <h3 class="help-menu">RESOURCES</h3>
            <ul class="about-features-works4">
              <li>
                <a href="https://youtube.com" target="_blank" rel="noopener"
                  >Youtube Channel</a
                >
              </li>
              <li>
                <a href="https://figma.com" target="_blank" rel="noopener"
                  >Figmal</a
                >
              </li>
              <li><a href="/how-to-sell">How to Sell</a></li>
              <li><a href="/guidelines">Guidelines</a></li>
            </ul>
          </div>
        </div>

        <div class="frame-129">
          <p class="_2025-art-2-cart-all-rights-reverved">
            2025 art2cart. All rights reverved
          </p>
        </div>
      </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="static/js/featured-products.js"></script>
    <script src="static/js/carousel-slider.js"></script>
    <script src="static/js/artists.js"></script>
    <script src="static/js/main.js"></script>
  </body>
</html>
