<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../templates/header.php';

// Hàm tạo slug
function createSlug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#[^a-z0-9\s]#'
    );
    $replace = array(
        'a',
        'e',
        'i',
        'o',
        'u',
        'y',
        'd',
        ''
    );
    $string = strtolower(preg_replace($search, $replace, $string));
    $string = preg_replace('/\s+/', '-', $string);
    return trim($string, '-');
}

// Xử lý thêm danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $status = ($_POST['status'] === 'active') ? 1 : 0;
        $type = $_POST['type'];
        $slug = createSlug($name);
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description, status, slug, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $name, $description, $status, $slug, $type);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Thêm danh mục thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi thêm danh mục: " . $conn->error;
        }
    }
    
    // Xử lý sửa danh mục
    if ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $status = ($_POST['status'] === 'active') ? 1 : 0;
        $type = $_POST['type'];
        $slug = createSlug($name);
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, status = ?, slug = ?, type = ? WHERE id = ?");
        $stmt->bind_param("ssissi", $name, $description, $status, $slug, $type, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Cập nhật danh mục thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật danh mục: " . $conn->error;
        }
    }
    
    // Xử lý xóa danh mục
    if ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Xóa danh mục thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi xóa danh mục: " . $conn->error;
        }
    }
    
    header("Location: categories.php");
    exit();
}

// Thiết lập phân trang
$items_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Lấy type từ query parameter, mặc định là 'product'
$type = isset($_GET['type']) ? $_GET['type'] : 'product';

// Đếm tổng số danh mục theo type
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM categories WHERE type = ?");
if (!$count_stmt) {
    die("Prepare failed: " . $conn->error);
}
$count_stmt->bind_param("s", $type);
if (!$count_stmt->execute()) {
    die("Execute failed: " . $count_stmt->error);
}
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách danh mục có phân trang theo type
$stmt = $conn->prepare("SELECT * FROM categories WHERE type = ? ORDER BY id DESC LIMIT ? OFFSET ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("sii", $type, $items_per_page, $offset);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../templates/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                <div>
                    <h1 class="h2">Quản lý danh mục</h1>
                    <div class="btn-group" role="group" aria-label="Loại danh mục">
                        <a href="?type=product" class="btn btn<?= $type === 'product' ? '' : '-outline' ?>-primary">Sản phẩm</a>
                        <a href="?type=post" class="btn btn<?= $type === 'post' ? '' : '-outline' ?>-primary">Bài viết</a>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    + Thêm danh mục
            </button>
        </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

        <div class="table-responsive">
                <table class="table">
                    <thead class="table-pink">
                        <tr>
                            <th>ID</th>
                            <th>Tên danh mục</th>
                        <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th>Loại</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                    </tr>
                </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= $category['id'] ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars($category['description']) ?></td>
                            <td>
                                <span class="badge rounded-pill bg-success">
                                    <?= $category['status'] == 1 ? 'Hoạt động' : 'Ẩn' ?>
                                </span>
                            </td>
                            <td><?= $category['type'] == 'product' ? 'Sản phẩm' : 'Bài viết' ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($category['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-pink edit-category" 
                                        data-id="<?= $category['id'] ?>"
                                        data-name="<?= htmlspecialchars($category['name']) ?>"
                                        data-description="<?= htmlspecialchars($category['description']) ?>"
                                        data-status="<?= $category['status'] == 1 ? 'active' : 'inactive' ?>"
                                        data-type="<?= $category['type'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-category" 
                                        data-id="<?= $category['id'] ?>"
                                        data-name="<?= htmlspecialchars($category['name']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>

                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?type=<?= $type ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?type=<?= $type ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?type=<?= $type ?>&page=<?= $page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<style>
.table-pink thead {
    background-color: #ff4081;
    color: white;
}

.btn-pink {
    background-color: #ff4081;
    color: white;
}

.btn-pink:hover {
    background-color: #f50057;
    color: white;
}

.page-item.active .page-link {
    background-color: #ff4081;
    border-color: #ff4081;
}

.page-link {
    color: #ff4081;
}

.page-link:hover {
    color: #f50057;
}

.badge {
    font-size: 0.875rem;
    padding: 0.5em 1em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý sửa danh mục
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const status = this.dataset.status;
            const type = this.dataset.type || 'product';
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_status').value = status;
            document.getElementById('editType').value = type;
            
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        });
    });
    
    // Xử lý xóa danh mục
    document.querySelectorAll('.delete-category').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
        });
    });
});
</script>

<!-- Modal Thêm danh mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm danh mục mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Tên danh mục</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Ẩn</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Loại danh mục</label>
                        <select class="form-select" id="type" name="type">
                            <option value="product">Sản phẩm</option>
                            <option value="post">Bài viết</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa danh mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Tên danh mục</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Ẩn</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editType" class="form-label">Loại danh mục</label>
                        <select class="form-select" id="editType" name="type">
                            <option value="product">Sản phẩm</option>
                            <option value="post">Bài viết</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xóa danh mục -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Bạn có chắc chắn muốn xóa danh mục "<span id="delete_name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
