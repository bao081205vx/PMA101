<?php
require_once 'header.php';
require_once 'sidebar.php';

// Xử lý cập nhật trạng thái đơn hàng
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $note = $_POST['note'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        // Thêm vào lịch sử đơn hàng
        $status_texts = [
            'pending' => 'Chờ thanh toán',
            'processing' => 'Đang xử lý',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Đã giao hàng thành công',
            'cancelled' => 'Đã hủy'
        ];

        $status_text = $status_texts[$new_status];
        
        $stmt = $conn->prepare("
            INSERT INTO order_history (order_id, status, status_text, note) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $order_id, $new_status, $status_text, $note);
        $stmt->execute();
        
        $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công!";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật trạng thái!";
    }
    
    header("Location: orders.php");
    exit;
}

// Lọc đơn hàng
$where = "1=1";
$params = [];
$types = "";

if (isset($_GET['status']) && $_GET['status'] != '') {
    $where .= " AND o.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (isset($_GET['search']) && $_GET['search'] != '') {
    $search = "%" . $_GET['search'] . "%";
    $where .= " AND (o.id LIKE ? OR u.username LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= "ssss";
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tổng số đơn hàng
$count_stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE $where
");
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Lấy danh sách đơn hàng
$sql = "
    SELECT o.*, u.username, 
           (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_items
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE $where 
    ORDER BY o.created_at DESC 
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);

// Thêm limit và offset vào params
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'shipping' => 'primary',
    'completed' => 'success',
    'cancelled' => 'danger'
];

$status_texts = [
    'pending' => 'Chờ thanh toán',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao',
    'completed' => 'Đã giao',
    'cancelled' => 'Đã hủy'
];
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <?php require_once 'topbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <h1 class="h3 mb-2 text-gray-800">Quản lý đơn hàng</h1>

            <!-- Alerts -->
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

            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row align-items-center">
                        <div class="col-md-4 mb-3">
                            <label>Tìm kiếm</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                                   placeholder="Mã đơn, tên KH, SĐT...">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Trạng thái</label>
                            <select class="form-control" name="status">
                                <option value="">Tất cả</option>
                                <?php foreach ($status_texts as $key => $text): ?>
                                    <option value="<?= $key ?>" <?= (isset($_GET['status']) && $_GET['status'] == $key) ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search fa-sm"></i> Lọc
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>SL SP</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($order['shipping_name']) ?></div>
                                            <small class="text-muted"><?= $order['shipping_phone'] ?></small>
                                        </td>
                                        <td><?= number_format($order['total_price']) ?>đ</td>
                                        <td><?= $order['total_items'] ?></td>
                                        <td>
                                            <span class="badge badge-<?= $status_colors[$order['status']] ?>">
                                                <?= $status_texts[$order['status']] ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-toggle="modal" data-target="#orderModal<?= $order['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="order-detail.php?id=<?= $order['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <!-- Update Status Modal -->
                                    <div class="modal fade" id="orderModal<?= $order['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            Cập nhật trạng thái đơn #<?= $order['id'] ?>
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <div class="form-group">
                                                            <label>Trạng thái mới</label>
                                                            <select class="form-control" name="new_status" required>
                                                                <?php foreach ($status_texts as $key => $text): ?>
                                                                    <option value="<?= $key ?>" 
                                                                            <?= $order['status'] == $key ? 'selected' : '' ?>>
                                                                        <?= $text ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Ghi chú</label>
                                                            <textarea class="form-control" name="note" rows="3"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                            Đóng
                                                        </button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">
                                                            Cập nhật
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Không có đơn hàng nào</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
