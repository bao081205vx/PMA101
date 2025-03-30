<?php
include '../config/db.php';
include '../templates/header.php';

?>

<div class="content">
    <h2>Quản lý danh mục</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên danh mục</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Giày nam</td>
                <td>
                    <button class="btn btn-warning">Sửa</button>
                    <button class="btn btn-danger">Xóa</button>
                </td>
            </tr>
            <tr>
                <td>2</td>
                <td>Giày nữ</td>
                <td>
                    <button class="btn btn-warning">Sửa</button>
                    <button class="btn btn-danger">Xóa</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php include '../templates/footer.php'; ?>
