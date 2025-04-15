<?php
require_once '../../config/database.php';
require_once '../../functions/response.php';
require_once '../../functions/validate.php';

header('Content-Type: application/json');

try {
    // Start session
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user'])) {
        throw new Exception('Vui lòng đăng nhập để xóa giỏ hàng');
    }

    // Clear cart
    $_SESSION['cart'] = [];

    sendResponse(200, true, 'Xóa giỏ hàng thành công', [
        'total_items' => 0,
        'total_amount' => 0
    ]);
} catch (Exception $e) {
    sendResponse(400, false, $e->getMessage());
}
