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
        throw new Exception('Vui lòng đăng nhập để xem giỏ hàng');
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Get cart items with product details
    $cartItems = [];
    $totalItems = 0;
    $totalAmount = 0;

    foreach ($_SESSION['cart'] as $item) {
        // Get product details
        $stmt = $conn->prepare("
            SELECT id, name, price, image, stock 
            FROM products 
            WHERE id = ? AND active = 1
        ");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Update price in case it changed
            $item['price'] = $product['price'];

            // Add to cart items
            $cartItems[] = [
                'product_id' => $item['product_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'stock' => $product['stock'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['quantity'] * $product['price']
            ];

            // Update totals
            $totalItems += $item['quantity'];
            $totalAmount += $item['quantity'] * $product['price'];
        }
    }

    sendResponse(200, true, 'Lấy thông tin giỏ hàng thành công', [
        'items' => $cartItems,
        'total_items' => $totalItems,
        'total_amount' => $totalAmount
    ]);
} catch (Exception $e) {
    sendResponse(400, false, $e->getMessage());
}
