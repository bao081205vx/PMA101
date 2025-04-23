<?php
include_once '../templates/header.php';
include_once '../functions/payment_functions.php';

$provider = $_GET['provider'] ?? '';
$status = $_GET['status'] ?? '';
$transactionId = $_GET['orderId'] ?? $_GET['app_trans_id'] ?? '';

$payment = null;
if ($transactionId) {
    $payment = getPaymentTransaction($transactionId);
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($payment && $payment['status'] === 'completed'): ?>
                        <h2 class="text-success mb-4">
                            <i class="fas fa-check-circle"></i>
                            Thanh toán thành công
                        </h2>
                        <p>Cảm ơn bạn đã thanh toán. Đơn hàng của bạn đang được xử lý.</p>
                        <p>Mã giao dịch: <?php echo htmlspecialchars($transactionId); ?></p>
                        <p>Số tiền: <?php echo number_format($payment['amount']); ?> VNĐ</p>
                    <?php else: ?>
                        <h2 class="text-danger mb-4">
                            <i class="fas fa-times-circle"></i>
                            Thanh toán thất bại
                        </h2>
                        <p>Có lỗi xảy ra trong quá trình thanh toán. Vui lòng thử lại sau.</p>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="/lotso/public/orders.php" class="btn btn-primary">
                            Xem đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>
