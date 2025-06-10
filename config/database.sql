-- Create the database
CREATE DATABASE IF NOT EXISTS art2cart;
USE art2cart;

-- Create roles table first (before users table)
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table (without user_type ENUM)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Junction table for user roles
CREATE TABLE user_roles (
    user_id INT,
    role_id INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon_path VARCHAR(255)
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'pending', 'rejected') DEFAULT 'pending',
    downloads INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Ratings table
CREATE TABLE ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_rating (product_id, user_id)
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('buyer', 'Can purchase and download digital products'),
('seller', 'Can sell digital products and manage their store'),
('admin', 'Has full administrative access to the platform');

-- Insert default categories
INSERT INTO categories (name, slug, description, icon_path) VALUES
('Digital Art', 'digital-art', 'Digital artwork and illustrations', 'static/images/icons/palette.png'),
('Photography', 'photography', 'High-quality photographs', 'static/images/icons/camera.png'),
('Illustrations', 'illustrations', 'Hand-drawn and vector illustrations', 'static/images/icons/brush.png'),
('Templates', 'templates', 'Website and design templates', 'static/images/icons/layout-template.png');

-- Insert a test admin user (password: admin123) with roles
INSERT INTO users (username, email, password_hash) VALUES
('admin', 'admin@art2cart.com', '$2y$10$8K1p/a7UqI1pj3uJn6XZY.x3USSOr.7o.X9kY2KF3YX3PIf9yO.W2');

-- Assign admin role to the test user
INSERT INTO user_roles (user_id, role_id) 
SELECT 
    (SELECT id FROM users WHERE username = 'admin'),
    (SELECT id FROM roles WHERE name = 'admin');

-- Add some test users with multiple roles
INSERT INTO users (username, email, password_hash) VALUES
('testuser', 'test@art2cart.com', '$2y$10$8K1p/a7UqI1pj3uJn6XZY.x3USSOr.7o.X9kY2KF3YX3PIf9yO.W2');

-- Assign both buyer and seller roles to test user
INSERT INTO user_roles (user_id, role_id)
SELECT 
    (SELECT id FROM users WHERE username = 'testuser'),
    id
FROM roles 
WHERE name IN ('buyer', 'seller');

-- Create seller_applications table
CREATE TABLE seller_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    experience_years INT NOT NULL,
    portfolio_url VARCHAR(255) NOT NULL,
    bio TEXT NOT NULL,
    government_id_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_date TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
