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
        throw new Exception('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng');
    }

    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    // Validate required fields
    if (empty($data['product_id']) || empty($data['quantity'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    // Validate quantity
    if ($data['quantity'] < 1) {
        throw new Exception('Số lượng không hợp lệ');
    }

    // Check if product exists and get its price
    $stmt = $conn->prepare("SELECT id, price, stock FROM products WHERE id = ? AND active = 1");
    $stmt->execute([$data['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Sản phẩm không tồn tại hoặc đã bị vô hiệu hóa');
    }

    // Check stock
    if ($product['stock'] < $data['quantity']) {
        throw new Exception('Số lượng sản phẩm trong kho không đủ');
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product already in cart
    $productExists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $data['product_id']) {
            // Update quantity
            $newQuantity = $item['quantity'] + $data['quantity'];
            if ($newQuantity > $product['stock']) {
                throw new Exception('Số lượng sản phẩm trong kho không đủ');
            }
            $item['quantity'] = $newQuantity;
            $productExists = true;
            break;
        }
    }

    // Add new product to cart
    if (!$productExists) {
        $_SESSION['cart'][] = [
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'price' => $product['price']
        ];
    }

    // Calculate cart totals
    $totalItems = 0;
    $totalAmount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $totalItems += $item['quantity'];
        $totalAmount += $item['quantity'] * $item['price'];
    }

    sendResponse(200, true, 'Thêm vào giỏ hàng thành công', [
        'total_items' => $totalItems,
        'total_amount' => $totalAmount
    ]);
} catch (Exception $e) {
    sendResponse(400, false, $e->getMessage());
}
