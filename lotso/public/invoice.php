<?php
include_once '../functions/payment_functions.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Tạo thư mục invoices nếu chưa tồn tại
$invoiceDir = __DIR__ . '/invoices';
if (!file_exists($invoiceDir)) {
    mkdir($invoiceDir, 0777, true);
}

// Tạo PDF
$filename = generateInvoicePdf($orderId);
if (!$filename) {
    die('Không thể tạo hóa đơn');
}

// Trả về file PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
readfile($invoiceDir . '/' . $filename);
?>
