<?php
require '../config/db.php'; // Kết nối database

// Hàm lấy danh sách sản phẩm
function getAllProducts($pdo) {
    $stmt = $pdo->query("SELECT * FROM products");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm lấy thông tin một sản phẩm theo ID
function getProductById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm thêm hoặc cập nhật sản phẩm
function saveProduct($pdo, $data) {
    if (!empty($data['id'])) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, stock=? WHERE id=?");
        return $stmt->execute([$data['name'], $data['price'], $data['stock'], $data['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
        return $stmt->execute([$data['name'], $data['price'], $data['stock']]);
    }
}

// Hàm xóa sản phẩm
function deleteProduct($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    return $stmt->execute([$id]);
}