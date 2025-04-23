<?php
include_once '../functions/stats_functions.php';

// Kiểm tra quyền admin
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Tạo báo cáo Excel
$filename = exportPaymentReport($startDate, $endDate);

// Trả về file Excel
$filepath = __DIR__ . '/reports/' . $filename;
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
readfile($filepath);

// Xóa file sau khi tải xong
unlink($filepath);
?>
