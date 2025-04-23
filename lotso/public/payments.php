<?php
include_once '../templates/header.php';
include_once '../functions/payment_functions.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Lấy tham số lọc
$provider = $_GET['provider'] ?? '';
$status = $_GET['status'] ?? '';
$order_id = $_GET['order_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Lấy danh sách giao dịch
$payments = getPayments($provider, $status, $order_id, $start_date, $end_date);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Quản lý thanh toán</h2>
    </div>

    <!-- Form lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Phương thức</label>
                    <select class="form-select" name="provider">
                        <option value="">Tất cả</option>
                        <option value="momo" <?php echo $provider == 'momo' ? 'selected' : ''; ?>>MoMo</option>
                        <option value="zalopay" <?php echo $provider == 'zalopay' ? 'selected' : ''; ?>>ZaloPay</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Đã thanh toán</option>
                        <option value="failed" <?php echo $status == 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                        <option value="refunded" <?php echo $status == 'refunded' ? 'selected' : ''; ?>>Đã hoàn tiền</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mã đơn hàng</label>
                    <input type="text" class="form-control" name="order_id" value="<?php echo $order_id; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng giao dịch -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Mã GD</th>
                            <th>Mã đơn</th>
                            <th>Phương thức</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Hoàn tiền</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo $payment['transaction_id']; ?></td>
                            <td>
                                <a href="orders.php?id=<?php echo $payment['order_id']; ?>">
                                    #<?php echo $payment['order_id']; ?>
                                </a>
                            </td>
                            <td><?php echo strtoupper($payment['provider']); ?></td>
                            <td><?php echo number_format($payment['amount']); ?> VNĐ</td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $payment['status'] == 'completed' ? 'success' : 
                                        ($payment['status'] == 'failed' ? 'danger' : 
                                        ($payment['status'] == 'refunded' ? 'info' : 'warning')); 
                                ?>">
                                    <?php 
                                        echo $payment['status'] == 'completed' ? 'Đã thanh toán' : 
                                            ($payment['status'] == 'failed' ? 'Thất bại' : 
                                            ($payment['status'] == 'refunded' ? 'Đã hoàn tiền' : 'Chờ thanh toán')); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($payment['refund_amount']): ?>
                                    <?php echo number_format($payment['refund_amount']); ?> VNĐ
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $payment['refund_reason']; ?>
                                    </small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#paymentDetailModal"
                                            data-payment='<?php echo json_encode($payment); ?>'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($payment['status'] == 'completed' && !$payment['refund_amount']): ?>
                                    <button type="button" class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#refundModal"
                                            data-payment='<?php echo json_encode($payment); ?>'>
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <?php endif; ?>
                                    <a href="invoice.php?id=<?php echo $payment['order_id']; ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal chi tiết giao dịch -->
<div class="modal fade" id="paymentDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết giao dịch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Mã giao dịch:</strong> <span id="modalTransactionId"></span></p>
                        <p><strong>Mã đơn hàng:</strong> <span id="modalOrderId"></span></p>
                        <p><strong>Phương thức:</strong> <span id="modalProvider"></span></p>
                        <p><strong>Số tiền:</strong> <span id="modalAmount"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Trạng thái:</strong> <span id="modalStatus"></span></p>
                        <p><strong>Hoàn tiền:</strong> <span id="modalRefund"></span></p>
                        <p><strong>Lý do hoàn:</strong> <span id="modalRefundReason"></span></p>
                        <p><strong>Ngày tạo:</strong> <span id="modalCreatedAt"></span></p>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Thông tin thanh toán</h6>
                    <pre id="modalPaymentInfo" class="bg-light p-3 rounded"></pre>
                </div>
                <?php if (isset($payment['refund_info'])): ?>
                <div class="mt-3">
                    <h6>Thông tin hoàn tiền</h6>
                    <pre id="modalRefundInfo" class="bg-light p-3 rounded"></pre>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal hoàn tiền -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hoàn tiền</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="refundForm" method="POST" action="../api/refund.php">
                <div class="modal-body">
                    <input type="hidden" name="payment_id" id="refundPaymentId">
                    <div class="mb-3">
                        <label class="form-label">Số tiền hoàn</label>
                        <input type="number" class="form-control" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý do hoàn tiền</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Hoàn tiền</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý modal chi tiết
document.querySelectorAll('[data-bs-target="#paymentDetailModal"]').forEach(button => {
    button.addEventListener('click', function() {
        const payment = JSON.parse(this.dataset.payment);
        document.getElementById('modalTransactionId').textContent = payment.transaction_id;
        document.getElementById('modalOrderId').textContent = '#' + payment.order_id;
        document.getElementById('modalProvider').textContent = payment.provider.toUpperCase();
        document.getElementById('modalAmount').textContent = new Intl.NumberFormat('vi-VN').format(payment.amount) + ' VNĐ';
        document.getElementById('modalStatus').textContent = payment.status == 'completed' ? 'Đã thanh toán' : 
            (payment.status == 'failed' ? 'Thất bại' : 
            (payment.status == 'refunded' ? 'Đã hoàn tiền' : 'Chờ thanh toán'));
        document.getElementById('modalRefund').textContent = payment.refund_amount ? 
            new Intl.NumberFormat('vi-VN').format(payment.refund_amount) + ' VNĐ' : '-';
        document.getElementById('modalRefundReason').textContent = payment.refund_reason || '-';
        document.getElementById('modalCreatedAt').textContent = new Date(payment.created_at).toLocaleString('vi-VN');
        document.getElementById('modalPaymentInfo').textContent = JSON.stringify(payment.payment_info, null, 2);
        if (payment.refund_info) {
            document.getElementById('modalRefundInfo').textContent = JSON.stringify(payment.refund_info, null, 2);
        }
    });
});

// Xử lý modal hoàn tiền
document.querySelectorAll('[data-bs-target="#refundModal"]').forEach(button => {
    button.addEventListener('click', function() {
        const payment = JSON.parse(this.dataset.payment);
        document.getElementById('refundPaymentId').value = payment.id;
        document.querySelector('[name="amount"]').max = payment.amount;
        document.querySelector('[name="amount"]').value = payment.amount;
    });
});
</script>

<?php include_once '../templates/footer.php'; ?>
