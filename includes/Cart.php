<?php
/**
 * Enhanced Cart Class with InnoDB Transaction Support
 * Updated to work with the new database structure and foreign key constraints
 */

class Cart {
    private $db;
    private $pdo;
    
    public function __construct($database) {
        $this->db = $database;
        $this->pdo = $database->getConnection();
    }

    /**
     * Check if a transaction is already active
     */
    private function isTransactionActive() {
        return $this->pdo->inTransaction();
    }

    /**
     * Add item to cart with transaction support
     * For digital marketplace: each product can only be added once per user
     */
    public function addToCart($user_id, $product_id, $quantity = 1) {
        // Only start transaction if one isn't already active
        $startedTransaction = false;
        if (!$this->isTransactionActive()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        
        try {
            // Verify product exists and is active
            $product_check = $this->pdo->prepare("
                SELECT id, status, price, title 
                FROM products 
                WHERE id = ? AND status = 'active'
            ");
            $product_check->execute([$product_id]);
            $product = $product_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Product not found or not available");
            }
            
            // Verify user exists
            $user_check = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
            $user_check->execute([$user_id]);
            if (!$user_check->fetch()) {
                throw new Exception("User not found");
            }
            
            // Check if item already exists in cart
            $stmt = $this->pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Item already in cart, just update timestamp
                $update_stmt = $this->pdo->prepare("
                    UPDATE cart 
                    SET updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $result = $update_stmt->execute([$existing['id']]);
            } else {
                // Insert new item (no quantity column for digital products)
                $insert_stmt = $this->pdo->prepare("
                    INSERT INTO cart (user_id, product_id, created_at, updated_at) 
                    VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $result = $insert_stmt->execute([$user_id, $product_id]);
            }
              if (!$result) {
                throw new Exception("Failed to add item to cart");
            }
            
            // Only commit if this method started the transaction
            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return true;
            
        } catch (Exception $e) {
            // Only rollback if this method started the transaction
            if ($startedTransaction) {
                $this->pdo->rollback();
            }
            error_log("Cart add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove item from cart with foreign key constraint handling
     */
    public function removeFromCart($user_id, $product_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM cart 
                WHERE user_id = ? AND product_id = ?
            ");
            return $stmt->execute([$user_id, $product_id]);
        } catch (PDOException $e) {
            error_log("Cart remove error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update item in cart (for digital marketplace, this just removes the item if quantity <= 0)
     * Maintained for API compatibility
     */
    public function updateQuantity($user_id, $product_id, $quantity) {
        try {
            if ($quantity <= 0) {
                return $this->removeFromCart($user_id, $product_id);
            }
            
            // For digital products, any quantity > 0 means keep the item
            // Just update the timestamp to show activity
            $stmt = $this->pdo->prepare("
                UPDATE cart 
                SET updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = ? AND product_id = ?
            ");
            return $stmt->execute([$user_id, $product_id]);
        } catch (PDOException $e) {
            error_log("Cart update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all cart items for a user with enhanced error handling and foreign key awareness
     */
    public function getCartItems($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id as cart_id,
                    1 as quantity,
                    c.created_at as added_at,
                    c.updated_at,
                    p.id as product_id,
                    p.title as product_name,
                    p.description,
                    p.price,
                    p.image_path,
                    p.file_path,
                    p.category_id,
                    p.status as product_status,
                    cat.name as category_name,
                    cat.slug as category_slug,
                    seller.username as seller_name,
                    seller.id as seller_id,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.id) as rating_count
                FROM cart c
                INNER JOIN products p ON c.product_id = p.id
                INNER JOIN categories cat ON p.category_id = cat.id
                INNER JOIN users seller ON p.seller_id = seller.id
                LEFT JOIN ratings r ON p.id = r.product_id
                WHERE c.user_id = ? 
                    AND p.status = 'active'
                GROUP BY c.id, p.id, cat.id, seller.id
                ORDER BY c.created_at DESC
            ");
            
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Clean up any cart items with inactive/deleted products
            if ($stmt->rowCount() == 0) {
                $this->cleanupInvalidCartItems($user_id);
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Cart items fetch error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cart item count for a user
     */
    public function getCartCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_items
                FROM cart c
                INNER JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? AND p.status = 'active'
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_items'] ? (int)$result['total_items'] : 0;
        } catch (PDOException $e) {
            error_log("Cart count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get cart total amount with validation
     */
    public function getCartTotal($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT SUM(p.price) as total_amount
                FROM cart c
                INNER JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? AND p.status = 'active'
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_amount'] ? (float)$result['total_amount'] : 0.0;
        } catch (PDOException $e) {
            error_log("Cart total error: " . $e->getMessage());
            return 0.0;
        }
    }    /**
     * Clear cart for a user with transaction support
     */
    public function clearCart($user_id) {
        // Only start transaction if one isn't already active
        $startedTransaction = false;
        if (!$this->isTransactionActive()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $result = $stmt->execute([$user_id]);
            
            if (!$result) {
                throw new Exception("Failed to clear cart");
            }
            
            // Only commit if this method started the transaction
            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return true;
            
        } catch (Exception $e) {
            // Only rollback if this method started the transaction
            if ($startedTransaction) {
                $this->pdo->rollback();
            }
            error_log("Cart clear error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up cart items that reference inactive or deleted products
     * This helps maintain referential integrity
     */
    private function cleanupInvalidCartItems($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE c FROM cart c
                LEFT JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? 
                    AND (p.id IS NULL OR p.status != 'active')
            ");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Cleaned up " . $stmt->rowCount() . " invalid cart items for user $user_id");
            }
            
        } catch (PDOException $e) {
            error_log("Cart cleanup error: " . $e->getMessage());
        }
    }

    /**
     * Validate cart items before checkout
     * Ensures all items are still available and prices haven't changed
     */
    public function validateCartForCheckout($user_id) {
        try {
            $cartItems = $this->getCartItems($user_id);
            $issues = [];
            
            foreach ($cartItems as $item) {
                // Check if product is still active
                if ($item['product_status'] !== 'active') {
                    $issues[] = "Product '{$item['product_name']}' is no longer available";
                    // Remove from cart
                    $this->removeFromCart($user_id, $item['product_id']);
                }
                
                // Verify current price
                $stmt = $this->pdo->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$item['product_id']]);
                $current = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($current && abs($current['price'] - $item['price']) > 0.01) {
                    $issues[] = "Price for '{$item['product_name']}' has changed from ₱" . 
                               number_format($item['price'], 2) . " to ₱" . 
                               number_format($current['price'], 2);
                }
            }
            
            return [
                'valid' => empty($issues),
                'issues' => $issues,
                'updated_items' => $this->getCartItems($user_id)
            ];
            
        } catch (Exception $e) {
            error_log("Cart validation error: " . $e->getMessage());
            return [
                'valid' => false,
                'issues' => ['An error occurred while validating your cart'],
                'updated_items' => []
            ];
        }
    }

    /**
     * Get cart statistics for admin/analytics
     */
    public function getCartStatistics() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT c.user_id) as active_carts,
                    COUNT(*) as total_items,
                    AVG(p.price) as avg_item_price,
                    SUM(p.price) as total_value,
                    MAX(c.created_at) as latest_addition
                FROM cart c
                INNER JOIN products p ON c.product_id = p.id
                WHERE p.status = 'active'
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Cart statistics error: " . $e->getMessage());
            return null;
        }
    }    /**
     * Transfer cart items from one user to another (useful for guest to registered user)
     */
    public function transferCart($from_user_id, $to_user_id) {
        // Only start transaction if one isn't already active
        $startedTransaction = false;
        if (!$this->isTransactionActive()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        
        try {
            // Verify both users exist
            $user_check = $this->pdo->prepare("SELECT id FROM users WHERE id IN (?, ?)");
            $user_check->execute([$from_user_id, $to_user_id]);
            if ($user_check->rowCount() < 2) {
                throw new Exception("One or both users not found");
            }
            
            // Get items from source cart
            $source_items = $this->getCartItems($from_user_id);
            
            foreach ($source_items as $item) {
                // Add to destination cart (this will handle duplicates)
                $this->addToCart($to_user_id, $item['product_id']);
            }
            
            // Clear source cart
            $this->clearCart($from_user_id);
            
            // Only commit if this method started the transaction
            if ($startedTransaction) {
                $this->pdo->commit();            }
            return true;
            
        } catch (Exception $e) {
            // Only rollback if this method started the transaction
            if ($startedTransaction) {
                $this->pdo->rollback();
            }
            error_log("Cart transfer error: " . $e->getMessage());
            return false;
        }
    }
}
?>
