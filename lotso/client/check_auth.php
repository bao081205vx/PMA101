<?php
session_start();
require_once '../config/Database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['return_to'] = basename($_SERVER['PHP_SELF']);
    header('Location: login.php');
    exit;
}

// Kiểm tra giỏ hàng nếu đang ở trang checkout
if (basename($_SERVER['PHP_SELF']) === 'checkout.php' && empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();
?>
