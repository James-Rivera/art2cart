<?php
require_once '../config/db.php';
require_once '../includes/User.php';
require_once '../includes/Art2CartConfig.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get base URL
$baseHref = Art2CartConfig::getBaseUrl();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $baseHref . 'auth/auth.html');
    exit;
}

$user = new User($_SESSION['user_id']);
if (!$user->hasRole('admin')) {
    header('Location: ' . $baseHref);
    exit;
}

// Get database connection
$db = Database::getInstance()->getConnection();

// Initialize variables with default values
$pendingProducts = 0;
$approvedProducts = 0;
$totalUsers = 0;
$totalOrders = 0;
$pendingProductsList = [];
$recentOrders = [];
$sellerApplications = [];
$activeSellers = 0;
$recentActivity = [];
$stats = [];
$billingAnalytics = [];
$healthScore = 0;

// Get dashboard data
try {
    // Overview stats - with error handling for missing columns
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE review_status = 'pending'");
        $pendingProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        // Fallback if review_status column doesn't exist
        $stmt = $db->query("SELECT COUNT(*) as count FROM products");
        $pendingProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE review_status = 'approved'");
        $approvedProducts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $approvedProducts = 0; // Default if column doesn't exist
    }
      $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];    // Pending products for review - with fallback
    try {
        // First, let's see what statuses exist
        $stmt = $db->prepare("SELECT DISTINCT review_status FROM products");
        $stmt->execute();        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Available review statuses: " . implode(', ', $statuses));
          // Get pagination parameters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $itemsPerPage = (int)($_GET['per_page'] ?? 20); // Default 20 items per page
        
        // Get total count for pagination
        $countStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE review_status = 'pending'");
        $countStmt->execute();
        $totalPendingProducts = $countStmt->fetchColumn();
        
        // Handle "show all" option
        if ($itemsPerPage == 0) {
            $itemsPerPage = $totalPendingProducts;
            $page = 1;
        }
        
        $offset = ($page - 1) * $itemsPerPage;
        $totalPages = $itemsPerPage > 0 ? ceil($totalPendingProducts / $itemsPerPage) : 1;
        
        $stmt = $db->prepare("SELECT id, title, price, created_at, image_path, description FROM products WHERE review_status = 'pending' ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$itemsPerPage, $offset]);$pendingProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (Exception $e) {
        error_log("Error in pending products query: " . $e->getMessage());
        // Fallback to basic query if extended fields don't exist
        try {
            $stmt = $db->prepare("SELECT id, title, price, created_at FROM products WHERE review_status = 'pending' ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$itemsPerPage, $offset]);
            $pendingProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            $stmt = $db->prepare("SELECT id, title, price, created_at FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$itemsPerPage, $offset]);
            $pendingProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);        }
    }

    // Get approved products for re-review functionality
    $approvedProductsList = [];
    try {
        $stmt = $db->prepare("SELECT id, title, price, created_at, image_path, description FROM products WHERE review_status = 'approved' ORDER BY created_at DESC LIMIT 50");
        $stmt->execute();
        $approvedProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in approved products query: " . $e->getMessage());
        $approvedProductsList = [];
    }

    // Recent orders
    $stmt = $db->prepare("SELECT id, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
      // Enhanced recent orders with billing info (direct JOIN query - no view dependency)
    try {
        $stmt = $db->prepare("
            SELECT 
                o.id as order_id,
                CONCAT(ba.first_name, ' ', ba.last_name) as customer_name,
                o.total_amount,
                ba.payment_method,
                ba.country,
                o.status,
                DATE_FORMAT(o.created_at, '%M %d, %Y %H:%i') as formatted_date,
                o.created_at
            FROM orders o
            LEFT JOIN billing_addresses ba ON o.id = ba.order_id
            ORDER BY o.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $recentOrdersEnhanced = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we got enhanced data, use it; otherwise stick with simple orders
        if (!empty($recentOrdersEnhanced)) {
            $recentOrders = $recentOrdersEnhanced;
        }
    } catch (Exception $e) {
        // Fallback to simple orders query if billing_addresses table issues
        error_log("Enhanced orders query failed, using fallback: " . $e->getMessage());
    }// Seller applications
    try {
        // Use the correct column names from the actual database schema
        $stmt = $db->prepare("
            SELECT sa.id, sa.user_id, sa.portfolio_url, sa.government_id_path, 
                   sa.application_date as created_at, sa.status, u.username, u.email 
            FROM seller_applications sa 
            JOIN users u ON sa.user_id = u.id 
            WHERE sa.status = 'pending' 
            ORDER BY sa.application_date DESC 
            LIMIT 20
        ");
        $stmt->execute();
        $sellerApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        // Alternative query if JOIN fails
        try {
            $stmt = $db->prepare("
                SELECT id, user_id, portfolio_url, government_id_path, 
                       application_date as created_at, status 
                FROM seller_applications 
                WHERE status = 'pending' 
                ORDER BY application_date DESC 
                LIMIT 20
            ");
            $stmt->execute();
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get user details for each application
            foreach ($applications as &$app) {
                $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
                $stmt->execute([$app['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $app['username'] = $user['username'];
                    $app['email'] = $user['email'];
                } else {
                    $app['username'] = 'Unknown';
                    $app['email'] = 'No email';
                }
            }
            $sellerApplications = $applications;
            
        } catch (Exception $e2) {
            $sellerApplications = [];
        }
    }
      // Count active sellers
    try {
        // Try to count users with seller role using user_roles table
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT ur.user_id) as count 
            FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE r.name = 'seller'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $activeSellers = $result['count'];
    } catch (Exception $e) {
        // Alternative: count approved seller applications
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM seller_applications WHERE status = 'approved'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $activeSellers = $result['count'];
        } catch (Exception $e2) {
            // Fallback: check if users table has a seller field
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'seller' OR role = 'seller'");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $activeSellers = $result['count'];
            } catch (Exception $e3) {
                $activeSellers = 0;
                error_log("Could not determine active sellers count: " . $e3->getMessage());
            }
        }
    }
    
    // Get comprehensive analytics data
    try {
        $stmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM users WHERE id IN (SELECT user_id FROM seller_applications WHERE status = 'approved')) as active_sellers,
                (SELECT COUNT(*) FROM products WHERE status = 'active') as active_products,
                (SELECT COUNT(*) FROM products WHERE status = 'pending') as pending_products_alt,
                (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
                (SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing')) as pending_orders,
                (SELECT COUNT(*) FROM seller_applications WHERE status = 'pending') as pending_applications
        ");
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add fallback data and additional metrics
        if (!$stats) $stats = [];        $stats = array_merge([
            'total_users' => $totalUsers,
            'active_sellers' => 0,
            'active_products' => $approvedProducts,
            'pending_products_alt' => $pendingProducts,
            'completed_orders' => $totalOrders,
            'pending_orders' => 0,
            'pending_applications' => count($sellerApplications),
            'total_downloads' => 0,
            'total_ratings' => 0,
            'cart_items' => 0
        ], $stats ?: []);
        
        // Try to get additional metrics
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM download_logs");
            $stats['total_downloads'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {}
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM ratings");
            $stats['total_ratings'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {}
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM cart");
            $stats['cart_items'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {}
        
    } catch (Exception $e) {        $stats = [
            'total_users' => $totalUsers,
            'active_sellers' => 0,
            'active_products' => $approvedProducts,
            'pending_products_alt' => $pendingProducts,
            'completed_orders' => $totalOrders,
            'pending_orders' => 0,
            'pending_applications' => count($sellerApplications),
            'total_downloads' => 0,
            'total_ratings' => 0,
            'cart_items' => 0
        ];
    }
      // Calculate health score
    $sellerRate = $stats['total_users'] > 0 ? round(($stats['active_sellers'] / $stats['total_users']) * 100, 1) : 0;
    $productRate = ($stats['active_products'] + $stats['pending_products_alt']) > 0 ? 
                   round(($stats['active_products'] / ($stats['active_products'] + $stats['pending_products_alt'])) * 100, 1) : 0;
    $orderRate = ($stats['completed_orders'] + $stats['pending_orders']) > 0 ? 
                 round(($stats['completed_orders'] / ($stats['completed_orders'] + $stats['pending_orders'])) * 100, 1) : 0;
    
    $healthScore = round(($sellerRate * 0.3 + $productRate * 0.4 + $orderRate * 0.3), 1);
    if ($healthScore < 20 && $stats['total_users'] > 0) {
        $healthScore = max(20, $healthScore);
    } elseif ($stats['total_users'] == 0) {
        $healthScore = 85; // Default for new installations
    }
    
    // Get recent activity
    try {
        $stmt = $db->prepare("
            (SELECT 'user' as type, CONCAT(username, ' registered') as activity, created_at FROM users ORDER BY created_at DESC LIMIT 5)
            UNION ALL
            (SELECT 'product' as type, CONCAT('\"', title, '\" uploaded') as activity, created_at FROM products ORDER BY created_at DESC LIMIT 5)
            UNION ALL
            (SELECT 'order' as type, CONCAT('Order #', id, ' placed') as activity, created_at FROM orders ORDER BY created_at DESC LIMIT 5)
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $recentActivity = [];
    }
      // Get billing analytics (direct aggregation query - no view dependency)
    try {
        $stmt = $db->prepare("
            SELECT 
                ba.country,
                ba.payment_method,
                COUNT(*) as total_orders,
                SUM(o.total_amount) as revenue,
                AVG(o.total_amount) as avg_order_value,
                MIN(ba.created_at) as first_order,
                MAX(ba.created_at) as last_order
            FROM billing_addresses ba
            JOIN orders o ON ba.order_id = o.id
            GROUP BY ba.country, ba.payment_method
            ORDER BY revenue DESC
            LIMIT 10
        ");
        $stmt->execute();
        $billingAnalytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $billingAnalytics = [];
        error_log("Billing analytics query failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Dashboard Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?php echo $baseHref; ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Art2Cart</title>
    
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


    <!-- CSS -->    
    <link rel="stylesheet" href="static/css/var.css">
    <link rel="stylesheet" href="static/css/fonts.css">
    <link rel="stylesheet" href="static/css/template/header.css">
    <link rel="stylesheet" href="admin/css/admin_dashboard.css">
    <link rel="stylesheet" href="admin/css/approved_products.css">
    <link rel="stylesheet" href="admin/css/database_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Theme initialization -->
    <script>
        // Set theme immediately to prevent flash
        const savedTheme = localStorage.getItem('art2cart-theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Dark mode toggle function
        function toggleDarkMode() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('art2cart-theme', newTheme);
        }
        
        window.toggleDarkMode = toggleDarkMode;
    </script>
</head>
<body>    <!-- Header -->
    <?php include '../static/templates/header_new.php'; ?>    <!-- Error Display -->
    <?php if (isset($error)): ?>
        <div style="background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 20px; color: #c62828; border-radius: 4px;">
            <strong>⚠️ Database Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Dashboard Layout -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
            </div>            <ul class="sidebar-menu">
                <li>
                    <button class="nav-btn active" data-tab="overview">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Overview</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn" data-tab="products">
                        <i class="fas fa-box"></i>
                        <span>Product Review</span>
                    </button>
                </li>                <li>
                    <button class="nav-btn" data-tab="user-management">
                        <i class="fas fa-users-cog"></i>
                        <span>User Management</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn" data-tab="analytics">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </button>
                </li>                <li>
                    <button class="nav-btn" data-tab="orders">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Orders</span>
                    </button>
                </li>
                <li>
                    <button class="nav-btn" data-tab="database">
                        <i class="fas fa-database"></i>
                        <span>Database</span>
                    </button>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Overview Tab -->
            <div id="overview" class="tab-panel active">
                <div class="page-header">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, Admin! Here's what's happening with your platform.</p>
                </div>
                  <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $pendingProducts; ?></h3>
                            <p>Pending Products</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $approvedProducts; ?></h3>
                            <p>Approved Products</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $totalUsers; ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $totalOrders; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $healthScore; ?>%</h3>
                            <p>System Health</p>
                            <small><?php
                                if ($healthScore >= 90) echo 'Excellent';
                                elseif ($healthScore >= 75) echo 'Good';
                                elseif ($healthScore >= 60) echo 'Fair';
                                else echo 'Needs Attention';
                            ?></small>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="content-section">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">                        <button class="btn btn-primary" onclick="switchTabAndScroll('products', 'pending-reviews')">
                            <i class="fas fa-box"></i> Review Products
                        </button><button class="btn btn-secondary" onclick="switchTab('user-management')">
                            <i class="fas fa-users-cog"></i> Manage Users
                        </button>
                        <button class="btn btn-info" onclick="switchTab('analytics')">
                            <i class="fas fa-chart-line"></i> View Analytics
                        </button>
                    </div>
                </div>
                
                <div class="content-grid">
                    <div class="content-card">
                        <h3>Recent Pending Products</h3>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pendingProductsList)): ?>
                                        <tr><td colspan="4">No pending products</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pendingProductsList as $product): ?>                                            <tr>
                                                <td><?php echo htmlspecialchars($product['title']); ?></td>
                                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn-sm btn-primary" onclick="reviewProductFromOverview(<?php echo $product['id']; ?>)">Review</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="content-card">
                        <h3>Recent Orders</h3>
                        <div class="table-container">                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <?php if (isset($recentOrders[0]['customer_name'])): ?>
                                            <th>Customer</th>
                                            <th>Payment</th>
                                            <th>Country</th>
                                        <?php endif; ?>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>                                    <?php if (empty($recentOrders)): ?>
                                        <tr><td colspan="<?php echo isset($recentOrders[0]['customer_name']) ? '7' : '4'; ?>">No orders yet</td></tr>
                                    <?php else: ?>                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id'] ?? $order['id']; ?></td>
                                                <?php if (isset($order['customer_name'])): ?>
                                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($order['country'] ?? 'N/A'); ?></td>
                                                <?php endif; ?>
                                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                                <td><?php echo isset($order['formatted_date']) ? $order['formatted_date'] : date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="content-card">
                        <h3>Recent Activity</h3>
                        <div class="activity-feed">
                            <?php if (!empty($recentActivity)): ?>
                                <?php 
                                function timeAgo($datetime) {
                                    $time = time() - strtotime($datetime);
                                    if ($time < 60) return 'just now';
                                    if ($time < 3600) return floor($time/60) . ' minutes ago';
                                    if ($time < 86400) return floor($time/3600) . ' hours ago';
                                    if ($time < 2592000) return floor($time/86400) . ' days ago';
                                    return date('M j, Y', strtotime($datetime));
                                }
                                ?>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php if ($activity['type'] === 'user'): ?>
                                                <i class="fas fa-user-plus"></i>
                                            <?php elseif ($activity['type'] === 'product'): ?>
                                                <i class="fas fa-upload"></i>
                                            <?php else: ?>
                                                <i class="fas fa-shopping-bag"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content">
                                            <p><?php echo htmlspecialchars($activity['activity']); ?></p>
                                            <span class="activity-time"><?php echo timeAgo($activity['created_at']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-activity">
                                    <i class="fas fa-history"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
              <!-- Products Tab -->
            <div id="products" class="tab-panel">
                <div class="page-header">
                    <h1>Product Review</h1>
                    <p>Review and manage product submissions.</p>
                </div>

                <!-- Product Review Navigation -->
                <div class="product-review-nav">
                    <div class="nav-buttons">
                        <button class="nav-btn-section active" data-section="pending-reviews" onclick="scrollToSection('pending-reviews')">
                            <i class="fas fa-clock"></i>
                            <span>Pending Product Reviews</span>
                            <span class="count-badge"><?php echo $pendingProducts; ?></span>
                        </button>
                        <button class="nav-btn-section" data-section="approved-management" onclick="scrollToSection('approved-management')">
                            <i class="fas fa-check-circle"></i>
                            <span>Approved Products Management</span>
                            <span class="count-badge"><?php echo $approvedProducts; ?></span>
                        </button>
                    </div>
                </div>

                <div class="content-card" id="pending-reviews">
                    <h3>Pending Product Reviews</h3>
                    <?php if (!empty($pendingProductsList)): ?>
                        <div class="product-review-controls-container">
                            <div class="results-info">
                                Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $itemsPerPage, $totalPendingProducts); ?> 
                                of <?php echo $totalPendingProducts; ?> pending products
                            </div>
                            <div class="product-review-controls">
                                <label for="itemsPerPage">Items per page:</label>
                                <select id="itemsPerPage" onchange="changeItemsPerPage(this.value)">
                                    <option value="10" <?php echo $itemsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo $itemsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $itemsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="0" <?php echo $itemsPerPage == 0 ? 'selected' : ''; ?>>Show all</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?><?php if (empty($pendingProductsList)): ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No pending products to review</p>
                        </div>
                    <?php else: ?>
                        <div class="products-review-grid">
                            <?php foreach ($pendingProductsList as $product): ?>
                                <div class="product-review-card" data-id="<?php echo $product['id']; ?>">
                                    <div class="product-image-container">                                        <?php if (!empty($product['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-badge">Pending Review</div>
                                    </div>
                                    
                                    <div class="product-details">
                                        <h4 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h4>
                                        
                                        <div class="product-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <span>Submitted <?php echo date('M j, Y', strtotime($product['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="product-price">
                                            ₱<?php echo number_format($product['price'], 2); ?>
                                        </div>
                                        
                                        <?php if (!empty($product['description'])): ?>
                                            <div class="product-description">
                                                <?php echo htmlspecialchars(substr($product['description'], 0, 150)) . (strlen($product['description']) > 150 ? '...' : ''); ?>
                                            </div>
                                        <?php endif; ?>                                        <div class="review-actions">
                                            <button class="btn btn-info btn-sm" 
                                                    onclick="viewProductDetails(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-success btn-sm" 
                                                    onclick="reviewProduct(<?php echo $product['id']; ?>, 'approve')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-danger btn-sm" 
                                                    onclick="showRejectForm(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                        
                                        <div class="reject-form" id="reject-form-<?php echo $product['id']; ?>" style="display: none;">
                                            <h5>Rejection Reason</h5>
                                            <textarea id="reject-notes-<?php echo $product['id']; ?>" 
                                                      placeholder="Please provide a reason for rejection..." rows="3"></textarea>
                                            <div class="form-actions">
                                                <button class="btn btn-danger btn-sm" 
                                                        onclick="reviewProduct(<?php echo $product['id']; ?>, 'reject')">
                                                    <i class="fas fa-times"></i> Confirm Rejection
                                                </button>
                                                <button class="btn btn-secondary btn-sm" 
                                                        onclick="hideRejectForm(<?php echo $product['id']; ?>)">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>                            <?php endforeach; ?>
                        </div>
                          <!-- Pagination Controls -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-container">
                                <div class="pagination">                                    <?php if ($page > 1): ?>
                                        <a href="admin/admin_dashboard.php?page=<?php echo $page - 1; ?>&per_page=<?php echo $itemsPerPage; ?>#products" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                      if ($startPage > 1): ?>
                                        <a href="admin/admin_dashboard.php?page=1&per_page=<?php echo $itemsPerPage; ?>#products" class="btn btn-sm btn-secondary">1</a>
                                        <?php if ($startPage > 2): ?>
                                            <span class="pagination-dots">...</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                      <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <a href="admin/admin_dashboard.php?page=<?php echo $i; ?>&per_page=<?php echo $itemsPerPage; ?>#products" 
                                           class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <span class="pagination-dots">...</span>
                                        <?php endif; ?>
                                        <a href="admin/admin_dashboard.php?page=<?php echo $totalPages; ?>&per_page=<?php echo $itemsPerPage; ?>#products" class="btn btn-sm btn-secondary"><?php echo $totalPages; ?></a>
                                    <?php endif; ?>
                                      <?php if ($page < $totalPages): ?>
                                        <a href="admin/admin_dashboard.php?page=<?php echo $page + 1; ?>&per_page=<?php echo $itemsPerPage; ?>#products" class="btn btn-sm btn-secondary">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                  <!-- Approved Products Management Section -->
                <div class="content-card" id="approved-management">
                    <h3>Approved Products Management</h3>
                    
                    <?php if (empty($approvedProductsList)): ?>
                        <div class="no-data">
                            <i class="fas fa-check-circle"></i>
                            <p>No approved products found</p>
                        </div>
                    <?php else: ?>
                        <div class="products-review-grid">
                            <?php foreach ($approvedProductsList as $product): ?>
                                <div class="product-review-card approved-product" data-id="<?php echo $product['id']; ?>">
                                    <div class="product-image-container">
                                        <?php if (!empty($product['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-badge approved">Approved</div>
                                    </div>
                                    
                                    <div class="product-details">
                                        <h4 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h4>
                                        
                                        <div class="product-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <span>Approved <?php echo date('M j, Y', strtotime($product['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="product-price">
                                            ₱<?php echo number_format($product['price'], 2); ?>
                                        </div>
                                        
                                        <?php if (!empty($product['description'])): ?>
                                            <div class="product-description">
                                                <?php echo htmlspecialchars(substr($product['description'], 0, 150)) . (strlen($product['description']) > 150 ? '...' : ''); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="review-actions">
                                            <button class="btn btn-info btn-sm" 
                                                    onclick="viewProductDetails(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-warning btn-sm" 
                                                    onclick="showRemoveForm(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-ban"></i> Remove Product
                                            </button>
                                        </div>
                                        
                                        <div class="reject-form" id="remove-form-<?php echo $product['id']; ?>" style="display: none;">
                                            <h5>Removal Reason</h5>
                                            <textarea id="remove-notes-<?php echo $product['id']; ?>" 
                                                      placeholder="Please provide a reason for removing this approved product..." rows="3"></textarea>
                                            <div class="form-actions">
                                                <button class="btn btn-danger btn-sm" 
                                                        onclick="removeApprovedProduct(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-ban"></i> Confirm Removal
                                                </button>
                                                <button class="btn btn-secondary btn-sm" 
                                                        onclick="hideRemoveForm(<?php echo $product['id']; ?>)">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
              <!-- User Management Tab -->
            <div id="user-management" class="tab-panel">
                <div class="page-header">
                    <h1>User Management</h1>
                    <p>Manage platform users and seller applications.</p>
                </div>
                
                <!-- Platform Statistics (moved from Users tab) -->
                <div class="content-card">
                    <h3>Platform Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h2><?php echo $totalUsers; ?></h2>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="stat-content">
                                <h2><?php echo $activeSellers; ?></h2>
                                <p>Active Sellers</p>
                            </div>
                        </div>
                    </div>                </div>
                
                <!-- Sub-navigation Buttons -->
                <div class="sub-nav-container">
                    <div class="sub-nav-buttons">
                        <button class="sub-tab-btn active" data-sub-tab="pending-applications">
                            <i class="fas fa-user-clock"></i>
                            Pending Applications
                        </button>
                        <button class="sub-tab-btn" data-sub-tab="manage-users">
                            <i class="fas fa-users-cog"></i>
                            Manage Users
                        </button>
                    </div>
                </div>
                
                <!-- Sub-navigation Content -->
                <div class="content-card">
                    <!-- Pending Applications Content -->
                    <div id="pending-applications" class="sub-tab-content active">
                        <h3>Pending Seller Applications</h3>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Portfolio</th>
                                        <th>Applied</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sellerApplications)): ?>
                                        <tr><td colspan="5">
                                            <div style="text-align: center; padding: 20px; color: #666;">
                                                <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                                                <strong>No pending seller applications</strong><br>
                                                <small>New seller applications will appear here for review</small>
                                            </div>
                                        </td></tr>
                                    <?php else: ?>
                                        <?php foreach ($sellerApplications as $application): ?>
                                            <tr data-application-id="<?php echo $application['id']; ?>">
                                                <td><?php echo htmlspecialchars($application['username'] ?? 'Unknown'); ?></td>
                                                <td><?php echo htmlspecialchars($application['email'] ?? 'No email'); ?></td>
                                                <td>
                                                    <?php if (!empty($application['portfolio_url'])): ?>
                                                        <a href="<?php echo htmlspecialchars($application['portfolio_url']); ?>" target="_blank" class="btn-sm btn-info">
                                                            <i class="fas fa-external-link-alt"></i> View Portfolio
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: #999;">No portfolio provided</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y H:i', strtotime($application['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn-sm btn-success" onclick="reviewSellerApplication(<?php echo $application['id']; ?>, 'approve')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn-sm btn-danger" onclick="reviewSellerApplication(<?php echo $application['id']; ?>, 'reject')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                    <button class="btn-sm btn-info" onclick="viewSellerDetails(<?php echo $application['id']; ?>)">
                                                        <i class="fas fa-eye"></i> Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Manage Users Content -->
                    <div id="manage-users" class="sub-tab-content">
                        <h3>All Users</h3>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr><td colspan="6">
                                        <div class="loading">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            <p>Loading users...</p>
                                        </div>
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Orders Tab -->
            <div id="orders" class="tab-panel">
                <div class="page-header">
                    <h1>Order Management</h1>
                    <p>View and manage customer orders.</p>
                </div>
                
                <div class="content-card">
                    <h3>All Orders</h3>
                    <div class="table-container">                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <?php if (isset($recentOrders[0]['customer_name'])): ?>
                                        <th>Customer</th>
                                        <th>Payment</th>
                                        <th>Country</th>
                                    <?php endif; ?>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr><td colspan="<?php echo isset($recentOrders[0]['customer_name']) ? '8' : '5'; ?>">No orders found</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>                                        <tr>
                                            <td>#<?php echo $order['order_id'] ?? $order['id']; ?></td>
                                            <?php if (isset($order['customer_name'])): ?>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($order['country'] ?? 'N/A'); ?></td>
                                            <?php endif; ?>
                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                            <td><?php echo isset($order['formatted_date']) ? $order['formatted_date'] : date('M j, Y H:i', strtotime($order['created_at'])); ?></td>                                            <td>
                                                <button class="btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['order_id'] ?? $order['id']; ?>)">View</button>
                                                <button class="btn-sm btn-primary" onclick="updateOrderStatus(<?php echo $order['order_id'] ?? $order['id']; ?>)">Update</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>            </div>
            
            <!-- Analytics Tab -->
            <div id="analytics" class="tab-panel">
                <div class="page-header">
                    <h1>Analytics Dashboard</h1>
                    <p>Business insights and system health metrics.</p>
                </div>
                
                <!-- Health Score Section -->
                <div class="content-card">
                    <h3>System Health Score</h3>
                    <div class="health-score-display">
                        <div class="score-circle">
                            <span class="score-number"><?php echo $healthScore; ?>%</span>
                        </div>
                        <div class="health-metrics">                            <div class="health-metric">
                                <span class="metric-label">Seller Activity</span>
                                <span class="metric-value"><?php echo round(($stats['active_sellers'] / max($stats['total_users'], 1)) * 100, 1); ?>%</span>
                            </div>
                            <div class="health-metric">
                                <span class="metric-label">Product Approval</span>
                                <span class="metric-value"><?php echo round(($stats['active_products'] / max($stats['active_products'] + $stats['pending_products_alt'], 1)) * 100, 1); ?>%</span>
                            </div>
                            <div class="health-metric">
                                <span class="metric-label">Order Success</span>
                                <span class="metric-value"><?php echo round(($stats['completed_orders'] / max($stats['completed_orders'] + $stats['pending_orders'], 1)) * 100, 1); ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_users']); ?></h3>
                            <p>Total Users</p>
                            <small><?php echo $stats['active_sellers']; ?> active sellers</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['active_products']); ?></h3>
                            <p>Active Products</p>
                            <small><?php echo $stats['pending_products_alt']; ?> pending</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['completed_orders']); ?></h3>
                            <p>Completed Orders</p>
                            <small><?php echo $stats['pending_orders']; ?> pending</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_downloads']); ?></h3>
                            <p>Downloads</p>
                            <small>All time</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_ratings']); ?></h3>
                            <p>Reviews</p>
                            <small>Customer feedback</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['cart_items']); ?></h3>
                            <p>Cart Items</p>
                            <small>Active sessions</small>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue Analytics -->
                <?php if (!empty($billingAnalytics)): ?>
                <div class="content-card">
                    <h3>Revenue Analytics</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Country</th>
                                    <th>Payment Method</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($billingAnalytics as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['country']); ?></td>
                                    <td><?php echo htmlspecialchars($item['payment_method']); ?></td>
                                    <td><?php echo number_format($item['total_orders']); ?></td>
                                    <td>₱<?php echo number_format($item['revenue'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="content-card">
                    <h3>Revenue Analytics</h3>
                    <div class="no-data">
                        <i class="fas fa-chart-line"></i>
                        <p>No billing data available yet</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
              <!-- Database Tab -->
            <div id="database" class="tab-panel">
                <div class="page-header">
                    <h1><i class="fas fa-database"></i> Database Management</h1>
                    <p>Backup and restore your Art2Cart database.</p>
                </div>
                
                <!-- Backup Section -->
                <div class="content-card">
                    <h3><i class="fas fa-download"></i> Database Backup</h3>
                    <div class="backup-section">
                        <div class="backup-info">
                            <p>Create a complete backup of your Art2Cart database. The backup will be automatically downloaded to your computer and a copy will be stored on the server.</p>
                            <div class="backup-status" id="backupStatus">
                                <span class="status-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Last backup: <span id="lastBackupDate">Never</span></span>
                                </span>
                            </div>
                        </div>
                        <div class="backup-actions">
                            <button class="btn btn-primary" id="createBackupBtn" onclick="createBackup()">
                                <i class="fas fa-download"></i> Create Backup
                            </button>
                            <button class="btn btn-secondary" onclick="refreshBackupList()">
                                <i class="fas fa-sync-alt"></i> Refresh List
                            </button>
                        </div>
                    </div>
                    
                    <!-- Backup History -->
                    <div class="backup-history">
                        <h4>Backup History</h4>
                        <div class="backup-list" id="backupList">
                            <div class="loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading backup history...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Restore Section -->
                <div class="content-card">
                    <h3><i class="fas fa-upload"></i> Database Restore</h3>
                    <div class="restore-section">
                        <div class="restore-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Warning:</strong> Restoring a database backup will replace all current data. 
                                A safety backup will be created automatically before the restore operation.
                            </div>
                        </div>
                        
                        <div class="restore-form">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Drop your .sql backup file here or click to browse</p>
                                    <input type="file" id="restoreFile" accept=".sql" style="display: none;">
                                    <button class="btn btn-outline" onclick="document.getElementById('restoreFile').click()">
                                        Browse Files
                                    </button>
                                </div>
                            </div>
                            
                            <div class="file-info" id="fileInfo" style="display: none;">
                                <div class="file-details">
                                    <i class="fas fa-file-alt"></i>
                                    <div>
                                        <strong id="fileName"></strong>
                                        <span id="fileSize"></span>
                                    </div>
                                </div>
                                <button class="btn btn-danger" id="restoreBtn" onclick="confirmRestore()" disabled>
                                    <i class="fas fa-upload"></i> Restore Database
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Database Info -->
                <div class="content-card">
                    <h3><i class="fas fa-info-circle"></i> Database Information</h3>
                    <div class="db-info-grid">
                        <div class="info-item">
                            <label>Database Name:</label>
                            <span>art2cart</span>
                        </div>
                        <div class="info-item">
                            <label>MySQL Version:</label>
                            <span id="mysqlVersion">Loading...</span>
                        </div>
                        <div class="info-item">
                            <label>Total Tables:</label>
                            <span id="totalTables">Loading...</span>
                        </div>
                        <div class="info-item">
                            <label>Database Size:</label>
                            <span id="databaseSize">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>        </main>
    </div>
    
    <!-- Seller Details Modal -->
    <div id="sellerDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-circle"></i> Seller Application Details</h3>
                <span class="modal-close" onclick="closeSellerModal()">&times;</span>
            </div>
            <div class="modal-body" id="sellerDetailsContent">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading seller details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeSellerModal()">Close</button>
                <div class="modal-actions" id="modalActions" style="display: none;">
                    <button class="btn btn-success" id="modalApproveBtn">
                        <i class="fas fa-check"></i> Approve Application
                    </button>
                    <button class="btn btn-danger" id="modalRejectBtn">
                        <i class="fas fa-times"></i> Reject Application
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <span class="modal-close" onclick="closeOrderModal()">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading order details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeOrderModal()">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Order Status Update Modal -->
    <div id="orderStatusModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Order Status</h2>
                <span class="modal-close" onclick="closeOrderStatusModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="orderStatus">Order Status:</label>
                    <select id="orderStatus" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="orderNotes">Notes (Optional):</label>
                    <textarea id="orderNotes" class="form-control" rows="3" placeholder="Add any notes about this status change..."></textarea>
                </div>
                <div id="orderStatusError" class="error-message" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeOrderStatusModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveOrderStatus()" id="saveOrderStatusBtn">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>        </div>
    </div>    <!-- Product Details Modal -->
    <div id="productDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-box"></i> Product Review Details</h3>
                <span class="modal-close" onclick="closeProductModal()">&times;</span>
            </div>
            <div class="modal-body" id="productDetailsContent">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading product details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeProductModal()">Close</button>
                <div class="modal-actions" id="productModalActions" style="display: none;">
                    <button class="btn btn-success" id="productApproveBtn">
                        <i class="fas fa-check"></i> Approve Product
                    </button>
                    <button class="btn btn-danger" id="productRejectBtn">
                        <i class="fas fa-times"></i> Reject Product
                    </button>
                </div>
            </div>
        </div>
    </div>    <!-- JavaScript -->
    <script src="admin/js/script.js"></script>
    <script src="admin/js/database_management.js"></script>
</body>
</html>
