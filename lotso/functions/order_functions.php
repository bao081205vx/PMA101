<?php
include '../config/db.php'; // Kết nối DB

// 🛒 Hàm lấy danh sách đơn hàng
function getOrders() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT orders.id, orders.total_price, orders.status, orders.created_at, users.username 
        FROM orders 
        JOIN users ON orders.user_id = users.id
        ORDER BY orders.created_at DESC
    ");
    return $stmt->fetchAll();
}

// 📦 Hàm lấy chi tiết đơn hàng theo ID (bao gồm sản phẩm trong đơn)
function getOrderById($id) {
    global $pdo;
    
    // Lấy thông tin đơn hàng
    $stmt = $pdo->prepare("
        SELECT orders.*, users.username 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        WHERE orders.id = ?
    ");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) return null;

    // Lấy danh sách sản phẩm trong đơn hàng
    $stmt = $pdo->prepare("
        SELECT order_items.quantity, order_items.price, products.name, products.image 
        FROM order_items
        JOIN products ON order_items.product_id = products.id
        WHERE order_items.order_id = ?
    ");
    $stmt->execute([$id]);
    $order['items'] = $stmt->fetchAll();

    return $order;
}

// 🔄 Hàm cập nhật trạng thái đơn hàng
function updateOrderStatus($id, $status) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

// ❌ Hàm xóa đơn hàng (xóa cả order_items trước)
function deleteOrder($id) {
    global $pdo;

    try {
        $pdo->beginTransaction();
        
        // Xóa sản phẩm trong đơn hàng
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$id]);

        // Xóa đơn hàng chính
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
?>
