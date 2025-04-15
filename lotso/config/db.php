<?php
$host = "localhost";
$dbname = "shoe_store"; // Sửa tên database thành shoe_store
$username = "root"; // Nếu có username khác, hãy đổi lại
$password = ""; // Nếu có mật khẩu, hãy điền vào

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>
