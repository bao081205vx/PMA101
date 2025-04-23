<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once '../config/Database.php';
require_once '../functions/payment_functions.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['order_id'])) {
    header('Location: profile.php');
    exit;
}

$order_id = (int)$_GET['order_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ? AND o.payment_method = 'bank' AND (o.status = 'pending' OR o.status = 'waiting_payment')
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Đơn hàng không hợp lệ hoặc không thể thanh toán';
    header('Location: profile.php');
    exit;
}

if ($order['payment_status'] === 'completed') {
    $_SESSION['error'] = 'Đơn hàng này đã được thanh toán';
    header('Location: order-detail.php?id=' . $order_id);
    exit;
}

// Lấy thông tin ngân hàng
$bank_info = $conn->query("SELECT * FROM settings WHERE `key` = 'bank_info'")->fetch_assoc();
$bank_info = $bank_info ? json_decode($bank_info['value'], true) : null;

if (!$bank_info) {
    $_SESSION['error'] = 'Chưa cấu hình thông tin ngân hàng';
    header('Location: order-detail.php?id=' . $order_id);
    exit;
}

// Cập nhật trạng thái đơn hàng
$stmt = $conn->prepare("UPDATE orders SET status = 'pending' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();

// Thêm vào lịch sử đơn hàng
$note = 'Khách hàng đã nhận thông tin thanh toán chuyển khoản';
$stmt = $conn->prepare("INSERT INTO order_history (order_id, status, note) VALUES (?, 'pending', ?)");
$stmt->bind_param("is", $order_id, $note);
$stmt->execute();

// Hiển thị thông tin thanh toán
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin thanh toán - Lotso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <i class="fas fa-university text-primary" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-4">Thông tin chuyển khoản</h2>
                        
                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Số tiền:</strong> <?= number_format($order['total_price']) ?> đ</p>
                        </div>

                        <div class="text-start bg-light p-4 rounded mb-4">
                            <p><strong>Tên ngân hàng:</strong> <?= htmlspecialchars($bank_info['bank_name']) ?></p>
                            <p><strong>Chủ tài khoản:</strong> <?= htmlspecialchars($bank_info['account_name']) ?></p>
                            <p><strong>Số tài khoản:</strong> <?= htmlspecialchars($bank_info['account_number']) ?></p>
                            <p class="mb-0"><strong>Nội dung:</strong> <span class="text-danger">LOTSO<?= $order_id ?></span></p>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
Vui lòng chuyển khoản chính xác số tiền và nội dung chuyển khoản để đơn hàng được xử lý nhanh nhất
                        </div>

                        <div class="d-grid gap-2">
                            <a href="confirm-payment.php?order_id=<?= $order_id ?>" class="btn btn-success">
                                <i class="fas fa-check-circle me-2"></i>Xác nhận đã thanh toán
                            </a>
                            <a href="profile.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Xem đơn hàng của tôi
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Về trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
?>
