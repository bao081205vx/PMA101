<?php
session_start();
require_once '../config/Database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Kiểm tra order_id
if (!isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đơn hàng']);
    exit;
}

$order_id = intval($_POST['order_id']);

// Kết nối database
$database = new Database();
$conn = $database->getConnection();

// Kiểm tra đơn hàng có tồn tại và thuộc về user không
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}

// Kiểm tra trạng thái đơn hàng
if ($order['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng ở trạng thái này']);
    exit;
}

// Cập nhật trạng thái đơn hàng
$stmt = $conn->prepare("UPDATE orders SET status = 'canceled' WHERE id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    // Hoàn lại số lượng sản phẩm trong kho
    $stmt = $conn->prepare("
        UPDATE products p 
        JOIN order_items oi ON p.id = oi.product_id 
        SET p.stock = p.stock + oi.quantity 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại']);
}
