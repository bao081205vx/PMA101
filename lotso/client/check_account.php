<?php
require_once '../config/Database.php';

$database = new Database();
$conn = $database->getConnection();

$email = "hungbiuu@gmail.com";

$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo "Tìm thấy tài khoản:\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Password hash length: " . strlen($user['password']) . "\n";
} else {
    echo "Không tìm thấy tài khoản với email: " . $email;
}
?>
