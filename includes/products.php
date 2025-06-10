<?php
require_once __DIR__ . '/../config/db.php';

class ProductService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }    public function getProductsByCategory($categorySlug, $userId = null) {
        try {
            error_log("Attempting to fetch products for category: " . $categorySlug);
            
            $sql = "
                SELECT 
                    p.id,
                    p.title,
                    p.price,
                    CONCAT('/Art2Cart/', p.image_path) as image,
                    p.downloads,
                    u.username as seller_name,
                    COALESCE(AVG(r.rating), 0) as rating
                FROM products p
                JOIN categories c ON p.category_id = c.id
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN ratings r ON p.id = r.product_id
                WHERE c.slug = :slug
                AND p.status = 'active'";
            
            $params = ['slug' => $categorySlug];
            
            // Exclude products already purchased by the user
            if ($userId) {
                $sql .= " AND p.id NOT IN (
                    SELECT DISTINCT oi.product_id 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE o.user_id = :user_id 
                    AND o.status = 'completed'
                )";
                $params['user_id'] = $userId;
            }
            
            $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            error_log("Found " . count($results) . " products for category: " . $categorySlug);
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return $this->getDummyProducts($categorySlug);
        }    }

    public function getPurchasedProducts($userId) {
        try {
            error_log("Fetching purchased products for user: " . $userId);
            
            $stmt = $this->db->prepare("
                SELECT DISTINCT
                    p.id,
                    p.title,
                    p.price,
                    CONCAT('/Art2Cart/', p.image_path) as image,
                    p.file_path,
                    p.downloads,
                    u.username as seller_name,
                    COALESCE(AVG(r.rating), 0) as rating,
                    o.created_at as purchase_date,
                    o.id as order_id
                FROM products p
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders o ON oi.order_id = o.id
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN ratings r ON p.id = r.product_id
                WHERE o.user_id = :user_id
                AND o.status = 'completed'
                GROUP BY p.id, o.id
                ORDER BY o.created_at DESC
            ");
            
            $stmt->execute(['user_id' => $userId]);
            $results = $stmt->fetchAll();
            error_log("Found " . count($results) . " purchased products for user: " . $userId);
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching purchased products: " . $e->getMessage());
            return [];
        }
    }

    public function getProductById($productId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.id,
                    p.title,
                    p.description,
                    p.price,
                    p.downloads,
                    p.image_path,
                    p.file_path,
                    p.created_at,
                    c.name as category_name,
                    c.slug as category_slug,
                    u.username as seller_name,
                    u.id as seller_id,
                    COALESCE(AVG(r.rating), 0) as rating,
                    COUNT(r.id) as rating_count
                FROM products p
                JOIN categories c ON p.category_id = c.id
                JOIN users u ON p.seller_id = u.id
                LEFT JOIN ratings r ON p.id = r.product_id
                WHERE p.id = ? AND p.status = 'active'
                GROUP BY p.id
            ");
            $stmt->execute([$productId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching product by ID: " . $e->getMessage());
            return null;
        }
    }

    public function getProductRatings($productId) {
        try {
            // Get rating breakdown
            $stmt = $this->db->prepare("
                SELECT 
                    rating,
                    COUNT(*) as count
                FROM ratings 
                WHERE product_id = ?
                GROUP BY rating
                ORDER BY rating DESC
            ");
            $stmt->execute([$productId]);
            $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get average and total count
            $stmt = $this->db->prepare("
                SELECT 
                    COALESCE(AVG(rating), 0) as average,
                    COUNT(*) as total
                FROM ratings 
                WHERE product_id = ?
            ");
            $stmt->execute([$productId]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            // Format breakdown
            $ratingBreakdown = [];
            for ($i = 5; $i >= 1; $i--) {
                $ratingBreakdown[$i] = 0;
            }
            
            foreach ($breakdown as $rating) {
                $ratingBreakdown[(int)$rating['rating']] = (int)$rating['count'];
            }

            return [
                'average' => round($summary['average'], 1),
                'total' => (int)$summary['total'],
                'breakdown' => $ratingBreakdown
            ];
        } catch (PDOException $e) {
            error_log("Error fetching product ratings: " . $e->getMessage());
            return [
                'average' => 0,
                'total' => 0,
                'breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
            ];
        }
    }

    public function getUserRating($productId, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT rating, comment 
                FROM ratings 
                WHERE product_id = ? AND user_id = ?
            ");
            $stmt->execute([$productId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user rating: " . $e->getMessage());
            return null;
        }
    }

    public function submitRating($productId, $userId, $rating, $comment = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ratings (product_id, user_id, rating, comment)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    rating = VALUES(rating),
                    comment = VALUES(comment),
                    created_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$productId, $userId, $rating, $comment]);
        } catch (PDOException $e) {
            error_log("Error submitting rating: " . $e->getMessage());
            return false;
        }
    }

    // Keeping the dummy data for testing purposes
    private function getDummyProducts($category) {
        // Dummy data for testing
        $products = [
            'digital-art' => [
                [
                    'id' => 1,                    'title' => 'Mob Ultra Sonic',
                    'price' => 25.99,
                    'image' => '/Art2Cart/static/images/products/sample.jpg',
                    'seller_name' => 'Jim Lee',
                    'rating' => 4.5,
                    'downloads' => 123
                ],
                [
                    'id' => 2,
                    'title' => 'Alter Ego',
                    'price' => 29.99,
                    'image' => 'static/images/products/Alter Ego.png',
                    'seller_name' => 'Maria Garcia',
                    'rating' => 4.8,
                    'downloads' => 89
                ],
                [
                    'id' => 3,
                    'title' => 'Wandering Whales',
                    'price' => 19.99,
                    'image' => 'static/images/products/Wandering Whales.png',
                    'seller_name' => 'Alex Chen',
                    'rating' => 4.7,
                    'downloads' => 156
                ]
            ],
            'photography' => [
                [
                    'id' => 4,
                    'title' => 'Street Life',
                    'price' => 15.99,
                    'image' => 'static/images/products/Scenary.png',
                    'seller_name' => 'John Smith',
                    'rating' => 4.2,
                    'downloads' => 78
                ],
                [
                    'id' => 5,
                    'title' => 'Urban Perspective',
                    'price' => 18.99,
                    'image' => 'static/images/products/sample.jpg',
                    'seller_name' => 'Sarah Williams',
                    'rating' => 4.6,
                    'downloads' => 92
                ]
            ],
            'illustrations' => [
                [
                    'id' => 6,
                    'title' => 'Anime Style',
                    'price' => 22.99,
                    'image' => 'static/images/products/sample.jpg',
                    'seller_name' => 'Yuki Tanaka',
                    'rating' => 4.9,
                    'downloads' => 245
                ],
                [
                    'id' => 7,
                    'title' => 'Fantasy World',
                    'price' => 24.99,
                    'image' => 'static/images/products/Wandering Whales.png',
                    'seller_name' => 'Emma Davis',
                    'rating' => 4.7,
                    'downloads' => 167
                ]
            ],
            'templates' => [
                [
                    'id' => 8,
                    'title' => 'Modern Portfolio',
                    'price' => 34.99,
                    'image' => 'static/images/products/sample.jpg',
                    'seller_name' => 'Design Studio',
                    'rating' => 4.8,
                    'downloads' => 312
                ],
                [
                    'id' => 9,
                    'title' => 'Business Cards',
                    'price' => 12.99,
                    'image' => 'static/images/products/Alter Ego.png',
                    'seller_name' => 'Creative Labs',
                    'rating' => 4.5,
                    'downloads' => 423
                ]
            ]
        ];
        
        return isset($products[$category]) ? $products[$category] : [];
    }    public function getAllCategories() {
        try {
            $stmt = $this->db->query("
                SELECT id, name, slug, description, icon_path 
                FROM categories 
                ORDER BY display_order, name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }
}

// Function to get products (works with both real DB and fallback dummy data)
function getProductsByCategory($category, $userId = null) {
    $productService = new ProductService();
    return $productService->getProductsByCategory($category, $userId);
}

// Function to get purchased products for a user
function getPurchasedProducts($userId) {
    $productService = new ProductService();
    return $productService->getPurchasedProducts($userId);
}

// Add global function to get all categories
function getAllCategories() {
    $productService = new ProductService();
    return $productService->getAllCategories();
}
?>
