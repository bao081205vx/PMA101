<?php
// Thông tin kết nối database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'shoe_store';

// Tạo kết nối
$conn = new mysqli($host, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?> 