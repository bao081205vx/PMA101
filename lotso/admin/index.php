<?php
require_once '../middleware/auth.php';
checkAdmin(); // Kiểm tra quyền admin trước khi hiển thị trang

require_once '../templates/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="my-4">Bảng điều khiển</h1>
            
            <div class="row">
                <!-- Thống kê đơn hàng -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Đơn hàng</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalOrders">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thống kê doanh thu -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Doanh thu</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRevenue">0đ</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thống kê sản phẩm -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Sản phẩm</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalProducts">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-box fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thống kê người dùng -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Người dùng</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalUsers">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ doanh thu -->
            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Biểu đồ doanh thu</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ đơn hàng -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Trạng thái đơn hàng</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Hàm format tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Load thống kê
async function loadStatistics() {
    try {
        const response = await fetch('/lotso/api/admin/statistics.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalOrders').textContent = data.data.orders.total;
            document.getElementById('totalRevenue').textContent = formatCurrency(data.data.revenue.total);
            document.getElementById('totalProducts').textContent = data.data.products.total;
            document.getElementById('totalUsers').textContent = data.data.users.total;
            
            // Vẽ biểu đồ doanh thu
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: data.data.revenue.labels,
                    datasets: [{
                        label: 'Doanh thu',
                        data: data.data.revenue.data,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                }
            });
            
            // Vẽ biểu đồ trạng thái đơn hàng
            const orderCtx = document.getElementById('orderStatusChart').getContext('2d');
            new Chart(orderCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Đang xử lý', 'Đã xác nhận', 'Đang giao', 'Hoàn thành', 'Đã hủy'],
                    datasets: [{
                        data: [
                            data.data.orders.pending,
                            data.data.orders.confirmed,
                            data.data.orders.shipping,
                            data.data.orders.completed,
                            data.data.orders.cancelled
                        ],
                        backgroundColor: [
                            '#f6c23e',
                            '#4e73df',
                            '#36b9cc',
                            '#1cc88a',
                            '#e74a3b'
                        ]
                    }]
                }
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load thống kê khi trang tải xong
document.addEventListener('DOMContentLoaded', loadStatistics);
</script>

<?php require_once '../templates/admin/footer.php'; ?>
