<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private $host = 'localhost';
    private $db_name = 'art2cart';
    private $username = 'root';
    private $password = '';

    public static function initErrorReporting() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
