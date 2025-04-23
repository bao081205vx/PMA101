<?php
header('Content-Type: application/json');
include_once '../functions/payment_functions.php';
include_once '../functions/order_functions.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        // Nhận thông tin từ request
        $data = json_decode(file_get_contents('php://input'), true);
        $orderId = $data['order_id'] ?? null;
        $provider = $data['provider'] ?? null;
        
        if (!$orderId || !$provider) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin thanh toán']);
            exit;
        }
        
        // Lấy thông tin đơn hàng
        $order = getOrderById($orderId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng']);
            exit;
        }
        
        $amount = $order['total_price'];
        $orderInfo = "Thanh toan don hang #" . $orderId;
        
        // Tạo thanh toán theo provider
        try {
            $result = null;
            if ($provider === 'momo') {
                $result = createMomoPayment($orderId, $amount, $orderInfo);
            } else if ($provider === 'zalopay') {
                $result = createZaloPayment($orderId, $amount, $orderInfo);
            } else {
                throw new Exception('Phương thức thanh toán không hợp lệ');
            }
            
            if ($result && isset($result['payUrl'])) {
                echo json_encode([
                    'status' => 'success',
                    'payment_url' => $result['payUrl']
                ]);
            } else {
                throw new Exception('Không thể tạo thanh toán');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        break;
        
    case 'callback':
        // Xử lý callback từ cổng thanh toán
        $provider = $_GET['provider'] ?? '';
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        if ($provider === 'momo') {
            // Xử lý callback từ MoMo
            $transactionId = $data['orderId'] ?? '';
            $resultCode = $data['resultCode'] ?? '';
            
            if ($resultCode === '0') { // Thanh toán thành công
                updatePaymentStatus($transactionId, 'completed', $data);
                // Cập nhật trạng thái đơn hàng
                $payment = getPaymentTransaction($transactionId);
                if ($payment) {
                    updateOrderStatus($payment['order_id'], 'paid');
                }
            } else {
                updatePaymentStatus($transactionId, 'failed', $data);
            }
            
            echo json_encode(['status' => 'success']);
        } 
        else if ($provider === 'zalopay') {
            // Xử lý callback từ ZaloPay
            $transactionId = $data['app_trans_id'] ?? '';
            $status = $data['status'] ?? '';
            
            if ($status === '1') { // Thanh toán thành công
                updatePaymentStatus($transactionId, 'completed', $data);
                // Cập nhật trạng thái đơn hàng
                $payment = getPaymentTransaction($transactionId);
                if ($payment) {
                    updateOrderStatus($payment['order_id'], 'paid');
                }
            } else {
                updatePaymentStatus($transactionId, 'failed', $data);
            }
            
            echo json_encode(['status' => 'success']);
        }
        break;

    case 'refund':
        // Kiểm tra quyền admin
        session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Không có quyền truy cập']);
            exit;
        }
        
        $transactionId = $_POST['transaction_id'] ?? null;
        $amount = $_POST['amount'] ?? null;
        $reason = $_POST['reason'] ?? '';
        
        if (!$transactionId || !$amount) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin hoàn tiền']);
            exit;
        }
        
        $result = processRefund($transactionId, $amount, $reason);
        echo json_encode($result);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ']);
}
?>
