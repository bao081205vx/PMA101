<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
require_once '../functions/database.php';

// Khởi tạo kết nối database
$database = new Database();
$conn = $database->getConnection();

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công!";
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật trạng thái đơn hàng: " . $conn->error;
        }
        
        header("Location: orders.php");
        exit();
    }
}

// Lấy danh sách đơn hàng
$query = "SELECT * FROM orders ORDER BY created_at DESC";
$result = $conn->query($query);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Tính tổng doanh thu
$total_revenue = array_sum(array_column($orders, 'total_price'));
?>

<!-- CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
.btn-group {
    display: flex;
    gap: 5px;
}
.card {
    margin-bottom: 1rem;
}
.badge {
    padding: 0.5rem 0.75rem;
}
</style>

<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Quản lý đơn hàng</h1>
            <button class="btn btn-primary" id="exportExcel">
                    <i class="fas fa-file-excel"></i> Xuất Excel
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

        <!-- Thống kê đơn hàng -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tổng doanh thu</h5>
                        <p class="card-text"><?= number_format($total_revenue, 0, ',', '.') ?> đ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Đơn hàng hoàn thành</h5>
                        <p class="card-text"><?= count(array_filter($orders, fn($o) => $o['status'] === 'completed')) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Đơn hàng đang xử lý</h5>
                        <p class="card-text"><?= count(array_filter($orders, fn($o) => $o['status'] === 'processing')) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Đơn hàng đã hủy</h5>
                        <p class="card-text"><?= count(array_filter($orders, fn($o) => $o['status'] === 'canceled')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách đơn hàng -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= number_format($order['total_price'], 0, ',', '.') ?> đ</td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo match($order['status']) {
                                            'pending' => 'secondary',
                                            'processing' => 'warning',
                                            'completed' => 'success',
                                            'canceled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php
                                        echo match($order['status']) {
                                            'pending' => 'Chờ xử lý',
                                            'processing' => 'Đang xử lý',
                                            'completed' => 'Hoàn thành',
                                            'canceled' => 'Đã hủy',
                                            default => 'Không xác định'
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-order" data-id="<?= $order['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary edit-status" 
                                                data-id="<?= $order['id'] ?>"
                                                data-status="<?= $order['status'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($order['status'] !== 'completed' && $order['status'] !== 'canceled'): ?>
                                        <button type="button" class="btn btn-sm btn-danger cancel-order" data-id="<?= $order['id'] ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
            </div>
        </div>

<!-- Modal Cập nhật trạng thái -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                <h5 class="modal-title">Cập nhật trạng thái đơn hàng</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
            <form action="" method="POST">
                    <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="update_order_id">
                    <div class="mb-3">
                        <label class="form-label">Trạng thái mới</label>
                        <select name="status" id="update_status" class="form-select" required>
                            <option value="pending">Chờ xử lý</option>
                            <option value="processing">Đang xử lý</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="canceled">Đã hủy</option>
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

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Xử lý cập nhật trạng thái
    $('.edit-status').click(function() {
        var orderId = $(this).data('id');
        var currentStatus = $(this).data('status');
        
        $('#update_order_id').val(orderId);
        $('#update_status').val(currentStatus);
        
        new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    });

    // Xử lý hủy đơn hàng
    $('.cancel-order').click(function() {
        if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
            var orderId = $(this).data('id');
            $('#update_order_id').val(orderId);
            $('#update_status').val('canceled');
            $('form[action=""][method="POST"]').submit();
        }
    });

    // Xử lý xuất Excel
    $('#exportExcel').click(function() {
        window.location.href = 'export_orders.php' + window.location.search;
    });
    });
</script>

<?php require_once '../templates/footer.php'; ?>
