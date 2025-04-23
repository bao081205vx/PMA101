<?php
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['quantity']) || !is_array($_POST['quantity'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    $database = new Database();
    $conn = $database->getConnection();

    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL');
        }

        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception('Lỗi thực thi câu lệnh SQL');
        }

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        if ($quantity > $product['quantity']) {
            throw new Exception("Số lượng sản phẩm '{$product['name']}' vượt quá số lượng trong kho");
        }

        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng thành công'
    ]);

} catch (Exception $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['quantity']) || !is_array($_POST['quantity'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    $database = new Database();
    $conn = $database->getConnection();

    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL');
        }

        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception('Lỗi thực thi câu lệnh SQL');
        }

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        if ($quantity > $product['quantity']) {
            throw new Exception("Số lượng sản phẩm '{$product['name']}' vượt quá số lượng trong kho");
        }

        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng thành công'
    ]);

} catch (Exception $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['quantity']) || !is_array($_POST['quantity'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    $database = new Database();
    $conn = $database->getConnection();

    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL');
        }

        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception('Lỗi thực thi câu lệnh SQL');
        }

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        if ($quantity > $product['quantity']) {
            throw new Exception("Số lượng sản phẩm '{$product['name']}' vượt quá số lượng trong kho");
        }

        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng thành công'
    ]);

} catch (Exception $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['quantity']) || !is_array($_POST['quantity'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    $database = new Database();
    $conn = $database->getConnection();

    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL');
        }

        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception('Lỗi thực thi câu lệnh SQL');
        }

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        if ($quantity > $product['quantity']) {
            throw new Exception("Số lượng sản phẩm '{$product['name']}' vượt quá số lượng trong kho");
        }

        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng thành công'
    ]);

} catch (Exception $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['quantity']) || !is_array($_POST['quantity'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    $database = new Database();
    $conn = $database->getConnection();

    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL');
        }

        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception('Lỗi thực thi câu lệnh SQL');
        }

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        if ($quantity > $product['quantity']) {
            throw new Exception("Số lượng sản phẩm '{$product['name']}' vượt quá số lượng trong kho");
        }

        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng thành công'
    ]);

} catch (Exception $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['quantity']) || !is_array($_POST['quantity'])) {
        throw new Exception('Dữ liệu không hợp lệ');
    }

    $database = new Database();
    $conn = $database->getConnection();

    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Lỗi chuẩn bị câu lệnh SQL');
        }

        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception('Lỗi thực thi câu lệnh SQL');
        }

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        if ($quantity > $product['quantity']) {
            throw new Exception("Số lượng sản phẩm '{$product['name']}' vượt quá số lượng trong kho");
        }

        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng thành công'
    ]);

} catch (Exception $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 