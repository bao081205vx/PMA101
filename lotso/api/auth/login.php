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

    // Get and validate request data
    $rawData = file_get_contents('php://input');
    error_log("Raw request data: " . $rawData);
    
    $data = json_decode($rawData, true);
    if (!$data) {
        throw new Exception('Dữ liệu không hợp lệ: ' . json_last_error_msg());
    }

    error_log("Processed request data: " . print_r($data, true));

    // Validate required fields
    if (empty($data['username']) || empty($data['password'])) {
        throw new Exception('Vui lòng nhập đầy đủ thông tin đăng nhập');
    }

    // Check if user exists
    $stmt = $pdo->prepare("
        SELECT id, username, email, password, fullname, role 
        FROM users 
        WHERE (username = :username OR email = :email)
    ");
    
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':email', $data['username']);
    
    if (!$stmt->execute()) {
        error_log("Database error: " . print_r($stmt->errorInfo(), true));
        throw new Exception('Lỗi truy vấn cơ sở dữ liệu');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("User data: " . print_r($user, true));

    if (!$user) {
        throw new Exception('Tài khoản không tồn tại');
    }

    // Verify password
    if (!password_verify($data['password'], $user['password'])) {
        throw new Exception('Mật khẩu không chính xác');
    }

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set session data
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'fullname' => $user['fullname'],
        'role' => $user['role']
    ];

    error_log("Session data: " . print_r($_SESSION, true));

    // Return success response with user data
    sendResponse(200, true, 'Đăng nhập thành công', [
        'user' => $_SESSION['user'],
        'redirect' => $user['role'] === 'admin' ? '/lotso/public/' : '/lotso/client/'
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendResponse(400, false, $e->getMessage());
}
?>
