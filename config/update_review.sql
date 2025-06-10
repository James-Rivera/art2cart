ALTER TABLE products
ADD COLUMN review_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
ADD COLUMN review_notes TEXT,
ADD COLUMN review_date TIMESTAMP NULL,
ADD COLUMN reviewed_by INT,
ADD FOREIGN KEY (reviewed_by) REFERENCES users(id);
