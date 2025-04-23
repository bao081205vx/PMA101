<?php
session_start();
require_once '../config/Database.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'payment_errors.log');

// Kiểm tra thông tin thanh toán
if (!isset($_SESSION['payment_order_id']) || !isset($_SESSION['payment_method'])) {
    header('Location: checkout.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Lấy thông tin đơn hàng
$order_id = $_SESSION['payment_order_id'];
$payment_method = $_SESSION['payment_method'];

$stmt = $conn->prepare("SELECT total_price FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['checkout_error'] = "Không tìm thấy đơn hàng";
    header('Location: checkout.php');
    exit;
}

try {
    switch ($payment_method) {
        case 'bank':
            // Kiểm tra đơn hàng đã thanh toán chưa
            $stmt = $conn->prepare("SELECT status FROM payment_transactions WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $transaction = $result->fetch_assoc();
                if ($transaction['status'] == 'completed') {
                    $_SESSION['checkout_error'] = "Đơn hàng này đã được thanh toán";
                    header('Location: order-detail.php?id=' . $order_id);
                    exit;
                }
            }

            // Hiển thị thông tin chuyển khoản
            $bank_info = [
                'name' => 'NGUYEN VAN A',
                'number' => '1234567890',
                'bank' => 'VIETCOMBANK',
                'branch' => 'Chi nhánh HCM',
                'content' => "LOTSO{$order_id}"
            ];
            
            // Tạo giao dịch trong database với transaction
            $conn->begin_transaction();
            try {
                // Tạo payment transaction
                $stmt = $conn->prepare("INSERT INTO payment_transactions (order_id, provider, transaction_id, amount, status) VALUES (?, 'bank', ?, ?, 'pending')");
                $transaction_id = 'BANK' . time() . rand(1000, 9999);
                $stmt->bind_param("isd", $order_id, $transaction_id, $order['total_price']);
                $stmt->execute();

                // Cập nhật trạng thái đơn hàng
                $stmt = $conn->prepare("UPDATE orders SET payment_status = 'pending' WHERE id = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            
            // Hiển thị trang thông tin chuyển khoản
            require_once 'header.php';
            ?>
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body text-center">
                                <h4 class="mb-4">Thông tin chuyển khoản</h4>
                                
                                <div class="mb-4">
                                    <p class="mb-1">Số tiền cần chuyển:</p>
                                    <h3 class="text-primary"><?= number_format($order['total_price']) ?> VNĐ</h3>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Tên tài khoản</th>
                                            <td><?= $bank_info['name'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số tài khoản</th>
                                            <td class="fw-bold"><?= $bank_info['number'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Ngân hàng</th>
                                            <td><?= $bank_info['bank'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Chi nhánh</th>
                                            <td><?= $bank_info['branch'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Nội dung chuyển khoản</th>
                                            <td class="fw-bold text-danger"><?= $bank_info['content'] ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="alert alert-warning mt-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Lưu ý quan trọng:</strong><br>
                                    - Vui lòng chuyển khoản chính xác số tiền và nội dung bên trên<br>
                                    - Đơn hàng sẽ được xử lý trong vòng 24h sau khi chúng tôi nhận được tiền<br>
                                    - Nếu cần hỗ trợ, vui lòng liên hệ hotline: <strong>1900 xxxx</strong>
                                </div>

                                <div class="mt-4">
                                    <a href="order-detail.php?id=<?= $order_id ?>" class="btn btn-primary me-2">
                                        <i class="fas fa-eye me-2"></i>Xem đơn hàng
                                    </a>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-home me-2"></i>Về trang chủ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            require_once 'footer.php';
            break;

        case 'momo':
            // TODO: Tích hợp MoMo
            $_SESSION['checkout_error'] = "Phương thức thanh toán MoMo đang được phát triển";
            header('Location: checkout.php');
            break;

        case 'zalopay':
            // TODO: Tích hợp ZaloPay
            $_SESSION['checkout_error'] = "Phương thức thanh toán ZaloPay đang được phát triển";
            header('Location: checkout.php');
            break;

        default:
            $_SESSION['checkout_error'] = "Phương thức thanh toán không hợp lệ";
            header('Location: checkout.php');
            break;
    }
} catch (Exception $e) {
    error_log('Payment Error: ' . $e->getMessage());
    $_SESSION['checkout_error'] = "Có lỗi xảy ra, vui lòng thử lại";
    header('Location: checkout.php');
}

// Xóa thông tin thanh toán session
unset($_SESSION['payment_order_id']);
unset($_SESSION['payment_method']);
