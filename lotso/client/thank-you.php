<?php
require_once 'header.php';

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['order_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
        <h1 class="mt-3">Cảm ơn bạn đã đặt hàng!</h1>
        <p class="lead">Đơn hàng của bạn đã được tiếp nhận và đang được xử lý.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Chi tiết đơn hàng #<?= $order_id ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Thông tin đơn hàng:</h6>
                            <p class="mb-1">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            <p class="mb-1">Trạng thái: 
                                <span class="badge bg-warning">Chờ xử lý</span>
                            </p>
                            <p class="mb-0">Phương thức thanh toán: 
                                <?= $order['payment_method'] === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng' ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Thông tin giao hàng:</h6>
                            <p class="mb-1">Họ tên: <?= htmlspecialchars($order['shipping_name']) ?></p>
                            <p class="mb-1">Số điện thoại: <?= htmlspecialchars($order['shipping_phone']) ?></p>
                            <p class="mb-0">Địa chỉ: <?= htmlspecialchars($order['shipping_address']) ?></p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Giá</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td class="text-end"><?= number_format($item['price']) ?> đ</td>
                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                    <td class="text-end"><?= number_format($item['price'] * $item['quantity']) ?> đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tạm tính:</strong></td>
                                    <td class="text-end"><?= number_format($order['total_price']) ?> đ</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Phí vận chuyển:</strong></td>
                                    <td class="text-end">Miễn phí</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?= number_format($order['total_price']) ?> đ</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($order['payment_method'] === 'bank'): ?>
                    <div class="alert alert-info mt-3">
                        <h6>Thông tin chuyển khoản:</h6>
                        <p class="mb-1">Ngân hàng: BIDV</p>
                        <p class="mb-1">Số tài khoản: 123456789</p>
                        <p class="mb-1">Chủ tài khoản: CÔNG TY LOTSO</p>
                        <p class="mb-1">Số tiền: <?= number_format($order['total_price']) ?> đ</p>
                        <p class="mb-0">Nội dung: Thanh toan don hang #<?= $order_id ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> Xem đơn hàng của tôi
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
