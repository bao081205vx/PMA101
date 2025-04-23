<?php
require_once 'header.php';
require_once '../functions/payment_functions.php';

$error = '';
$success = '';
$order_id = 0;

if (isset($_GET['partnerCode']) && isset($_GET['orderId'])) { // MoMo callback
    $order_id = (int)substr($_GET['orderId'], 4); // Bỏ tiền tố "MOMO"
    
    if ($_GET['resultCode'] == 0) {
        // Xác thực chữ ký
        $rawHash = "partnerCode=" . $_GET['partnerCode'] .
                  "&orderId=" . $_GET['orderId'] .
                  "&requestId=" . $_GET['requestId'] .
                  "&amount=" . $_GET['amount'] .
                  "&orderInfo=" . $_GET['orderInfo'] .
                  "&orderType=" . $_GET['orderType'] .
                  "&transId=" . $_GET['transId'] .
                  "&resultCode=" . $_GET['resultCode'] .
                  "&message=" . $_GET['message'] .
                  "&payType=" . $_GET['payType'] .
                  "&responseTime=" . $_GET['responseTime'] .
                  "&extraData=" . $_GET['extraData'];
        
        $signature = hash_hmac('sha256', $rawHash, MOMO_SECRET_KEY);
        
        if ($signature === $_GET['signature']) {
            // Cập nhật trạng thái đơn hàng
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                $success = 'Thanh toán thành công!';
            } else {
                $error = 'Không thể cập nhật trạng thái đơn hàng';
            }
        } else {
            $error = 'Chữ ký không hợp lệ';
        }
    } else {
        $error = 'Thanh toán thất bại: ' . $_GET['message'];
    }
} elseif (isset($_GET['data']) && isset($_GET['mac'])) { // ZaloPay callback
    $data = $_GET['data'];
    $mac = $_GET['mac'];
    
    // Xác thực chữ ký
    $mac2 = hash_hmac('sha256', $data, ZALOPAY_KEY2);
    if ($mac === $mac2) {
        $data = json_decode($data, true);
        $order_id = (int)substr($data['app_trans_id'], 4); // Bỏ tiền tố "ZALO"
        
        if ($data['status'] == 1) {
            // Cập nhật trạng thái đơn hàng
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                $success = 'Thanh toán thành công!';
            } else {
                $error = 'Không thể cập nhật trạng thái đơn hàng';
            }
        } else {
            $error = 'Thanh toán thất bại';
        }
    } else {
        $error = 'Chữ ký không hợp lệ';
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <?php if ($error): ?>
                <div class="mb-4">
                    <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
                    <h2 class="mt-3 text-danger">Thanh toán thất bại!</h2>
                    <p class="text-muted"><?= $error ?></p>
                </div>
                <a href="thank-you.php?order_id=<?= $order_id ?>" class="btn btn-primary">
                    Quay lại đơn hàng
                </a>
            <?php else: ?>
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    <h2 class="mt-3 text-success">Thanh toán thành công!</h2>
                    <p class="text-muted">Cảm ơn bạn đã mua hàng.</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-primary me-2">
                        <i class="fas fa-home"></i> Về trang chủ
                    </a>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-user"></i> Xem đơn hàng
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
