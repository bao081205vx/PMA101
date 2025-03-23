<?php
include '../config/db.php';
include '../templates/header.php';

// Lấy tổng số lượng từ database
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>

<div class="container mt-5">
    <h2>Trang Quản Trị</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Sản phẩm</h5>
                    <p class="card-text">Tổng số: <?= $totalProducts; ?></p>
                    <a href="products.php" class="btn btn-light">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Người dùng</h5>
                    <p class="card-text">Tổng số: <?= $totalUsers; ?></p>
                    <a href="users.php" class="btn btn-light">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Đơn hàng</h5>
                    <p class="card-text">Tổng số: <?= $totalOrders; ?></p>
                    <a href="orders.php" class="btn btn-light">Xem chi tiết</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>

