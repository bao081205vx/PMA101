<?php
session_start();
require_once '../config/Database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra order_id
if (!isset($_GET['id'])) {
    header('Location: profile.php');
    exit;
}

$order_id = intval($_GET['id']);

// Kết nối database
$database = new Database();
$conn = $database->getConnection();

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: profile.php');
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image, p.price
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Định nghĩa trạng thái
$status_texts = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'completed' => 'Đã hoàn thành',
    'canceled' => 'Đã hủy',
    'waiting_payment' => 'Chờ thanh toán'
];

$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'completed' => 'success',
    'canceled' => 'danger',
    'waiting_payment' => 'secondary'
];

$payment_method_texts = [
    'cod' => 'Thanh toán khi nhận hàng (COD)',
    'momo' => 'Ví MoMo',
    'zalopay' => 'Ví ZaloPay'
];

$payment_status_texts = [
    'pending' => 'Chưa thanh toán',
    'completed' => 'Đã thanh toán',
    'failed' => 'Thanh toán thất bại'
];

// Tạo lịch sử đơn hàng dựa trên trạng thái
$history = [];

// Luôn thêm sự kiện tạo đơn hàng
$history[] = [
    'time' => $order['created_at'] ?? date('Y-m-d H:i:s'),
    'status_text' => 'Đơn hàng được tạo',
    'note' => ''
];

// Thêm các sự kiện dựa trên trạng thái hiện tại
switch ($order['status']) {
    case 'processing':
        $history[] = [
            'time' => date('Y-m-d H:i:s'),
            'status_text' => 'Đơn hàng đang được xử lý',
            'note' => ''
        ];
        break;
    case 'completed':
        $history[] = [
            'time' => date('Y-m-d H:i:s'),
            'status_text' => 'Đơn hàng đã hoàn thành',
            'note' => ''
        ];
        break;
    case 'canceled':
        $history[] = [
            'time' => date('Y-m-d H:i:s'),
            'status_text' => 'Đơn hàng đã bị hủy',
            'note' => ''
        ];
        break;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= $order_id ?> - Lotso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: #ff4081;
        --secondary-color: #ff80ab;
        --hover-color: #f50057;
    }

    .btn-primary {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .btn-primary:hover {
        background-color: var(--hover-color) !important;
        border-color: var(--hover-color) !important;
    }

    .text-primary {
        color: var(--primary-color) !important;
    }

    .bg-primary {
        background-color: var(--primary-color) !important;
    }

    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 20px;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-point {
        position: absolute;
        left: 0;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--primary-color);
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px var(--primary-color);
    }

    .timeline-item:not(:last-child):before {
        content: '';
        position: absolute;
        left: 5px;
        top: 12px;
        bottom: -20px;
        width: 2px;
        background: var(--primary-color);
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 4px;
    }
    </style>
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="profile.php">Đơn hàng của tôi</a></li>
                        <li class="breadcrumb-item active">Chi tiết đơn hàng #<?= $order_id ?></li>
                    </ol>
                </nav>

                <!-- Order Status -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Trạng thái đơn hàng</h5>
                            <span class="badge bg-<?= $status_colors[$order['status']] ?>">
                                <?= $status_texts[$order['status']] ?>
                            </span>
                        </div>

                        <!-- Order Timeline -->
                        <div class="timeline">
                            <?php foreach ($history as $event): ?>
                                <div class="timeline-item">
                                    <div class="timeline-point"></div>
                                    <div class="timeline-content">
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($event['time'] ?? date('Y-m-d H:i:s'))) ?></small>
                                        <div><?= htmlspecialchars($event['status_text']) ?></div>
                                        <?php if (!empty($event['note'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($event['note']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Chi tiết sản phẩm</h5>
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex mb-3">
                                <div class="me-3" style="width: 100px; height: 100px;">
                                    <img src="<?= $item['image'] ? '../uploads/' . htmlspecialchars($item['image']) : '../assets/images/no-image.jpg' ?>" 
                                         alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                         class="img-thumbnail w-100 h-100" style="object-fit: cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                                    <div class="text-muted mb-2">
                                        <?= number_format($item['price']) ?> đ x <?= $item['quantity'] ?>
                                    </div>
                                    <div class="text-primary">
                                        <?= number_format($item['price'] * $item['quantity']) ?> đ
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr>

                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tạm tính:</span>
                                    <strong><?= number_format($order['total_price']) ?> đ</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Phí vận chuyển:</span>
                                    <span>Miễn phí</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <strong>Tổng cộng:</strong>
                                    <strong class="text-primary"><?= number_format($order['total_price']) ?> đ</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Thông tin giao hàng</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Người nhận:</strong> <?= htmlspecialchars($order['shipping_name']) ?></p>
                                <p class="mb-1"><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['shipping_phone']) ?></p>
                                <p class="mb-0"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Phương thức thanh toán:</strong>
                                    <?php
                                    $payment_methods = [
                                        'cod' => 'Thanh toán khi nhận hàng',
                                        'bank' => 'Chuyển khoản ngân hàng',
                                        'momo' => 'Ví MoMo',
                                        'zalopay' => 'Ví ZaloPay'
                                    ];
                                    echo isset($payment_methods[$order['payment_method']]) 
                                        ? $payment_methods[$order['payment_method']] 
                                        : 'Không xác định';
                                    ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Trạng thái thanh toán:</strong>
                                    <?php if (isset($order['payment_status']) && $order['payment_status'] == 'completed'): ?>
                                        <span class="badge bg-success">Đã thanh toán</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Chưa thanh toán</span>
                                    <?php endif; ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Ngày đặt hàng:</strong>
                                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger mb-4">
                        <?= $_SESSION['error'] ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="text-center d-flex gap-2 justify-content-center">
                    <?php if ($order['status'] === 'completed'): ?>
                        <button class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Mua lại
                        </button>
                        <a href="review.php?order_id=<?= $order['id'] ?>" class="btn btn-outline-primary">
                            <i class="fas fa-star me-2"></i>Đánh giá sản phẩm
                        </a>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'pending' || $order['status'] === 'waiting_payment'): ?>
                        <button class="btn btn-danger" onclick="cancelOrder(<?= $order_id ?>)">
                            <i class="fas fa-times me-2"></i>Hủy đơn hàng
                        </button>
                        <?php if ($order['payment_method'] !== 'cod'): ?>
                            <a href="process-payment.php?order_id=<?= $order_id ?>" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i>Thanh toán ngay
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cancelOrder(orderId) {
        if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
            fetch('cancel-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đơn hàng đã được hủy thành công');
                    window.location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra khi hủy đơn hàng');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi kết nối đến máy chủ');
            });
        }
    }
    </script>

    <?php require_once 'footer.php'; ?>
</body>
</html>

</html>

</html>

</html>

</html>

</html>
