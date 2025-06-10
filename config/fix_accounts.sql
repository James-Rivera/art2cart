-- Reset passwords for test accounts
UPDATE users SET password_hash = '$2y$10$8K1p/a7UqI1pj3uJn6XZY.x3USSOr.7o.X9kY2KF3YX3PIf9yO.W2' 
WHERE username = 'testuser';

-- Make sure admin has correct role
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u, roles r
WHERE u.username = 'admin' AND r.name = 'admin';
