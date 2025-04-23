<?php
session_start();
require_once '../config/Database.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'order_errors.log');

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

$order_id = (int)$_POST['order_id'];

// Kiểm tra đơn hàng tồn tại và thuộc về user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND (status = 'pending' OR status = 'waiting_payment')");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng này']);
    exit;
}

// Kiểm tra nếu đơn hàng đã thanh toán
if ($order['payment_status'] === 'completed') {
    echo json_encode(['success' => false, 'message' => 'Đơn hàng đã được thanh toán, không thể hủy']);
    exit;
}

try {
    $conn->begin_transaction();

    // Cập nhật trạng thái đơn hàng
    $stmt = $conn->prepare("UPDATE orders SET status = 'canceled', canceled_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi hủy đơn hàng: " . $stmt->error);
    }

    // Cập nhật trạng thái thanh toán nếu chưa thanh toán
    if ($order['payment_status'] !== 'completed') {
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi cập nhật trạng thái thanh toán: " . $stmt->error);
        }
    }

    // Hoàn lại số lượng sản phẩm
    $stmt = $conn->prepare("
        UPDATE products p 
        JOIN order_items oi ON p.id = oi.product_id 
        SET p.stock = p.stock + oi.quantity 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi hoàn lại số lượng sản phẩm: " . $stmt->error);
    }

    // Thêm lịch sử hủy đơn hàng
    $note = "Khách hàng hủy đơn hàng";
    $stmt = $conn->prepare("INSERT INTO order_history (order_id, status, note, created_at) VALUES (?, 'canceled', ?, NOW())");
    $stmt->bind_param("is", $order_id, $note);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi thêm lịch sử hủy đơn hàng: " . $stmt->error);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đơn hàng đã được hủy thành công']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại']);
}
?>
