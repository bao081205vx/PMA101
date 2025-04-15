<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý đơn hàng</h2>
            <div>
                <select class="form-select d-inline-block w-auto me-2" id="orderStatus" onchange="filterOrders()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending">Chờ xử lý</option>
                    <option value="processing">Đang xử lý</option>
                    <option value="completed">Hoàn thành</option>
                    <option value="canceled">Đã hủy</option>
                </select>
                <button class="btn btn-primary" onclick="exportOrders()">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" width="5%">ID</th>
                                <th width="20%">Khách hàng</th>
                                <th width="15%">Tổng tiền</th>
                                <th width="15%">Trạng thái</th>
                                <th width="15%">Ngày đặt</th>
                                <th class="text-center" width="15%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center" id="pagination">
                        <!-- Các nút phân trang sẽ được thêm vào đây bằng JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Modal Chi tiết đơn hàng -->
        <div class="modal fade" id="orderModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Chi tiết đơn hàng #<span id="orderNumber"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Thông tin khách hàng</h6>
                                <p><strong>Tên:</strong> <span id="customerName"></span></p>
                                <p><strong>Email:</strong> <span id="customerEmail"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Thông tin đơn hàng</h6>
                                <p><strong>Ngày đặt:</strong> <span id="orderDate"></span></p>
                                <p><strong>Trạng thái:</strong> <span id="orderStatusBadge"></span></p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <h6>Sản phẩm</h6>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">Số lượng</th>
                                            <th class="text-end">Đơn giá</th>
                                            <th class="text-end">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orderItems">
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Tổng tiền:</strong></td>
                                            <td class="text-end" id="totalPrice"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <div id="orderActions"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;

    async function loadOrders(page = 1, status = '') {
        try {
            currentPage = page;
            const url = new URL('/lotso/api/orders', window.location.origin);
            url.searchParams.append('page', page);
            if (status) {
                url.searchParams.append('status', status);
            }
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                const orders = result.data.orders;
                const tableBody = document.getElementById('ordersTableBody');
                tableBody.innerHTML = '';
                
                orders.forEach(order => {
                    const row = document.createElement('tr');
                    const statusClass = getStatusClass(order.status);
                    row.innerHTML = `
                        <td>${order.id}</td>
                        <td>${order.username}</td>
                        <td>${formatPrice(order.total_price)}</td>
                        <td>
                            <span class="badge ${statusClass}">
                                ${getStatusText(order.status)}
                            </span>
                        </td>
                        <td>${new Date(order.created_at).toLocaleDateString('vi-VN')}</td>
                        <td>
                            <button class="btn btn-sm btn-info me-1" onclick="viewOrder(${order.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary me-1" onclick="editOrder(${order.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteOrder(${order.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });

                // Cập nhật phân trang
                if (result.data.pagination) {
                    totalPages = result.data.pagination.total_pages;
                    updatePagination();
                }
            } else {
                showToast('error', 'Lỗi', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Lỗi', 'Không thể tải danh sách đơn hàng');
        }
    }

    function getStatusClass(status) {
        switch (status) {
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

    function getStatusText(status) {
        switch (status) {
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

    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(price);
    }

    function updatePagination() {
        const paginationContainer = document.getElementById('pagination');
        paginationContainer.innerHTML = '';
        
        // Nút Previous
        const prevButton = document.createElement('li');
        prevButton.className = 'page-item ' + (currentPage === 1 ? 'disabled' : '');
        prevButton.innerHTML = `
            <a class="page-link" href="#" onclick="loadOrders(${currentPage - 1})">&laquo;</a>
        `;
        paginationContainer.appendChild(prevButton);
        
        // Các nút số trang
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('li');
            pageButton.className = 'page-item ' + (currentPage === i ? 'active' : '');
            pageButton.innerHTML = `
                <a class="page-link" href="#" onclick="loadOrders(${i})">${i}</a>
            `;
            paginationContainer.appendChild(pageButton);
        }
        
        // Nút Next
        const nextButton = document.createElement('li');
        nextButton.className = 'page-item ' + (currentPage === totalPages ? 'disabled' : '');
        nextButton.innerHTML = `
            <a class="page-link" href="#" onclick="loadOrders(${currentPage + 1})">&raquo;</a>
        `;
        paginationContainer.appendChild(nextButton);
    }

    function filterOrders() {
        const status = document.getElementById('orderStatus').value;
        loadOrders(1, status);
    }

    function exportOrders() {
        const status = document.getElementById('orderStatus').value;
        window.location.href = `/lotso/api/orders/export${status ? '?status=' + status : ''}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadOrders();
    });
</script>

<?php require_once '../templates/footer.php'; ?>
