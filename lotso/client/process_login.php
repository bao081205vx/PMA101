<?php
session_start();
require_once '../config/Database.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'login_errors.log');

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Log input data
    error_log('=== New Login Attempt ===');
    error_log('Email input: ' . $email);
    error_log('Password length: ' . strlen($password));
    
    // Debug information
    error_log('Login attempt - Email: ' . $email);
    
    // Kiểm tra xem có dữ liệu POST không
    error_log('POST data received: ' . print_r($_POST, true));
    
    // Debug information
    error_log('Attempting login for email: ' . $email);
    
    // Truy vấn user từ database
    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        error_log('Found user in database:');
        error_log('Database email: ' . $user['email']);
        error_log('Database password hash: ' . substr($user['password'], 0, 10) . '...');
        error_log('User found in database');
        error_log('User details: ' . print_r($user, true));
        error_log('User role: ' . $user['role']);
        
        // Kiểm tra mật khẩu sử dụng password_verify
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Chuyển hướng về trang trước đó nếu có
            if (isset($_SESSION['return_to'])) {
                $return_to = $_SESSION['return_to'];
                unset($_SESSION['return_to']);
                header("Location: $return_to");
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không đúng';
        }
    } else {
        error_log('User not found in database');
        $error = 'Email hoặc mật khẩu không đúng';
    }
}

// Nếu có lỗi, lưu vào session và chuyển hướng về trang login
if ($error) {
    $_SESSION['login_error'] = $error;
    header('Location: login.php');
    exit;
}
?>
