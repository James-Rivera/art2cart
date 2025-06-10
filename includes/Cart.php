<?php
class Cart {
    private $db;
    private $pdo;
    
    public function __construct($database) {
        $this->db = $database;
        $this->pdo = $database->getConnection();
    }
    
    /**
     * Add item to cart
     */
    public function addToCart($user_id, $product_id, $quantity = 1) {
        try {            // Check if item already exists in cart
            $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update quantity if item exists
                $new_quantity = $existing['quantity'] + $quantity;
                $update_stmt = $this->pdo->prepare("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                return $update_stmt->execute([$new_quantity, $existing['id']]);
            } else {
                // Insert new item
                $insert_stmt = $this->pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                return $insert_stmt->execute([$user_id, $product_id, $quantity]);
            }
        } catch (PDOException $e) {
            error_log("Cart add error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove item from cart
     */    public function removeFromCart($user_id, $product_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$user_id, $product_id]);
        } catch (PDOException $e) {
            error_log("Cart remove error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update item quantity in cart
     */    public function updateQuantity($user_id, $product_id, $quantity) {
        try {
            if ($quantity <= 0) {
                return $this->removeFromCart($user_id, $product_id);
            }
            
            $stmt = $this->pdo->prepare("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
            return $stmt->execute([$quantity, $user_id, $product_id]);
        } catch (PDOException $e) {
            error_log("Cart update error: " . $e->getMessage());
            return false;
        }
    }    /**
     * Get all cart items for a user
     */
    public function getCartItems($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id as cart_id,
                    c.quantity,
                    c.created_at as added_at,
                    p.id as product_id,
                    p.title as product_name,
                    p.description,
                    p.price,
                    p.file_path,
                    p.category_id,                    COALESCE(cat.name, 'Uncategorized') as category_name,
                    COALESCE(cat.slug, 'uncategorized') as category_slug,
                    COALESCE(u.username, 'Unknown Seller') as seller_name,
                    COALESCE(u.id, 0) as seller_id
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Cart fetch error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cart item count for a user
     */    public function getCartCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_items'] ? (int)$result['total_items'] : 0;
        } catch (PDOException $e) {
            error_log("Cart count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get cart total amount
     */
    public function getCartTotal($user_id) {
        try {            $stmt = $this->pdo->prepare("
                SELECT SUM(c.quantity * p.price) as total_amount
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_amount'] ? (float)$result['total_amount'] : 0.0;
        } catch (PDOException $e) {
            error_log("Cart total error: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Clear cart for a user
     */    public function clearCart($user_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Cart clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if product is in cart
     */    public function isInCart($user_id, $product_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Cart check error: " . $e->getMessage());
            return false;
        }
    }
}
?>
