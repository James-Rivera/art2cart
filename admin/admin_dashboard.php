<?php
require_once '../config/db.php';
require_once '../includes/User.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /Art2Cart/auth/auth.html');
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    header('Location: /Art2Cart/');
    exit;
}

// Get counts for dashboard
$db = Database::getInstance()->getConnection();

// Get pending products count
$stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE review_status = 'pending'");
$pendingProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get approved products count
$stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE review_status = 'approved'");
$approvedProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get pending products for review
$stmt = $db->prepare("    SELECT 
        p.*,
        u.username as seller_name,
        c.name as category_name
    FROM products p
    LEFT JOIN users u ON p.seller_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.review_status = 'pending'
    ORDER BY p.created_at DESC
");
$stmt->execute();
$pendingProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Art2Cart</title>
    <link rel="stylesheet" href="/Art2Cart/static/css/var.css">
    <link rel="stylesheet" href="/Art2Cart/static/css/fonts.css">
    <link rel="stylesheet" href="/Art2Cart/static/css/template/header.css">
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>    <!-- Header/Navigation -->
    <header class="header-bar admin-header">
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

        <!-- Admin Navigation -->
        <nav class="nav admin-nav">
            <ul class="stacked-group admin-nav-list">
                <li><a href="#product-auth" class="nav-link active paintings" data-section="product-auth">PRODUCT REVIEW</a></li>
                <li><a href="#approved-products" class="nav-link sculpture-copy-2" data-section="approved-products">APPROVED PRODUCTS</a></li>
                <li><a href="#seller-applications" class="nav-link artists" data-section="seller-applications">SELLER APPLICATIONS</a></li>
            </ul>
            
            <div class="icons admin-icons">
                <div class="admin-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>ADMIN</span>
                </div>
                <a href="/Art2Cart/auth/logout.php" class="button2 admin-logout">
                    <div class="s-ell-products">LOGOUT</div>
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Dashboard Overview -->
            <section class="dashboard-overview">
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle">Review and manage product submissions</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $pendingProducts; ?></h3>
                            <p>Pending Products</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon approved">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $approvedProducts; ?></h3>
                            <p>Approved Products</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Product Review Section -->
            <section id="product-auth" class="content-section active">
                <div class="section-header">
                    <h2>Product Review</h2>
                    <div class="section-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search products..." id="product-search">
                        </div>                        <select class="filter-select" id="product-filter">
                            <option value="all">All Categories</option>
                            <?php
                            // Get all categories
                            $stmt = $db->query("SELECT * FROM categories ORDER BY name");
                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($categories as $category): 
                                $value = strtolower(str_replace(' ', '-', $category['name']));
                            ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingProductsList as $product): ?>
                                <tr data-product-id="<?php echo $product['id']; ?>">
                                    <td>                                        <div class="product-info">
                                            <?php 
                                            $imagePath = $product['image_path'] ?? '';
                                            // If it's an uploaded file (starts with 'uploads/')
                                            if (strpos($imagePath, 'uploads/') === 0) {
                                                $displayPath = '/Art2Cart/' . $imagePath;
                                            } 
                                            // If it's a static image (starts with 'static/')
                                            else if (strpos($imagePath, 'static/') === 0) {
                                                $displayPath = '/Art2Cart/' . $imagePath;
                                            }
                                            // Default fallback image
                                            else {
                                                $displayPath = '/Art2Cart/static/images/products/sample.jpg';
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($displayPath); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['title'] ?? ''); ?>" 
                                                 class="product-img">
                                            <div>
                                                <div class="product-name"><?php echo htmlspecialchars($product['title'] ?? ''); ?></div>
                                                <div class="product-desc"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)) . '...'; ?></div>
                                            </div>
                                        </div>
                                    </td>                                    <td><?php echo htmlspecialchars($product['seller_name'] ?? 'No Seller'); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>₱<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                    <td><?php echo $product['created_at'] ? date('M j, Y', strtotime($product['created_at'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-approve" onclick="approveProduct(<?php echo $product['id']; ?>)" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-reject" onclick="rejectProduct(<?php echo $product['id']; ?>)" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button class="btn-view" onclick="viewProductDetails(<?php echo $product['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>                </div>
            </section>

            <!-- Approved Products Section -->
            <section id="approved-products" class="content-section">
                <div class="section-header">
                    <h2>Approved Products</h2>
                    <div class="section-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search approved products..." id="approved-product-search">
                        </div>                        <select class="filter-select" id="approved-product-filter">
                            <option value="all">All Categories</option>
                            <?php
                            // Categories already fetched above, reuse them
                            foreach ($categories as $category): 
                                $value = strtolower(str_replace(' ', '-', $category['name']));
                            ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Approved Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Get approved products
                            $stmt = $db->prepare("
                                SELECT 
                                    p.*,
                                    u.username as seller_name,
                                    c.name as category_name
                                FROM products p
                                LEFT JOIN users u ON p.seller_id = u.id
                                LEFT JOIN categories c ON p.category_id = c.id
                                WHERE p.review_status = 'approved'
                                ORDER BY p.review_date DESC
                            ");
                            $stmt->execute();
                            $approvedProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($approvedProductsList as $product): 
                            ?>
                                <tr data-product-id="<?php echo $product['id']; ?>">
                                    <td>
                                        <div class="product-info">
                                            <img src="/Art2Cart/<?php echo htmlspecialchars($product['image_path'] ?? ''); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['title'] ?? ''); ?>" 
                                                 class="product-img">
                                            <div>
                                                <div class="product-name"><?php echo htmlspecialchars($product['title'] ?? ''); ?></div>
                                                <div class="product-desc"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)) . '...'; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['seller_name'] ?? 'No Seller'); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>₱<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                    <td><?php echo $product['review_date'] ? date('M j, Y', strtotime($product['review_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-view" onclick="viewProductDetails(<?php echo $product['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Seller Applications Section -->
            <section id="seller-applications" class="content-section">
                <div class="section-header">
                    <h2>Seller Applications</h2>
                </div>

                <div class="table-container">
                    <table class="data-table">                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Experience</th>
                                <th>Portfolio</th>
                                <th>Government ID</th>
                                <th>Application Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php                            // Get pending seller applications
                            $stmt = $db->prepare("
                                SELECT 
                                    sa.*,
                                    u.email,
                                    sa.government_id_path
                                FROM seller_applications sa
                                JOIN users u ON sa.user_id = u.id
                                WHERE sa.status = 'pending'
                                ORDER BY sa.application_date DESC
                            ");
                            $stmt->execute();
                            $sellerApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($sellerApplications as $application): 
                            ?>
                                <tr data-application-id="<?php echo $application['id']; ?>">
                                    <td><?php echo htmlspecialchars($application['name']); ?></td>
                                    <td><?php echo htmlspecialchars($application['email']); ?></td>
                                    <td><?php echo htmlspecialchars($application['experience_years']); ?> years</td>
                                    <td>                                        <a href="<?php echo htmlspecialchars($application['portfolio_url']); ?>" target="_blank" class="portfolio-link">
                                            View Portfolio <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($application['government_id_path'])): ?>
                                            <a href="/Art2Cart/<?php echo htmlspecialchars($application['government_id_path']); ?>" 
                                               target="_blank" class="document-link" title="View Government ID">
                                                <i class="fas fa-id-card"></i> View ID
                                            </a>
                                        <?php else: ?>
                                            Not provided
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($application['application_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-approve" onclick="approveSellerApplication(<?php echo $application['id']; ?>)" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-reject" onclick="rejectSellerApplication(<?php echo $application['id']; ?>)" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button class="btn-view" onclick="viewSellerDetails(<?php echo $application['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal for detailed view -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="modalBody">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
