<?php
include '../config/db.php';
include '../templates/header.php';

$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll();
?>

<div class="container mt-5">
    <h2 class="text-center text-uppercase" style="color: #FF4081; font-weight: bold;">Quản lý Sản phẩm</h2>
    <a href="add_product.php" class="btn btn-primary mb-3">➕ Thêm sản phẩm</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product) : ?>
                <tr>
                    <td class="text-center"><?= $product['id']; ?></td>
                    <td><?= $product['name']; ?></td>
                    <td class="text-center"><?= number_format($product['price'], 0, ',', '.') . ' đ'; ?></td>
                    <td class="text-center"><?= $product['stock']; ?></td>
                    <td class="text-center">
                        <a href="show_product.php?id=<?= $product['id']; ?>" class="btn btn-success">Xem</a>
                        <a href="edit_product.php?id=<?= $product['id']; ?>" class="btn btn-warning">✏️ Sửa</a>
                        <a href="delete_product.php?id=<?= $product['id']; ?>" class="btn btn-danger">🗑 Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../templates/footer.php'; ?>