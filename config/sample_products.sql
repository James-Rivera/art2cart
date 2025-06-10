-- Insert some sample products
INSERT INTO products (title, description, price, image_path, category_id, seller_id, status, downloads) VALUES
-- Digital Art
('Mob Ultra Sonic', 'A stunning digital artwork featuring ultra sonic waves', 25.99, 'static/images/products/sample.jpg', 
 (SELECT id FROM categories WHERE slug = 'digital-art'), 
 (SELECT id FROM users WHERE username = 'admin'), 'active', 123),
('Alter Ego', 'Creative digital art exploring identity', 29.99, 'static/images/products/Alter Ego.png',
 (SELECT id FROM categories WHERE slug = 'digital-art'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 89),
('Wandering Whales', 'Ethereal digital art with floating whales', 19.99, 'static/images/products/Wandering Whales.png',
 (SELECT id FROM categories WHERE slug = 'digital-art'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 156),

-- Photography
('Street Life', 'Urban photography capturing city moments', 15.99, 'static/images/products/Scenary.png',
 (SELECT id FROM categories WHERE slug = 'photography'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 78),
('Urban Perspective', 'Modern architectural photography', 18.99, 'static/images/products/sample.jpg',
 (SELECT id FROM categories WHERE slug = 'photography'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 92),

-- Illustrations
('Anime Style', 'Japanese-inspired illustration', 22.99, 'static/images/products/sample.jpg',
 (SELECT id FROM categories WHERE slug = 'illustrations'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 245),
('Fantasy World', 'Detailed fantasy illustration', 24.99, 'static/images/products/Wandering Whales.png',
 (SELECT id FROM categories WHERE slug = 'illustrations'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 167),

-- Templates
('Modern Portfolio', 'Clean and professional portfolio template', 34.99, 'static/images/products/sample.jpg',
 (SELECT id FROM categories WHERE slug = 'templates'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 312),
('Business Cards', 'Elegant business card templates', 12.99, 'static/images/products/Alter Ego.png',
 (SELECT id FROM categories WHERE slug = 'templates'),
 (SELECT id FROM users WHERE username = 'admin'), 'active', 423);
