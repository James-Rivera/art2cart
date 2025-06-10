<?php
require_once __DIR__ . '/../config/db.php';

class User {
    private $db;
    private $id;
    private $roles = [];

    public function __construct($userId) {
        $this->db = Database::getInstance()->getConnection();
        $this->id = $userId;
        $this->loadRoles();
    }

    private function loadRoles() {
        try {
            $stmt = $this->db->prepare("
                SELECT r.name 
                FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$this->id]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $this->roles = $roles;
        } catch (PDOException $e) {
            error_log("Error loading user roles: " . $e->getMessage());
            return false;
        }
    }

    public function hasRole($roleName) {
        return in_array($roleName, $this->roles);
    }

    public function addRole($roleName) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_roles (user_id, role_id)
                SELECT ?, id FROM roles WHERE name = ?
                ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$this->id, $roleName]);
        } catch (PDOException $e) {
            error_log("Error adding role: " . $e->getMessage());
            return false;
        }
    }

    public function removeRole($roleName) {
        try {
            $stmt = $this->db->prepare("
                DELETE ur FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND r.name = ?
            ");
            return $stmt->execute([$this->id, $roleName]);
        } catch (PDOException $e) {
            error_log("Error removing role: " . $e->getMessage());
            return false;
        }
    }

    public function getRoles() {
        return $this->roles;
    }

    public function isBoth() {
        return $this->hasRole('buyer') && $this->hasRole('seller');
    }

    public function getProducts($status = null) {
        try {            $sql = "
                SELECT 
                    p.id,
                    p.title,
                    p.description,
                    p.price,
                    p.status,
                    p.downloads,
                    p.image_path,
                    p.file_path,
                    p.created_at,
                    p.updated_at,
                    p.review_notes,
                    c.name as category_name,
                    c.slug as category_slug,
                    c.id as category_id,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(DISTINCT r.id) as rating_count
                FROM products p
                JOIN categories c ON p.category_id = c.id
                LEFT JOIN ratings r ON p.id = r.product_id
                WHERE p.seller_id = ?";
            
            if ($status) {
                $sql .= " AND p.status = ?";
            }
            
            $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            
            if ($status) {
                $stmt->execute([$this->id, $status]);
            } else {
                $stmt->execute([$this->id]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user products: " . $e->getMessage());
            return [];
        }
    }

    public function getProductStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_products,
                    SUM(downloads) as total_downloads,
                    COALESCE(AVG(
                        (SELECT AVG(rating) FROM ratings WHERE product_id = p.id)
                    ), 0) as average_rating
                FROM products p
                WHERE p.seller_id = ? AND p.status = 'active'
            ");
            $stmt->execute([$this->id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching product stats: " . $e->getMessage());
            return [
                'total_products' => 0,
                'total_downloads' => 0,
                'average_rating' => 0
            ];
        }
    }

    public function getProfileInfo() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    username,
                    email,
                    profile_image,
                    created_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$this->id]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userInfo) {
                $userInfo['roles'] = $this->getRoles();
                // Get first letter of username for avatar
                $userInfo['avatar_letter'] = strtoupper(substr($userInfo['username'], 0, 1));
            }
            
            return $userInfo;
        } catch (PDOException $e) {
            error_log("Error fetching user info: " . $e->getMessage());
            return null;
        }
    }
}
