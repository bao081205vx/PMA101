<?php
include_once '../config/db.php';

// Hàm lấy thống kê doanh thu theo phương thức thanh toán
function getPaymentStats($startDate = null, $endDate = null) {
    global $pdo;
    
    $where = "WHERE pt.status = 'completed'";
    $params = [];
    
    if ($startDate && $endDate) {
        $where .= " AND DATE(pt.created_at) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
    }
    
    // Thống kê theo phương thức
    $stmt = $pdo->prepare("
        SELECT 
            pt.provider,
            COUNT(*) as total_transactions,
            SUM(pt.amount) as total_amount,
            COUNT(CASE WHEN pt.status = 'refunded' THEN 1 END) as refunded_count,
            SUM(CASE WHEN pt.status = 'refunded' THEN pt.refund_amount ELSE 0 END) as refunded_amount
        FROM payment_transactions pt
        $where
        GROUP BY pt.provider
    ");
    $stmt->execute($params);
    $byProvider = $stmt->fetchAll();
    
    // Thống kê theo ngày
    $stmt = $pdo->prepare("
        SELECT 
            DATE(pt.created_at) as date,
            COUNT(*) as total_transactions,
            SUM(pt.amount) as total_amount
        FROM payment_transactions pt
        $where
        GROUP BY DATE(pt.created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->execute($params);
    $byDate = $stmt->fetchAll();
    
    // Thống kê theo tháng
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(pt.created_at, '%Y-%m') as month,
            COUNT(*) as total_transactions,
            SUM(pt.amount) as total_amount,
            SUM(CASE WHEN pt.status = 'refunded' THEN pt.refund_amount ELSE 0 END) as refunded_amount
        FROM payment_transactions pt
        $where
        GROUP BY DATE_FORMAT(pt.created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute($params);
    $byMonth = $stmt->fetchAll();
    
    // Tổng doanh thu
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(pt.amount) as total_amount,
            COUNT(CASE WHEN pt.status = 'refunded' THEN 1 END) as total_refunded,
            SUM(CASE WHEN pt.status = 'refunded' THEN pt.refund_amount ELSE 0 END) as total_refunded_amount
        FROM payment_transactions pt
        $where
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    return [
        'by_provider' => $byProvider,
        'by_date' => $byDate,
        'by_month' => $byMonth,
        'summary' => $summary
    ];
}

// Hàm xuất báo cáo Excel
function exportPaymentReport($startDate = null, $endDate = null) {
    require_once '../vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
    
    $stats = getPaymentStats($startDate, $endDate);
    
    // Tạo workbook mới
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    
    // Trang tổng quan
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Tổng quan');
    
    // Tiêu đề
    $sheet->setCellValue('A1', 'BÁO CÁO DOANH THU THANH TOÁN');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // Thời gian báo cáo
    $sheet->setCellValue('A2', 'Từ ngày: ' . $startDate);
    $sheet->setCellValue('D2', 'Đến ngày: ' . $endDate);
    
    // Tổng quan
    $sheet->setCellValue('A4', 'TỔNG QUAN');
    $sheet->getStyle('A4')->getFont()->setBold(true);
    
    $sheet->setCellValue('A5', 'Tổng doanh thu:');
    $sheet->setCellValue('B5', $stats['summary']['total_amount']);
    $sheet->setCellValue('A6', 'Tổng giao dịch:');
    $sheet->setCellValue('B6', $stats['summary']['total_transactions']);
    $sheet->setCellValue('A7', 'Tổng hoàn tiền:');
    $sheet->setCellValue('B7', $stats['summary']['total_refunded_amount']);
    
    // Thống kê theo phương thức
    $sheet->setCellValue('A9', 'THỐNG KÊ THEO PHƯƠNG THỨC THANH TOÁN');
    $sheet->getStyle('A9')->getFont()->setBold(true);
    
    $sheet->setCellValue('A10', 'Phương thức');
    $sheet->setCellValue('B10', 'Số giao dịch');
    $sheet->setCellValue('C10', 'Tổng tiền');
    $sheet->setCellValue('D10', 'Số hoàn tiền');
    $sheet->setCellValue('E10', 'Tổng hoàn');
    $sheet->setCellValue('F10', 'Doanh thu thực');
    
    $row = 11;
    foreach ($stats['by_provider'] as $provider) {
        $sheet->setCellValue('A'.$row, strtoupper($provider['provider']));
        $sheet->setCellValue('B'.$row, $provider['total_transactions']);
        $sheet->setCellValue('C'.$row, $provider['total_amount']);
        $sheet->setCellValue('D'.$row, $provider['refunded_count']);
        $sheet->setCellValue('E'.$row, $provider['refunded_amount']);
        $sheet->setCellValue('F'.$row, $provider['total_amount'] - $provider['refunded_amount']);
        $row++;
    }
    
    // Định dạng số tiền
    $sheet->getStyle('B5:B7')->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('C11:F'.$row)->getNumberFormat()->setFormatCode('#,##0');
    
    // Trang thống kê theo ngày
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Theo ngày');
    
    $sheet->setCellValue('A1', 'THỐNG KÊ THEO NGÀY');
    $sheet->mergeCells('A1:C1');
    $sheet->getStyle('A1')->getFont()->setBold(true);
    
    $sheet->setCellValue('A2', 'Ngày');
    $sheet->setCellValue('B2', 'Số giao dịch');
    $sheet->setCellValue('C2', 'Doanh thu');
    
    $row = 3;
    foreach ($stats['by_date'] as $date) {
        $sheet->setCellValue('A'.$row, $date['date']);
        $sheet->setCellValue('B'.$row, $date['total_transactions']);
        $sheet->setCellValue('C'.$row, $date['total_amount']);
        $row++;
    }
    
    $sheet->getStyle('C3:C'.$row)->getNumberFormat()->setFormatCode('#,##0');
    
    // Trang thống kê theo tháng
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Theo tháng');
    
    $sheet->setCellValue('A1', 'THỐNG KÊ THEO THÁNG');
    $sheet->mergeCells('A1:D1');
    $sheet->getStyle('A1')->getFont()->setBold(true);
    
    $sheet->setCellValue('A2', 'Tháng');
    $sheet->setCellValue('B2', 'Số giao dịch');
    $sheet->setCellValue('C2', 'Doanh thu');
    $sheet->setCellValue('D2', 'Hoàn tiền');
    
    $row = 3;
    foreach ($stats['by_month'] as $month) {
        $sheet->setCellValue('A'.$row, $month['month']);
        $sheet->setCellValue('B'.$row, $month['total_transactions']);
        $sheet->setCellValue('C'.$row, $month['total_amount']);
        $sheet->setCellValue('D'.$row, $month['refunded_amount']);
        $row++;
    }
    
    $sheet->getStyle('C3:D'.$row)->getNumberFormat()->setFormatCode('#,##0');
    
    // Auto-size columns
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    // Tạo writer
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    
    // Tạo tên file
    $filename = 'payment_report_' . date('Y-m-d_His') . '.xlsx';
    $filepath = dirname(__DIR__) . '/public/reports/' . $filename;
    
    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists(dirname($filepath))) {
        mkdir(dirname($filepath), 0777, true);
    }
    
    // Lưu file
    $writer->save($filepath);
    
    return $filename;
}

// Hàm gửi email thông báo
function sendEmail($to, $subject, $content) {
    require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Cấu hình server
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Thay bằng email thật
        $mail->Password = 'your-password';         // Thay bằng mật khẩu thật
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Người gửi và người nhận
        $mail->setFrom('your-email@gmail.com', 'Lotso Shop');
        $mail->addAddress($to);
        
        // Nội dung
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $content;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending email: " . $mail->ErrorInfo);
        return false;
    }
}

// Hàm gửi email thông báo hoàn tiền
function sendRefundNotification($orderId, $transactionId, $amount, $reason) {
    global $pdo;
    
    // Lấy thông tin đơn hàng và khách hàng
    $stmt = $pdo->prepare("
        SELECT o.*, u.email, u.username
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) return false;
    
    $subject = "Thông báo hoàn tiền đơn hàng #$orderId";
    
    $content = "
        <h2>Thông báo hoàn tiền</h2>
        <p>Xin chào {$order['username']},</p>
        <p>Đơn hàng #{$orderId} của bạn đã được hoàn tiền với thông tin sau:</p>
        <ul>
            <li>Số tiền hoàn: " . number_format($amount) . " VNĐ</li>
            <li>Lý do: $reason</li>
            <li>Mã giao dịch: $transactionId</li>
        </ul>
        <p>Số tiền sẽ được hoàn về tài khoản của bạn trong vòng 3-5 ngày làm việc.</p>
        <p>Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.</p>
        <p>Trân trọng,<br>Lotso Shop</p>
    ";
    
    return sendEmail($order['email'], $subject, $content);
}
?>
