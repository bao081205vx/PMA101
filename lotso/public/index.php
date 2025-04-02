<?php
require_once '../functions/database.php';
require_once '../templates/header.php';
require_once '../templates/sidebar.php';

$db = new Database();
$conn = $db->getConnection();

try {
    // Lấy thông tin tổng quan
    $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
    $totalCategories = $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];
    $totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
    $totalPosts = $conn->query("SELECT COUNT(*) FROM posts")->fetch_row()[0];

    // Lấy sản phẩm mới nhất
    $latestProducts = $conn->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    // Lấy đơn hàng mới nhất
    $latestOrders = $conn->query("
        SELECT o.*, u.username, u.email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    // Lấy bài viết mới nhất
    $latestPosts = $conn->query("
        SELECT p.*, c.name as category_name, u.username as author_name 
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    // Lấy đơn hàng gần đây
    $recentOrders = $conn->query("
        SELECT o.*, u.username, u.email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Hàm helper để lấy class cho trạng thái
function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'processing':
            return 'bg-info';
        case 'completed':
            return 'bg-success';
        case 'canceled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Hàm helper để lấy tên trạng thái tiếng Việt
function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'Chờ xử lý';
        case 'processing':
            return 'Đang xử lý';
        case 'completed':
            return 'Hoàn thành';
        case 'canceled':
            return 'Đã hủy';
        default:
            return 'Không xác định';
    }
}
?>

<div class="content">
    <div class="container-fluid">
        <h2 class="mb-4">Trang Quản Trị</h2>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-box"></i> Sản phẩm
                        </h5>
                        <p class="card-text">Tổng số: <?= $totalProducts ?></p>
                        <a href="products.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-list"></i> Danh mục
                        </h5>
                        <p class="card-text">Tổng số: <?= $totalCategories ?></p>
                        <a href="categories.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-users"></i> Người dùng
                        </h5>
                        <p class="card-text">Tổng số: <?= $totalUsers ?></p>
                        <a href="users.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-shopping-cart"></i> Đơn hàng
                        </h5>
                        <p class="card-text">Tổng số: <?= $totalOrders ?></p>
                        <a href="orders.php" class="btn btn-light btn-sm">Xem chi tiết</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đơn hàng gần đây -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-shopping-cart"></i> Đơn hàng gần đây
                    </h5>
                    <a href="orders.php" class="btn btn-light btn-sm">
                        <i class="fas fa-external-link-alt"></i> Xem tất cả
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td>
                                    <div><?= htmlspecialchars($order['username']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                                </td>
                                <td><?= number_format($order['total_price']) ?> đ</td>
                                <td>
                                    <span class="badge <?= getStatusClass($order['status']) ?>">
                                        <?= getStatusText($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Nội dung khác -->
        <div class="row">
            <!-- Sản phẩm mới nhất -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-box"></i> Sản phẩm mới nhất
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($latestProducts as $product): ?>
                            <a href="products.php" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                                    <small><?= number_format($product['price']) ?> đ</small>
                                </div>
                                <small class="text-muted">
                                    Danh mục: <?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?>
                                </small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bài viết mới nhất -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-newspaper"></i> Bài viết mới nhất
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($latestPosts as $post): ?>
                            <a href="posts.php" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($post['title']) ?></h6>
                                    <small><?= date('d/m/Y', strtotime($post['created_at'])) ?></small>
                                </div>
                                <small class="text-muted">
                                    <?= htmlspecialchars($post['author_name'] ?? 'Không xác định') ?> - 
                                    <?= htmlspecialchars($post['category_name'] ?? 'Chưa phân loại') ?>
                                </small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
