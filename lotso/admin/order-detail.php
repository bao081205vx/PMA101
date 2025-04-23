<?php
require_once 'header.php';
require_once 'sidebar.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image, p.slug 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy lịch sử đơn hàng
$stmt = $conn->prepare("
    SELECT * FROM order_history 
    WHERE order_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status_texts = [
    'pending' => 'Chờ thanh toán',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Đã giao',
    'cancelled' => 'Đã hủy'
];

$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'shipping' => 'primary',
    'completed' => 'success',
    'cancelled' => 'danger'
];

$payment_methods = [
    'cod' => 'Thanh toán khi nhận hàng',
    'bank' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo',
    'zalopay' => 'Ví ZaloPay'
];
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <?php require_once 'topbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Chi tiết đơn hàng #<?= $order_id ?></h1>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <div class="row">
                <!-- Order Status -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Trạng thái đơn hàng</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge badge-<?= $status_colors[$order['status']] ?> badge-lg">
                                    <?= $status_texts[$order['status']] ?>
                                </span>
                            </div>

                            <!-- Order Timeline -->
                            <div class="timeline">
                                <?php foreach ($history as $event): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <div class="text-muted small">
                                                <?= date('d/m/Y H:i', strtotime($event['created_at'])) ?>
                                            </div>
                                            <div class="font-weight-bold"><?= $event['status_text'] ?></div>
                                            <?php if ($event['note']): ?>
                                                <div class="text-muted"><?= $event['note'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Thông tin khách hàng</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Tài khoản:</strong>
                                <?= $order['username'] ? htmlspecialchars($order['username']) : 'Khách vãng lai' ?>
                            </div>
                            <div class="mb-2">
                                <strong>Họ tên:</strong> <?= htmlspecialchars($order['shipping_name']) ?>
                            </div>
                            <div class="mb-2">
                                <strong>Số điện thoại:</strong> <?= htmlspecialchars($order['shipping_phone']) ?>
                            </div>
                            <div class="mb-2">
                                <strong>Email:</strong> <?= htmlspecialchars($order['email']) ?>
                            </div>
                            <div>
                                <strong>Địa chỉ:</strong> <?= htmlspecialchars($order['shipping_address']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Thông tin thanh toán</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Phương thức:</strong>
                                <?= $payment_methods[$order['payment_method']] ?>
                            </div>
                            <div class="mb-2">
                                <strong>Trạng thái:</strong>
                                <?php if ($order['payment_status'] == 'completed'): ?>
                                    <span class="badge badge-success">Đã thanh toán</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chưa thanh toán</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($order['payment_method'] != 'cod'): ?>
                                <div>
                                    <strong>Mã giao dịch:</strong>
                                    <?= $order['transaction_id'] ?: 'Chưa có' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Chi tiết sản phẩm</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Giá</th>
                                            <th>Số lượng</th>
                                            <th class="text-right">Tổng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= $item['image'] ?>" 
                                                             alt="<?= $item['product_name'] ?>"
                                                             class="mr-3" width="50" height="50">
                                                        <div>
                                                            <div><?= htmlspecialchars($item['product_name']) ?></div>
                                                            <small class="text-muted">SKU: <?= $item['product_id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= number_format($item['price']) ?>đ</td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td class="text-right">
                                                    <?= number_format($item['price'] * $item['quantity']) ?>đ
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Tạm tính:</strong></td>
                                            <td class="text-right"><?= number_format($order['total_price']) ?>đ</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Phí vận chuyển:</strong></td>
                                            <td class="text-right">Miễn phí</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Tổng cộng:</strong></td>
                                            <td class="text-right">
                                                <strong class="text-primary">
                                                    <?= number_format($order['total_price']) ?>đ
                                                </strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status -->
                    <?php if ($order['status'] != 'cancelled' && $order['status'] != 'completed'): ?>
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Cập nhật trạng thái</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="orders.php">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <div class="form-group">
                                        <label>Trạng thái mới</label>
                                        <select class="form-control" name="new_status" required>
                                            <?php foreach ($status_texts as $key => $text): ?>
                                                <option value="<?= $key ?>" 
                                                        <?= $order['status'] == $key ? 'selected' : '' ?>>
                                                    <?= $text ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Ghi chú</label>
                                        <textarea class="form-control" name="note" rows="3"></textarea>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        Cập nhật trạng thái
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 30px;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #4e73df;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #4e73df;
}

.timeline-item:not(:last-child):after {
    content: '';
    position: absolute;
    left: 5px;
    top: 12px;
    bottom: -20px;
    width: 2px;
    background: #4e73df;
}

.timeline-content {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 4px;
}

.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}
</style>

<?php require_once 'footer.php'; ?>
