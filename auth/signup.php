<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['email']) || !isset($data['password']) || 
    !isset($data['firstName']) || !isset($data['lastName'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username, email, password, first name, and last name are required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if email already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already exists']);
        exit;
    }

    // Check if username already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$data['username']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username already exists']);
        exit;
    }

    // Hash password and create user
    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);    $stmt = $db->prepare('INSERT INTO users (username, email, first_name, last_name, password_hash) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$data['username'], $data['email'], $data['firstName'], $data['lastName'], $password_hash]);

    $userId = $db->lastInsertId();

    // Add default 'buyer' role
    $stmt = $db->prepare('
        INSERT INTO user_roles (user_id, role_id)
        SELECT ?, id FROM roles WHERE name = ?
    ');
    $stmt->execute([$userId, 'buyer']);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
