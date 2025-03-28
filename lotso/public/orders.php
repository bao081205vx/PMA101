<?php
include '../config/db.php';
include '../templates/header.php';
include '../templates/sidebar.php';


$stmt = $conn->query("SELECT orders.*, users.username FROM orders 
                      JOIN users ON orders.user_id = users.id");
$orders = $stmt->fetchAll();
?>
<script src="js/main.js"></script>

<div class="container mt-5">
    <h2>Quản lý Đơn hàng</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Khách hàng</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order) : ?>
                <tr>
                    <td><?= $order['id']; ?></td>
                    <td><?= $order['username']; ?></td>
                    <td><?= $order['total_price']; ?></td>
                    <td><?= ucfirst($order['status']); ?></td>
                    <td><?= $order['created_at']; ?></td>
                    <td>
                        <a href="edit_order.php?id=<?= $order['id']; ?>" class="btn btn-warning">Sửa</a>
                        <a href="delete_order.php?id=<?= $order['id']; ?>" class="btn btn-danger">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../templates/footer.php'; ?>
