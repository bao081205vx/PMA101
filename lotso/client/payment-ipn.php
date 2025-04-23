<?php
require_once '../config/Database.php';
require_once '../functions/payment_functions.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Xử lý IPN từ MoMo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = file_get_contents('php://input');
    $data = json_decode($inputData, true);
    
    if ($data && isset($data['partnerCode'])) {
        // Xác thực chữ ký
        $rawHash = "partnerCode=" . $data['partnerCode'] .
                  "&orderId=" . $data['orderId'] .
                  "&requestId=" . $data['requestId'] .
                  "&amount=" . $data['amount'] .
                  "&orderInfo=" . $data['orderInfo'] .
                  "&orderType=" . $data['orderType'] .
                  "&transId=" . $data['transId'] .
                  "&resultCode=" . $data['resultCode'] .
                  "&message=" . $data['message'] .
                  "&payType=" . $data['payType'] .
                  "&responseTime=" . $data['responseTime'] .
                  "&extraData=" . $data['extraData'];
        
        $signature = hash_hmac('sha256', $rawHash, MOMO_SECRET_KEY);
        
        if ($signature === $data['signature'] && $data['resultCode'] == 0) {
            $order_id = (int)substr($data['orderId'], 4); // Bỏ tiền tố "MOMO"
            
            // Cập nhật trạng thái đơn hàng
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            // Trả về success cho MoMo
            http_response_code(204);
            exit;
        }
    }
}

// Xử lý IPN từ ZaloPay
if (isset($_GET['data']) && isset($_GET['mac'])) {
    $data = $_GET['data'];
    $mac = $_GET['mac'];
    
    // Xác thực chữ ký
    $mac2 = hash_hmac('sha256', $data, ZALOPAY_KEY2);
    if ($mac === $mac2) {
        $data = json_decode($data, true);
        if ($data['status'] == 1) {
            $order_id = (int)substr($data['app_trans_id'], 4); // Bỏ tiền tố "ZALO"
            
            // Cập nhật trạng thái đơn hàng
            $stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            // Trả về success cho ZaloPay
            echo json_encode(['return_code' => 1, 'return_message' => 'success']);
            exit;
        }
    }
}

// Nếu có lỗi
http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>
