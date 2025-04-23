<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../functions/validate.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Kiểm tra kết nối database
if (!$conn) {
    die("Không thể kết nối đến cơ sở dữ liệu");
}

// Lấy danh sách danh mục bài viết
try {
    $sql = "SELECT * FROM categories WHERE type = 'post' ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Có lỗi xảy ra: " . $e->getMessage();
    $categories = [];
}

// Xử lý thêm danh mục mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $slug = createSlug($name);
    $type = 'post'; // Thêm type cho danh mục bài viết

    if ($_POST['action'] === 'add') {
        try {
            // Kiểm tra tên danh mục đã tồn tại chưa
            $check_sql = "SELECT id FROM categories WHERE name = ? AND type = 'post'";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $name);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "Tên danh mục đã tồn tại";
            } else {
                $sql = "INSERT INTO categories (name, slug, description, status, type) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssis", $name, $slug, $description, $status, $type);
                if ($stmt->execute()) {
                    $success = "Thêm danh mục thành công";
                    header("Location: post_categories.php");
                    exit;
                } else {
                    $error = "Có lỗi xảy ra khi thêm danh mục";
                }
            }
        } catch (Exception $e) {
            $error = "Có lỗi xảy ra: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        try {
            // Kiểm tra xem danh mục có phải là danh mục bài viết không
            $check_sql = "SELECT id FROM categories WHERE id = ? AND type = 'post'";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows === 0) {
                $error = "Không tìm thấy danh mục bài viết";
            } else {
                $sql = "UPDATE categories SET name = ?, slug = ?, description = ?, status = ? WHERE id = ? AND type = 'post'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $name, $slug, $description, $status, $id);
                if ($stmt->execute()) {
                    $success = "Cập nhật danh mục thành công";
                    header("Location: post_categories.php");
                    exit;
                } else {
                    $error = "Có lỗi xảy ra khi cập nhật danh mục";
                }
            }
        } catch (Exception $e) {
            $error = "Có lỗi xảy ra: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        try {
            // Kiểm tra xem danh mục có phải là danh mục bài viết không
            $check_sql = "SELECT id FROM categories WHERE id = ? AND type = 'post'";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows === 0) {
                $error = "Không tìm thấy danh mục bài viết";
            } else {
                $sql = "DELETE FROM categories WHERE id = ? AND type = 'post'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = "Xóa danh mục thành công";
                    header("Location: post_categories.php");
                    exit;
                } else {
                    $error = "Có lỗi xảy ra khi xóa danh mục";
                }
            }
        } catch (Exception $e) {
            $error = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// Lấy thông tin danh mục để chỉnh sửa
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $id = $_GET['edit'];
        $sql = "SELECT * FROM categories WHERE id = ? AND type = 'post'";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $edit_category = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $error = "Có lỗi xảy ra: " . $e->getMessage();
    }
}

// Hàm tạo slug từ title
function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return $str;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục bài viết - Lotso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/lotso/public/css/style.css">
    <style>
        :root {
            --primary-color: #FF4D8D;
            --primary-hover: #ff3377;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.2rem;
        }

        .btn-edit {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-delete {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include '../templates/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../templates/sidebar.php'; ?>
            
            <div class="col-md-10 ms-sm-auto px-4 py-3">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-3">
                    <h1 class="h2">Quản lý danh mục bài viết</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        + Thêm danh mục mới
                    </button>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0">ID</th>
                                        <th class="border-0">Tên danh mục</th>
                                        <th class="border-0">Slug</th>
                                        <th class="border-0">Mô tả</th>
                                        <th class="border-0">Trạng thái</th>
                                        <th class="border-0">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Không có danh mục nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?= $category['id'] ?></td>
                                                <td><?= htmlspecialchars($category['name']) ?></td>
                                                <td><?= htmlspecialchars($category['slug']) ?></td>
                                                <td><?= htmlspecialchars($category['description']) ?></td>
                                                <td>
                                                    <span class="badge <?= $category['status'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $category['status'] == 1 ? 'Hoạt động' : 'Không hoạt động' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary me-1" onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?= $category['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Thêm danh mục mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="categoryForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" id="categoryId">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên danh mục</label>
                            <input type="text" class="form-control" name="name" id="categoryName" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" id="categoryDescription" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status" id="categoryStatus">
                                <option value="1">Hoạt động</option>
                                <option value="0">Không hoạt động</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        
        function editCategory(category) {
            document.getElementById('categoryForm').action.value = 'edit';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description;
            document.getElementById('categoryStatus').value = category.status;
            document.querySelector('#categoryModal .modal-title').textContent = 'Chỉnh sửa danh mục';
            modal.show();
        }

        function deleteCategory(id) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryForm').action.value = 'add';
            document.getElementById('categoryId').value = '';
            document.querySelector('#categoryModal .modal-title').textContent = 'Thêm danh mục mới';
        });
    </script>
</body>
</html> 
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        
        function editCategory(category) {
            document.getElementById('categoryForm').action.value = 'edit';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description;
            document.getElementById('categoryStatus').value = category.status;
            document.querySelector('#categoryModal .modal-title').textContent = 'Chỉnh sửa danh mục';
            modal.show();
        }

        function deleteCategory(id) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryForm').action.value = 'add';
            document.getElementById('categoryId').value = '';
            document.querySelector('#categoryModal .modal-title').textContent = 'Thêm danh mục mới';
        });
    </script>
</body>
</html> 
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        
        function editCategory(category) {
            document.getElementById('categoryForm').action.value = 'edit';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description;
            document.getElementById('categoryStatus').value = category.status;
            document.querySelector('#categoryModal .modal-title').textContent = 'Chỉnh sửa danh mục';
            modal.show();
        }

        function deleteCategory(id) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryForm').action.value = 'add';
            document.getElementById('categoryId').value = '';
            document.querySelector('#categoryModal .modal-title').textContent = 'Thêm danh mục mới';
        });
    </script>
</body>
</html> 
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        
        function editCategory(category) {
            document.getElementById('categoryForm').action.value = 'edit';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description;
            document.getElementById('categoryStatus').value = category.status;
            document.querySelector('#categoryModal .modal-title').textContent = 'Chỉnh sửa danh mục';
            modal.show();
        }

        function deleteCategory(id) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryForm').action.value = 'add';
            document.getElementById('categoryId').value = '';
            document.querySelector('#categoryModal .modal-title').textContent = 'Thêm danh mục mới';
        });
    </script>
</body>
</html> 
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        
        function editCategory(category) {
            document.getElementById('categoryForm').action.value = 'edit';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description;
            document.getElementById('categoryStatus').value = category.status;
            document.querySelector('#categoryModal .modal-title').textContent = 'Chỉnh sửa danh mục';
            modal.show();
        }

        function deleteCategory(id) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryForm').action.value = 'add';
            document.getElementById('categoryId').value = '';
            document.querySelector('#categoryModal .modal-title').textContent = 'Thêm danh mục mới';
        });
    </script>
</body>
</html> 
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        
        function editCategory(category) {
            document.getElementById('categoryForm').action.value = 'edit';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description;
            document.getElementById('categoryStatus').value = category.status;
            document.querySelector('#categoryModal .modal-title').textContent = 'Chỉnh sửa danh mục';
            modal.show();
        }

        function deleteCategory(id) {
            if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryForm').action.value = 'add';
            document.getElementById('categoryId').value = '';
            document.querySelector('#categoryModal .modal-title').textContent = 'Thêm danh mục mới';
        });
    </script>
</body>
</html> 