<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Trang chủ</h2>
            <div>
                <select class="form-select d-inline-block w-auto" id="chartType" onchange="updateChart()">
                    <option value="week">7 ngày qua</option>
                    <option value="month">30 ngày qua</option>
                    <option value="year">12 tháng qua</option>
                </select>
            </div>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Tổng đơn hàng</h6>
                                <h3 class="card-text mb-0" id="totalOrders">0</h3>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Doanh thu</h6>
                                <h3 class="card-text mb-0" id="totalRevenue">0đ</h3>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Tổng sản phẩm</h6>
                                <h3 class="card-text mb-0" id="totalProducts">0</h3>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Tổng khách hàng</h6>
                                <h3 class="card-text mb-0" id="totalUsers">0</h3>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ và thống kê chi tiết -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Biểu đồ doanh thu</h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadChart('PNG')">
                                    <i class="fas fa-download"></i> PNG
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadChart('PDF')">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Đơn hàng gần đây</h5>
                            <a href="orders.php" class="btn btn-primary btn-sm">
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
                                        <th class="text-end">Tổng tiền</th>
                                        <th class="text-center">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody id="recentOrdersTable">
                                    <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Thêm Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script>
// Khởi tạo biểu đồ
let revenueChart = null;

// Hàm định dạng tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Hàm lấy badge trạng thái
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-warning">Chờ xử lý</span>',
        'processing': '<span class="badge bg-info">Đang xử lý</span>',
        'completed': '<span class="badge bg-success">Hoàn thành</span>',
        'canceled': '<span class="badge bg-danger">Đã hủy</span>'
    };
    return badges[status] || status;
}

// Hàm tải dữ liệu thống kê
async function loadStats() {
    try {
        // Lấy số lượng sản phẩm
        const productsResponse = await fetch('/lotso/api/stats.php?type=products');
        const productsData = await productsResponse.json();
        if (productsData.success) {
            document.getElementById('totalProducts').textContent = productsData.data;
        }

        // Lấy thống kê đơn hàng
        const ordersResponse = await fetch('/lotso/api/stats.php?type=orders');
        const ordersData = await ordersResponse.json();
        if (ordersData.success) {
            const stats = ordersData.data;
            document.getElementById('totalOrders').textContent = stats.total;
        }

        // Lấy số lượng người dùng
        const usersResponse = await fetch('/lotso/api/stats.php?type=users');
        const usersData = await usersResponse.json();
        if (usersData.success) {
            document.getElementById('totalUsers').textContent = usersData.data;
        }

        // Lấy doanh thu
        const revenueResponse = await fetch('/lotso/api/stats.php?type=revenue');
        const revenueData = await revenueResponse.json();
        if (revenueData.success) {
            document.getElementById('totalRevenue').textContent = formatCurrency(revenueData.data);
        }

        // Lấy đơn hàng gần đây
        const recentOrdersResponse = await fetch('/lotso/api/recent_orders');
        const recentOrdersData = await recentOrdersResponse.json();
        if (recentOrdersData.success) {
            const tbody = document.getElementById('recentOrdersTable');
            tbody.innerHTML = recentOrdersData.data.map(order => `
                <tr>
                    <td>#${order.id}</td>
                    <td>
                        <div>${order.username}</div>
                        <small class="text-muted">${order.email}</small>
                    </td>
                    <td class="text-end">${formatCurrency(order.total_price)}</td>
                    <td class="text-center">${getStatusBadge(order.status)}</td>
                </tr>
            `).join('');
        }

        // Cập nhật biểu đồ
        updateChart();
    } catch (error) {
        console.error('Error:', error);
    }
}

// Hàm cập nhật biểu đồ
async function updateChart() {
    try {
        const type = document.getElementById('chartType').value;
        const response = await fetch(`/lotso/api/stats.php?type=revenue_chart&period=${type}`);
        const data = await response.json();

        if (data.success) {
            const chartData = data.data;

            // Hủy biểu đồ cũ nếu tồn tại
            if (revenueChart) {
                revenueChart.destroy();
            }

            // Tạo biểu đồ mới
            const ctx = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Doanh thu',
                        data: chartData.values,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            color: '#000',
                            anchor: 'end',
                            align: 'top',
                            formatter: function(value) {
                                return formatCurrency(value);
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Doanh thu: ' + formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Hàm tải xuống biểu đồ
function downloadChart(type) {
    if (!revenueChart) return;

    const canvas = document.getElementById('revenueChart');
    if (type === 'PNG') {
        const link = document.createElement('a');
        link.download = 'doanh-thu.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    } else if (type === 'PDF') {
        const pdf = new jsPDF();
        const imgData = canvas.toDataURL('image/png');
        pdf.addImage(imgData, 'PNG', 10, 10);
        pdf.save('doanh-thu.pdf');
    }
}

// Tải dữ liệu khi trang được load
document.addEventListener('DOMContentLoaded', loadStats);

// Tải lại dữ liệu mỗi 30 giây
setInterval(loadStats, 30000);
</script>

<?php require_once '../templates/footer.php'; ?>
