<?php
require_once '../../config/db.php';
require_once '../../functions/response.php';
require_once '../../functions/validate.php';

header('Content-Type: application/json');

try {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Get database connection
    if (!isset($pdo)) {
        throw new Exception('Không thể kết nối đến cơ sở dữ liệu');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không được hỗ trợ');
    }

    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    // Validate required fields
    $required = ['username', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Vui lòng nhập $field");
        }
    }

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không hợp lệ');
    }

    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $data['username']);
    $stmt->execute();
    if ($stmt->fetch()) {
        throw new Exception('Tên đăng nhập đã tồn tại');
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();
    if ($stmt->fetch()) {
        throw new Exception('Email đã được sử dụng');
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, fullname, phone, address, role, created_at) 
        VALUES (:username, :email, :password, :fullname, :phone, :address, 'user', NOW())
    ");

    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':fullname', $data['fullname']);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':address', $data['address']);
    $stmt->execute();

    sendResponse(201, true, 'Đăng ký thành công');
} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    sendResponse(400, false, $e->getMessage());
}
