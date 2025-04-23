<?php
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Lấy danh sách đơn hàng
$status = isset($_GET['status']) ? $_GET['status'] : '';
$where = "user_id = ?";
$params = [$_SESSION['user_id']];
$types = "i";

if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$stmt = $conn->prepare("
    SELECT *, 
    (SELECT COUNT(*) FROM order_items WHERE order_id = orders.id) as total_items 
    FROM orders 
    WHERE $where 
    ORDER BY created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'shipping' => 'primary',
    'completed' => 'success',
    'canceled' => 'danger',
    'waiting_payment' => 'secondary'
];

$status_texts = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Đã giao',
    'canceled' => 'Đã hủy',
    'waiting_payment' => 'Chờ thanh toán'
];
?>

<div class="container py-4">
    <div class="row px-2">
        <div class="col-lg-3 mb-4">
            <!-- User Profile Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-4">
                    <img src="<?= isset($user['avatar']) ? 'uploads/avatars/' . $user['avatar'] : 'assets/images/default-avatar.jpg' ?>" 
                         alt="Avatar" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                    <h5 class="mb-1"><?= htmlspecialchars($user['username']) ?></h5>
                    <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>

            <!-- Order Status Filter -->
            <div class="list-group shadow-sm">
                <a href="?" class="list-group-item list-group-item-action <?= !isset($_GET['status']) ? 'active' : '' ?>">
                    Tất cả đơn hàng
                </a>
                <a href="?status=pending" class="list-group-item list-group-item-action <?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'active' : '' ?>">
                    <span class="text-warning">●</span> Chờ thanh toán
                </a>
                <a href="?status=processing" class="list-group-item list-group-item-action <?= isset($_GET['status']) && $_GET['status'] === 'processing' ? 'active' : '' ?>">
                    <span class="text-info">●</span> Đang xử lý
                </a>
                <a href="?status=shipping" class="list-group-item list-group-item-action <?= isset($_GET['status']) && $_GET['status'] === 'shipping' ? 'active' : '' ?>">
                    <span class="text-primary">●</span> Đang giao
                </a>
                <a href="?status=completed" class="list-group-item list-group-item-action <?= isset($_GET['status']) && $_GET['status'] === 'completed' ? 'active' : '' ?>">
                    <span class="text-success">●</span> Đã giao
                </a>
                <a href="?status=waiting_payment" class="list-group-item list-group-item-action <?= isset($_GET['status']) && $_GET['status'] === 'waiting_payment' ? 'active' : '' ?>">
                    <span class="text-secondary">●</span> Chờ thanh toán
                </a>
                <a href="?status=canceled" class="list-group-item list-group-item-action <?= isset($_GET['status']) && $_GET['status'] === 'canceled' ? 'active' : '' ?>">
                    <span class="text-danger">●</span> Đã hủy
                </a>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h4 class="mb-4">Đơn hàng của tôi</h4>

                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <img src="./assets/images/empty-order.png" alt="No orders" class="mb-4" width="150">
                            <h5>Bạn chưa có đơn hàng nào</h5>
                            <p class="text-muted">Hãy mua sắm và quay lại đây để xem đơn hàng của bạn</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>Mua sắm ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Sản phẩm</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="fw-medium">#<?= $order['id'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                        <td><?= $order['total_items'] ?> sản phẩm</td>
                                        <td class="fw-medium"><?= number_format($order['total_price']) ?> đ</td>
                                        <td>
                                            <span class="badge bg-<?= $status_colors[$order['status']] ?> rounded-pill">
                                                <?= $status_texts[$order['status']] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">Chi tiết</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #ff4081;
    --secondary-color: #ff80ab;
    --hover-color: #f50057;
}

.profile-sidebar {
    position: sticky;
    top: 20px;
}

.order-tabs .nav-link {
    color: var(--primary-color) !important;
    background-color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
}

.order-tabs .nav-link:hover {
    background-color: #fff5f8;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(255, 64, 129, 0.15);
}

.order-tabs .nav-link.active {
    color: white !important;
    background-color: var(--primary-color);
    box-shadow: 0 4px 8px rgba(255, 64, 129, 0.2);
}

.order-tabs .nav-link.active:hover {
    background-color: var(--hover-color);
    color: white !important;
    transform: translateY(-1px);
}

.order-tabs .nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 8px;
    font-size: 14px;
}

/* Đảm bảo màu icon không bị ghi đè */
.order-tabs .nav-link:not(.active) i.text-warning { color: #ffc107 !important; }
.order-tabs .nav-link:not(.active) i.text-info { color: #0dcaf0 !important; }
.order-tabs .nav-link:not(.active) i.text-primary { color: #0d6efd !important; }
.order-tabs .nav-link:not(.active) i.text-success { color: #198754 !important; }
.order-tabs .nav-link:not(.active) i.text-danger { color: #dc3545 !important; }

.order-tabs .nav-link.active i {
    color: white !important;
}

/* Table styles */
.table thead th {
    background-color: var(--primary-color);
    color: white;
    font-weight: 500;
    border: none;
    padding: 15px;
}

.table tbody td {
    padding: 15px;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #fff5f8;
}

/* Card styles */
.card {
    border-radius: 10px;
    overflow: hidden;
}

.card-title {
    color: var(--primary-color);
    font-weight: 600;
}

/* Button styles */
.btn-outline-primary {
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-primary:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

/* Badge styles */
.badge {
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 500;
}
</style>

<?php require_once 'footer.php'; ?>
