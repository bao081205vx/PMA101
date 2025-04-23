<?php
session_start();
require_once '../config/Database.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'order_errors.log');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['return_to'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Kiểm tra giỏ hàng
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate thông tin
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'];
    
    $errors = [];
    if (empty($name)) $errors[] = "Vui lòng nhập họ tên";
    if (empty($phone)) $errors[] = "Vui lòng nhập số điện thoại";
    if (empty($address)) $errors[] = "Vui lòng nhập địa chỉ";
    
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Tính tổng tiền
            $total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            // Thêm đơn hàng
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, total_price, shipping_name, shipping_phone, shipping_address, payment_method, payment_status, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Xác định trạng thái đơn hàng và thanh toán
            $orderStatus = ($payment_method === 'cod') ? 'pending' : 'waiting_payment';
            $paymentStatus = 'pending';
            
            // Kiểm tra tồn kho trước khi tạo đơn hàng
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $check_stock = $conn->prepare("SELECT stock FROM products WHERE id = ? AND stock >= ?");
                $check_stock->bind_param("ii", $product_id, $item['quantity']);
                $check_stock->execute();
                if (!$check_stock->get_result()->fetch_assoc()) {
                    throw new Exception("Sản phẩm #$product_id không đủ số lượng trong kho");
                }
            }
            
            // Tạo đơn hàng
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, total_price, shipping_name, shipping_phone, shipping_address, payment_method, payment_status, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("idssssss", $_SESSION['user_id'], $total, $name, $phone, $address, $payment_method, $paymentStatus, $orderStatus);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi tạo đơn hàng: " . $stmt->error);
            }
            $order_id = $conn->insert_id;
            
            // Thêm chi tiết đơn hàng
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                if (!$stmt->execute()) {
                    throw new Exception("Lỗi khi thêm chi tiết đơn hàng: " . $stmt->error);
                }
                
                // Cập nhật số lượng tồn kho
                $stmt_update = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt_update->bind_param("ii", $item['quantity'], $product_id);
                if (!$stmt_update->execute()) {
                    throw new Exception("Lỗi khi cập nhật tồn kho: " . $stmt_update->error);
                }
            }
            
            $conn->commit();
            
            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            
            // Xử lý thanh toán online
            // Xử lý thanh toán online
            if ($payment_method === 'bank') {
                $_SESSION['payment_order_id'] = $order_id;
                $_SESSION['payment_method'] = $payment_method;
                header('Location: process_payment.php');
                exit;
            } else if (in_array($payment_method, ['momo', 'zalopay'])) {
                $_SESSION['checkout_error'] = "Phương thức thanh toán " . strtoupper($payment_method) . " đang được phát triển";
                header('Location: checkout.php');
                exit;
            }
            
            // Chuyển đến trang cảm ơn
            header("Location: thank-you.php?order_id=$order_id");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log('Order Error: ' . $e->getMessage());
            error_log('Order Error Stack Trace: ' . $e->getTraceAsString());
            $_SESSION['checkout_error'] = "Có lỗi xảy ra, vui lòng thử lại";
            header('Location: checkout.php');
            exit;
        }
    } else {
        $_SESSION['checkout_error'] = implode("<br>", $errors);
        header('Location: checkout.php');
        exit;
    }
}

// Nếu không phải POST request, chuyển về trang checkout
header('Location: checkout.php');
exit;
?>
