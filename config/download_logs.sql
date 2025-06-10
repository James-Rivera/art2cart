-- Create download_logs table to track download activity
CREATE TABLE IF NOT EXISTS download_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NOT NULL,
    download_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_user_downloads (user_id, download_time),
    INDEX idx_product_downloads (product_id, download_time),
    INDEX idx_order_downloads (order_id, download_time)
);

-- Add downloads column to products table if it doesn't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS downloads INT DEFAULT 0;

-- Update existing products to have 0 downloads if NULL
UPDATE products SET downloads = 0 WHERE downloads IS NULL;
