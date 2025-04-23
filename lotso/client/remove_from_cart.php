<?php
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    $product_id = (int)$_POST['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
        ]);
    }
} catch (Exception $e) {
    error_log('Remove from cart error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    $product_id = (int)$_POST['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
        ]);
    }
} catch (Exception $e) {
    error_log('Remove from cart error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    $product_id = (int)$_POST['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
        ]);
    }
} catch (Exception $e) {
    error_log('Remove from cart error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    $product_id = (int)$_POST['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
        ]);
    }
} catch (Exception $e) {
    error_log('Remove from cart error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    $product_id = (int)$_POST['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
        ]);
    }
} catch (Exception $e) {
    error_log('Remove from cart error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Thiếu thông tin sản phẩm');
    }

    $product_id = (int)$_POST['product_id'];
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
        ]);
    }
} catch (Exception $e) {
    error_log('Remove from cart error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 