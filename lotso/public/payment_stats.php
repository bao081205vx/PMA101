<?php
include_once '../templates/header.php';
include_once '../functions/stats_functions.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$stats = getPaymentStats($startDate, $endDate);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Thống kê doanh thu</h2>
        <a href="export_report.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="btn btn-success">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </a>
    </div>

    <!-- Form lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" name="start_date" 
                           value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" name="end_date" 
                           value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tổng quan -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Tổng doanh thu</div>
                            <div class="fs-4">
                                <?php echo number_format($stats['summary']['total_amount']); ?> VNĐ
                            </div>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Tổng giao dịch</div>
                            <div class="fs-4">
                                <?php echo number_format($stats['summary']['total_transactions']); ?>
                            </div>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Số lượng hoàn tiền</div>
                            <div class="fs-4">
                                <?php echo number_format($stats['summary']['total_refunded']); ?>
                            </div>
                        </div>
                        <i class="fas fa-undo fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Tổng tiền hoàn</div>
                            <div class="fs-4">
                                <?php echo number_format($stats['summary']['total_refunded_amount']); ?> VNĐ
                            </div>
                        </div>
                        <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Thống kê theo phương thức -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Doanh thu theo phương thức thanh toán
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Thống kê theo ngày -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Doanh thu theo ngày
                </div>
                <div class="card-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê theo tháng -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar me-1"></i>
            So sánh doanh thu theo tháng
        </div>
        <div class="card-body">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- Chi tiết theo phương thức -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Chi tiết theo phương thức thanh toán
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Phương thức</th>
                            <th>Số giao dịch</th>
                            <th>Tổng tiền</th>
                            <th>Số hoàn tiền</th>
                            <th>Tổng hoàn</th>
                            <th>Doanh thu thực</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_provider'] as $provider): ?>
                        <tr>
                            <td><?php echo strtoupper($provider['provider']); ?></td>
                            <td><?php echo number_format($provider['total_transactions']); ?></td>
                            <td><?php echo number_format($provider['total_amount']); ?> VNĐ</td>
                            <td><?php echo number_format($provider['refunded_count']); ?></td>
                            <td><?php echo number_format($provider['refunded_amount']); ?> VNĐ</td>
                            <td><?php echo number_format($provider['total_amount'] - $provider['refunded_amount']); ?> VNĐ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dữ liệu cho biểu đồ phương thức thanh toán
    const methodData = {
        labels: <?php echo json_encode(array_column($stats['by_provider'], 'provider')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($stats['by_provider'], 'total_amount')); ?>,
            backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545']
        }]
    };

    // Dữ liệu cho biểu đồ doanh thu theo ngày
    const revenueData = {
        labels: <?php echo json_encode(array_column($stats['by_date'], 'date')); ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?php echo json_encode(array_column($stats['by_date'], 'total_amount')); ?>,
            fill: false,
            borderColor: '#0d6efd',
            tension: 0.1
        }]
    };

    // Dữ liệu cho biểu đồ doanh thu theo tháng
    const monthlyData = {
        labels: <?php echo json_encode(array_map(function($month) {
            return date('m/Y', strtotime($month['month'] . '-01'));
        }, $stats['by_month'])); ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?php echo json_encode(array_column($stats['by_month'], 'total_amount')); ?>,
            backgroundColor: '#0d6efd',
            borderColor: '#0d6efd'
        }, {
            label: 'Hoàn tiền',
            data: <?php echo json_encode(array_column($stats['by_month'], 'refunded_amount')); ?>,
            backgroundColor: '#dc3545',
            borderColor: '#dc3545'
        }]
    };

    // Vẽ biểu đồ phương thức thanh toán
    new Chart(document.getElementById('paymentMethodChart'), {
        type: 'pie',
        data: methodData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Vẽ biểu đồ doanh thu theo ngày
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: revenueData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Vẽ biểu đồ doanh thu theo tháng
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: monthlyData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php include_once '../templates/footer.php'; ?>
