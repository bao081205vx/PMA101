<?php
require '../config/db.php'; // Kết nối database

// Hàm lấy danh sách mã giảm giá
function getAllDiscounts($pdo) {
    $stmt = $pdo->query("SELECT * FROM discounts ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm thêm hoặc cập nhật mã giảm giá
function saveDiscount($pdo, $data) {
    if (!empty($data['id'])) {
        // Cập nhật mã giảm giá
        $stmt = $pdo->prepare("UPDATE discounts SET code=?, discount_type=?, discount_value=?, start_date=?, end_date=? WHERE id=?");
        return $stmt->execute([$data['code'], $data['discount_type'], $data['discount_value'], $data['start_date'], $data['end_date'], $data['id']]);
    } else {
        // Thêm mã giảm giá mới
        $stmt = $pdo->prepare("INSERT INTO discounts (code, discount_type, discount_value, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$data['code'], $data['discount_type'], $data['discount_value'], $data['start_date'], $data['end_date']]);
    }
}

// Hàm xóa mã giảm giá
function deleteDiscount($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM discounts WHERE id = ?");
    return $stmt->execute([$id]);
}

// Xử lý xóa mã giảm giá
if (isset($_GET['delete'])) {
    deleteDiscount($pdo, $_GET['delete']);
    header("Location: discounts.php");
    exit;
}

// Xử lý thêm hoặc sửa mã giảm giá
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    saveDiscount($pdo, $_POST);
    header("Location: discounts.php");
    exit;
}

// Lấy danh sách mã giảm giá
$discounts = getAllDiscounts($pdo);


