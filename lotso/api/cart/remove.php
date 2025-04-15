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
        throw new Exception('Vui lòng đăng nhập để xóa sản phẩm khỏi giỏ hàng');
    }

    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    // Validate required fields
    if (empty($data['product_id'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Remove product from cart
    $found = false;
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $data['product_id']) {
            unset($_SESSION['cart'][$key]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        throw new Exception('Sản phẩm không có trong giỏ hàng');
    }

    // Reindex array after removal
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    // Calculate cart totals
    $totalItems = 0;
    $totalAmount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $totalItems += $item['quantity'];
        $totalAmount += $item['quantity'] * $item['price'];
    }

    sendResponse(200, true, 'Xóa sản phẩm khỏi giỏ hàng thành công', [
        'total_items' => $totalItems,
        'total_amount' => $totalAmount
    ]);
} catch (Exception $e) {
    sendResponse(400, false, $e->getMessage());
}
