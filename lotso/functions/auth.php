<?php
session_start();

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /lotso/public/login.php');
        exit;
    }
}

/**
 * Kiểm tra xem người dùng có phải là admin không
 */
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: /lotso/public/login.php');
        exit;
    }
}

/**
 * Lấy thông tin người dùng hiện tại
 */
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        global $conn;
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
} 