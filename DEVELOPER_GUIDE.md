# Art2Cart E-commerce Platform - Developer Guide

## Table of Contents
1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Directory Structure](#directory-structure)
4. [Database Architecture](#database-architecture)
5. [Core Components](#core-components)
6. [Authentication System](#authentication-system)
7. [Shopping Cart Implementation](#shopping-cart-implementation)
8. [File Upload System](#file-upload-system)
9. [Admin Dashboard](#admin-dashboard)
10. [API Endpoints](#api-endpoints)
11. [Frontend Structure](#frontend-structure)
12. [Deployment Guide](#deployment-guide)
13. [Development Workflow](#development-workflow)

## Project Overview

Art2Cart is a digital marketplace platform where artists can sell digital products (art, templates, 3D models, etc.) and buyers can purchase and download them. The platform features user authentication, role-based access, shopping cart functionality, order processing, and administrative controls.

### Key Features
- Multi-role user system (Buyer, Seller, Admin)
- Digital product catalog with categories
- Shopping cart and checkout system
- Secure file downloads for purchased products
- Seller dashboard for product management
- Admin panel for platform oversight
- Responsive design for mobile and desktop

## Technology Stack

### Backend
- **Language**: PHP 8.x
- **Database**: MySQL with PDO
- **Architecture**: MVC-like structure with custom classes
- **Session Management**: PHP native sessions
- **File Handling**: Custom upload and download system

### Frontend
- **HTML5**: Semantic markup structure
- **CSS3**: Custom stylesheets with responsive design
- **JavaScript**: Vanilla JS for interactivity
- **External Libraries**: Bootstrap 5.3.3 (partial usage)

### Development Environment
- **Server**: WAMP (Windows, Apache, MySQL, PHP)
- **Database Tool**: phpMyAdmin
- **Version Control**: Git (recommended)

## Directory Structure

```
Art2Cart/
├── Root Files (Main Pages)
│   ├── index.php              # Homepage
│   ├── catalogue.php          # Product catalog
│   ├── product_preview.php    # Individual product pages
│   ├── cart.php              # Shopping cart
│   ├── checkout.php          # Checkout process
│   ├── account.php           # User account page
│   └── order-confirmation.php # Order success page
│
├── admin/                     # Administrative Interface
│   ├── admin_dashboard.php    # Main admin panel
│   ├── create_admin.php      # Admin user creation
│   ├── get_product_details.php # Product details API
│   └── script.js             # Admin panel JavaScript
│
├── api/                      # API Endpoints
│   ├── cart.php              # Cart operations API
│   ├── checkout.php          # Checkout processing
│   └── products.php          # Product data API
│
├── auth/                     # Authentication System
│   ├── auth.html             # Login/Register form
│   ├── login.php             # Login processing
│   ├── signup.php            # Registration processing
│   ├── logout.php            # Session termination
│   └── become_seller.html    # Seller upgrade form
│
├── config/                   # Configuration & Database
│   ├── db.php                # Database connection class
│   ├── art2cart.sql          # Full database dump
│   ├── database.sql          # Initial schema
│   └── *.sql                 # Various DB updates
│
├── includes/                 # Core Classes
│   ├── User.php              # User management class
│   ├── Cart.php              # Shopping cart class
│   └── products.php          # Product management functions
│
├── seller/                   # Seller Interface
│   ├── dashboard.php         # Seller dashboard
│   ├── upload_product.php    # Product upload
│   └── delete_product.php    # Product removal
│
├── static/                   # Static Assets
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   ├── images/               # Images and icons
│   └── templates/            # Reusable HTML components
│
├── tests/                    # Testing & Debugging
│   └── *.php                 # Various test files
│
└── uploads/                  # User-uploaded Files
    ├── files/                # Digital products
    └── government_ids/       # Seller verification docs
```

## Database Architecture

### Core Tables

#### users
```sql
- id (Primary Key)
- username (Unique)
- email (Unique)
- password_hash
- profile_image
- created_at, updated_at
```

#### roles & user_roles
```sql
roles: id, name, description
user_roles: user_id, role_id (Junction table)
```

#### categories
```sql
- id (Primary Key)
- name, slug (Unique)
- description
- icon_path
- display_order
```

#### products
```sql
- id (Primary Key)
- seller_id (Foreign Key → users.id)
- category_id (Foreign Key → categories.id)
- title, description
- price (DECIMAL)
- image_path, file_path
- status (ENUM: active, inactive, pending, rejected)
- downloads (Counter)
- created_at, updated_at
```

#### cart
```sql
- id (Primary Key)
- user_id (Foreign Key → users.id)
- product_id (Foreign Key → products.id)
- quantity
- created_at, updated_at
- UNIQUE constraint on (user_id, product_id)
```

#### orders & order_items
```sql
orders: id, user_id, total_amount, status, billing_info, created_at
order_items: id, order_id, product_id, price, quantity, created_at
```

### Key Relationships
- Users can have multiple roles (many-to-many)
- Products belong to one category and one seller
- Cart items link users to products with quantities
- Orders contain multiple products through order_items

## Core Components

### 1. Database Connection (`config/db.php`)
```php
class Database {
    private static $instance = null;
    private $conn;
    
    // Singleton pattern for PDO connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### 2. User Management (`includes/User.php`)
```php
class User {
    private $id;
    private $db;
    
    public function __construct($user_id) {
        $this->id = $user_id;
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Methods: getProfileInfo(), hasRole(), getProducts(), etc.
}
```

### 3. Shopping Cart (`includes/Cart.php`)
```php
class Cart {
    private $db;
    private $pdo;
    
    // Methods: addToCart(), removeFromCart(), getCartItems(), etc.
}
```

## Authentication System

### Session-Based Authentication
- Login sets `$_SESSION['user_id']`
- Role checking via `User::hasRole()` method
- Automatic redirects for unauthorized access

### User Roles
1. **Buyer** (Default): Can purchase and download products
2. **Seller**: Can upload and manage products
3. **Admin**: Full platform access

### Security Features
- Password hashing with `password_hash()`
- SQL injection prevention via prepared statements
- Session-based CSRF protection
- File upload validation

## Shopping Cart Implementation

### Cart Operations
```php
// Add to cart
$cart->addToCart($user_id, $product_id, $quantity);

// Get cart items with product details
$items = $cart->getCartItems($user_id);

// Calculate total
$total = $cart->getCartTotal($user_id);
```

### AJAX Cart Updates
- Real-time quantity updates
- Instant total recalculation
- Cart count display in header

## File Upload System

### Product Files
- **Location**: `uploads/files/`
- **Security**: Original filenames hashed
- **Validation**: File type and size restrictions

### Download Protection
- Ownership verification before download
- Download logging for analytics
- Proper HTTP headers for file delivery

## Admin Dashboard

### Features
- Product approval/rejection
- Seller application management
- Platform statistics
- User management

### Access Control
```php
// Admin-only pages check
if (!$user->hasRole('admin')) {
    http_response_code(403);
    exit;
}
```

## API Endpoints

### Cart API (`api/cart.php`)
- POST: Add item to cart
- DELETE: Remove item from cart
- PUT: Update quantity

### Products API (`api/products.php`)
- GET: Fetch products by category
- GET: Product details
- POST: Create product (sellers only)

## Frontend Structure

### CSS Organization
```
static/css/
├── var.css              # CSS variables and theme
├── fonts.css            # Font definitions
├── responsive.css       # Media queries
├── index/               # Homepage styles
├── catalogue/           # Catalog page styles
└── template/           # Reusable component styles
```

### JavaScript Modules
```
static/js/
├── main.js             # Core functionality
├── load.js             # Dynamic content loading
├── cart.js             # Cart operations
├── artists.js          # Artist showcase
└── featured-products.js # Product carousels
```

### Template System
- Header: `static/templates/header_new.php`
- Footer: `static/templates/footer_new.html`
- Dynamic loading via `fetch()` API

## Deployment Guide

### Prerequisites
1. PHP 8.0+ with PDO extension
2. MySQL 5.7+ or MariaDB
3. Apache with mod_rewrite
4. SSL certificate (for production)

### Installation Steps
1. Clone repository to web root
2. Import `config/art2cart.sql` to MySQL
3. Update database credentials in `config/db.php`
4. Set proper file permissions on `uploads/` directory
5. Configure Apache virtual host
6. Create admin user via `admin/create_admin.php`

### Environment Configuration
```php
// config/db.php
private $host = 'localhost';        // Production: actual host
private $db_name = 'art2cart';      // Production: database name
private $username = 'root';         // Production: db username
private $password = '';             // Production: secure password
```

## Development Workflow

### Adding New Features
1. **Database Changes**: Update schema in `config/` directory
2. **Backend Logic**: Add methods to existing classes or create new ones
3. **Frontend Updates**: Add CSS/JS files in `static/` directory
4. **Testing**: Create test files in `tests/` directory

### Code Standards
- Use prepared statements for all database queries
- Validate and sanitize all user inputs
- Follow PSR-4 autoloading conventions where possible
- Use semantic HTML and responsive CSS
- Comment complex business logic

### Common Patterns

#### Page Structure
```php
<?php
session_start();
require_once 'config/db.php';
require_once 'includes/User.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /Art2Cart/auth/auth.html');
    exit;
}

// Business logic here
?>
<!DOCTYPE html>
<html>
<!-- HTML content -->
</html>
```

#### AJAX Responses
```php
header('Content-Type: application/json');

try {
    // Operation logic
    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

## Troubleshooting

### Common Issues
1. **Database Connection**: Check WAMP services and credentials
2. **File Uploads**: Verify directory permissions and PHP limits
3. **Session Issues**: Clear browser cookies and check session configuration
4. **Cart Problems**: Check cart table integrity and foreign keys

### Debug Tools
- Enable error reporting: `error_reporting(E_ALL)`
- Use test files in `tests/` directory
- Check `php_errors.log` for server errors
- Browser developer tools for frontend issues

### Performance Optimization
- Implement database indexing for large datasets
- Use CSS/JS minification for production
- Enable gzip compression
- Implement caching for frequently accessed data

---

## Contributing

When contributing to this project:
1. Follow the established code structure
2. Test thoroughly using files in `tests/` directory
3. Document any new features or changes
4. Maintain backward compatibility where possible

For questions or support, refer to the implementation details in each component file and the extensive test suite provided.
