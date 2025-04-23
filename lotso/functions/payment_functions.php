<?php
include_once '../config/db.php';
include_once 'response.php';

// Hàm tạo mã đơn hàng ngẫu nhiên
function generateOrderCode($prefix = '') {
    return $prefix . time() . rand(1000, 9999);
}

// Hàm lấy danh sách giao dịch thanh toán
function getPaymentTransactions($page = 1, $limit = 10) {
    global $pdo;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT pt.*, o.total_price as order_total, u.username 
        FROM payment_transactions pt
        JOIN orders o ON pt.order_id = o.id
        JOIN users u ON o.user_id = u.id
        ORDER BY pt.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    
    // Lấy tổng số giao dịch
    $total = $pdo->query("SELECT COUNT(*) FROM payment_transactions")->fetchColumn();
    
    return [
        'transactions' => $stmt->fetchAll(),
        'total' => $total,
        'pages' => ceil($total / $limit)
    ];
}

// Hàm hoàn tiền MoMo
function refundMomoPayment($transactionId, $amount, $reason) {
    $endpoint = "https://test-payment.momo.vn/v2/gateway/api/refund";
    $partnerCode = "MOMOXXX20220626";
    $accessKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";
    $secretKey = "ppuDXq1KowPT1ftR8DvlQTHhC03aul17";
    
    $requestId = generateOrderCode('RF');
    
    $rawHash = "accessKey=" . $accessKey .
        "&amount=" . $amount .
        "&description=" . $reason .
        "&orderId=" . $requestId .
        "&partnerCode=" . $partnerCode .
        "&requestId=" . $requestId .
        "&transId=" . $transactionId;
    
    $signature = hash_hmac('sha256', $rawHash, $secretKey);
    
    $data = [
        'partnerCode' => $partnerCode,
        'orderId' => $requestId,
        'requestId' => $requestId,
        'amount' => $amount,
        'transId' => $transactionId,
        'description' => $reason,
        'signature' => $signature
    ];
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

// Hàm hoàn tiền ZaloPay
function refundZaloPayment($transactionId, $amount, $reason) {
    $endpoint = "https://sandbox.zalopay.com.vn/v2/refund";
    $appId = "2553";
    $key1 = "PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL";
    $key2 = "kLtgPl8HHhfvMuDHPwKfgfsY4Ydm9eIz";
    
    $refundId = generateOrderCode('RF');
    $timestamp = time();
    
    $data = [
        'app_id' => $appId,
        'zp_trans_id' => $transactionId,
        'm_refund_id' => $refundId,
        'timestamp' => $timestamp,
        'amount' => $amount,
        'description' => $reason
    ];
    
    $data['mac'] = hash_hmac('sha256', 
        $data['app_id']."|".$data['zp_trans_id']."|".$data['m_refund_id']."|".
        $data['amount']."|".$data['timestamp']."|".$data['description'],
        $key1
    );
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

// Hàm xử lý hoàn tiền
function processRefund($transactionId, $amount, $reason) {
    global $pdo;
    
    // Lấy thông tin giao dịch
    $transaction = getPaymentTransaction($transactionId);
    if (!$transaction) {
        return ['status' => 'error', 'message' => 'Không tìm thấy giao dịch'];
    }
    
    if ($transaction['status'] !== 'completed') {
        return ['status' => 'error', 'message' => 'Giao dịch không thể hoàn tiền'];
    }
    
    try {
        $result = null;
        if ($transaction['provider'] === 'momo') {
            $result = refundMomoPayment($transactionId, $amount, $reason);
        } else if ($transaction['provider'] === 'zalopay') {
            $result = refundZaloPayment($transactionId, $amount, $reason);
        }
        
        if ($result && ($result['resultCode'] === '0' || $result['return_code'] === '1')) {
            // Cập nhật trạng thái giao dịch
            $stmt = $pdo->prepare("
                UPDATE payment_transactions 
                SET status = 'refunded', 
                    refund_info = ?,
                    refund_amount = ?,
                    refund_reason = ?
                WHERE transaction_id = ?
            ");
            $stmt->execute([json_encode($result), $amount, $reason, $transactionId]);
            
            // Gửi email thông báo
            include_once 'stats_functions.php';
            sendRefundNotification($transaction['order_id'], $transactionId, $amount, $reason);
            
            return ['status' => 'success', 'message' => 'Hoàn tiền thành công'];
        }
        
        return ['status' => 'error', 'message' => 'Hoàn tiền thất bại'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Hàm tạo hóa đơn PDF
function generateInvoicePdf($orderId) {
    require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Lấy thông tin đơn hàng
    $order = getOrderById($orderId);
    if (!$order) {
        return false;
    }
    
    // Lấy thông tin thanh toán
    $stmt = $pdo->prepare("
        SELECT * FROM payment_transactions 
        WHERE order_id = ? AND status = 'completed'
        LIMIT 1
    ");
    $stmt->execute([$orderId]);
    $payment = $stmt->fetch();
    
    // Tạo PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Lotso Shop');
    $pdf->SetAuthor('Lotso Shop');
    $pdf->SetTitle('Hóa đơn #' . $orderId);
    
    // Thiết lập font
    $pdf->SetFont('dejavusans', '', 10);
    
    // Thêm trang
    $pdf->AddPage();
    
    // Header
    $pdf->Cell(0, 10, 'HÓA ĐƠN BÁN HÀNG', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Mã đơn hàng: ' . $orderId, 0, 1, 'C');
    
    // Thông tin khách hàng
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Thông tin khách hàng:', 0, 1);
    $pdf->Cell(0, 10, 'Tên: ' . $order['username'], 0, 1);
    $pdf->Cell(0, 10, 'Ngày tạo: ' . date('d/m/Y H:i', strtotime($order['created_at'])), 0, 1);
    
    // Chi tiết sản phẩm
    $pdf->Ln(10);
    $pdf->Cell(90, 10, 'Sản phẩm', 1);
    $pdf->Cell(30, 10, 'Số lượng', 1);
    $pdf->Cell(70, 10, 'Thành tiền', 1);
    $pdf->Ln();
    
    foreach ($order['items'] as $item) {
        $pdf->Cell(90, 10, $item['name'], 1);
        $pdf->Cell(30, 10, $item['quantity'], 1);
        $pdf->Cell(70, 10, number_format($item['price']) . ' VNĐ', 1);
        $pdf->Ln();
    }
    
    // Tổng tiền
    $pdf->Ln(10);
    $pdf->Cell(120, 10, 'Tổng tiền:', 0);
    $pdf->Cell(70, 10, number_format($order['total_price']) . ' VNĐ', 0);
    
    // Thông tin thanh toán
    if ($payment) {
        $pdf->Ln(10);
        $pdf->Cell(0, 10, 'Thông tin thanh toán:', 0, 1);
        $pdf->Cell(0, 10, 'Phương thức: ' . strtoupper($payment['provider']), 0, 1);
        $pdf->Cell(0, 10, 'Mã giao dịch: ' . $payment['transaction_id'], 0, 1);
        $pdf->Cell(0, 10, 'Trạng thái: Đã thanh toán', 0, 1);
    }
    
    // Tạo tên file
    $filename = 'invoice_' . $orderId . '.pdf';
    
    // Lưu file
    $pdf->Output(dirname(__DIR__) . '/public/invoices/' . $filename, 'F');
    
    return $filename;
}

// Hàm khởi tạo thanh toán MoMo
function createMomoPayment($orderId, $amount, $orderInfo) {
    $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
    $partnerCode = "MOMOXXX20220626";
    $accessKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";
    $secretKey = "ppuDXq1KowPT1ftR8DvlQTHhC03aul17";
    
    $orderCode = generateOrderCode('MM');
    $redirectUrl = "http://localhost/lotso/lotso/client/payment-callback.php";
    $ipnUrl = "http://localhost/lotso/lotso/client/payment-ipn.php";
    $requestType = "captureWallet";
    
    $rawHash = "accessKey=" . $accessKey .
        "&amount=" . $amount .
        "&extraData=" .
        "&ipnUrl=" . $ipnUrl .
        "&orderId=" . $orderCode .
        "&orderInfo=" . $orderInfo .
        "&partnerCode=" . $partnerCode .
        "&redirectUrl=" . $redirectUrl .
        "&requestId=" . $orderCode .
        "&requestType=" . $requestType;
    
    $signature = hash_hmac('sha256', $rawHash, $secretKey);
    
    $data = [
        'partnerCode' => $partnerCode,
        'partnerName' => "Test",
        'requestId' => $orderCode,
        'amount' => $amount,
        'orderId' => $orderCode,
        'orderInfo' => $orderInfo,
        'redirectUrl' => $redirectUrl,
        'ipnUrl' => $ipnUrl,
        'requestType' => $requestType,
        'extraData' => '',
        'signature' => $signature,
        'lang' => 'vi'
    ];
    
    // Lưu thông tin thanh toán vào database
    savePaymentRequest($orderId, 'momo', $orderCode, $amount);
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if ($httpCode == 200 && isset($response['payUrl'])) {
        return $response['payUrl'];
    }
    return false;
}

// Hàm khởi tạo thanh toán ZaloPay
function createZaloPayment($orderId, $amount, $orderInfo) {
    $endpoint = "https://sandbox.zalopay.com.vn/v2/create";
    $appId = "2553";
    $key1 = "PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL";
    $key2 = "kLtgPl8HHhfvMuDHPwKfgfsY4Ydm9eIz";
    
    $orderCode = generateOrderCode('ZLP');
    $embedData = json_encode([
        'redirecturl' => 'http://localhost/lotso/lotso/client/payment-callback.php'
    ]);
    
    $items = [[
        "id" => $orderId,
        "name" => $orderInfo,
        "price" => $amount,
        "quantity" => 1
    ]];
    
    $data = [
        'app_id' => $appId,
        'app_trans_id' => $orderCode,
        'app_user' => 'user123',
        'app_time' => time(),
        'amount' => $amount,
        'item' => json_encode($items),
        'embed_data' => $embedData,
        'callback_url' => 'http://localhost/lotso/lotso/client/payment-ipn.php',
        'description' => $orderInfo,
        'bank_code' => ''
    ];
    
    $data['mac'] = hash_hmac('sha256', 
        $data['app_id']."|".$data['app_trans_id']."|".$data['app_user']."|".$data['amount']."|".
        $data['app_time']."|".$data['embed_data']."|".$data['item'],
        $key1
    );
    
    // Lưu thông tin thanh toán vào database
    savePaymentRequest($orderId, 'zalopay', $orderCode, $amount);
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $response = json_decode($result, true);
    
    if ($httpCode == 200 && isset($response['order_url'])) {
        return $response['order_url'];
    }
    return false;
}

// Hàm lưu thông tin yêu cầu thanh toán
function savePaymentRequest($orderId, $provider, $transactionId, $amount) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO payment_transactions (order_id, provider, transaction_id, amount, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    return $stmt->execute([$orderId, $provider, $transactionId, $amount]);
}

// Hàm cập nhật trạng thái thanh toán
function updatePaymentStatus($transactionId, $status, $paymentInfo = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE payment_transactions 
        SET status = ?, payment_info = ?, updated_at = NOW() 
        WHERE transaction_id = ?
    ");
    return $stmt->execute([$status, $paymentInfo ? json_encode($paymentInfo) : null, $transactionId]);
}

// Hàm lấy thông tin thanh toán
function getPaymentTransaction($transactionId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM payment_transactions 
        WHERE transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    return $stmt->fetch();
}
?>
