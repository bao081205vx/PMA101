<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once '../config/Database.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: profile.php');
    exit;
}

$order_id = (int)$_GET['order_id'];

// Kiểm tra đơn hàng
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ? AND payment_method = 'bank' 
    AND (status = 'pending' OR status = 'waiting_payment')
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Đơn hàng không hợp lệ hoặc không thể xác nhận thanh toán';
    header('Location: profile.php');
    exit;
}

// Cập nhật trạng thái đơn hàng
$stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed', status = 'processing' WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();

// Thêm vào lịch sử đơn hàng
$note = 'Khách hàng đã xác nhận thanh toán chuyển khoản';
$stmt = $conn->prepare("INSERT INTO order_history (order_id, status, note) VALUES (?, 'processing', ?)");
$stmt->bind_param("is", $order_id, $note);
$stmt->execute();

// Chuyển đến trang cảm ơn
header('Location: thank-you.php?order_id=' . $order_id);
exit;
?>
