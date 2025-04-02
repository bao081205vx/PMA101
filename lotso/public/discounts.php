<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

            <div class="content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Quản lý mã giảm giá</h2>
                    <button class="btn btn-primary" onclick="openDiscountModal()">
                        ➕ Thêm mã giảm giá mới
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-center" width="5%">ID</th>
                                <th width="15%">Mã giảm giá</th>
                                <th width="10%">Giảm giá</th>
                                <th width="10%">Loại</th>
                                <th width="10%">Số lượng</th>
                                <th width="10%">Đã dùng</th>
                                <th width="15%">Thời hạn</th>
                                <th width="10%">Trạng thái</th>
                                <th class="text-center" width="15%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="discountsTableBody">
                            <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Modal Thêm/Sửa mã giảm giá -->
                <div class="modal fade" id="discountModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Thêm / Sửa mã giảm giá</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="discountForm">
                                    <input type="hidden" id="discountId">
                                    <div class="mb-3">
                                        <label class="form-label">Mã giảm giá</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="discountCode" required>
                                            <button type="button" class="btn btn-warning" onclick="generateCode()">
                                                🎲 Tạo mã
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Giá trị giảm</label>
                                            <input type="number" class="form-control" id="discountValue" required min="0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Loại giảm giá</label>
                                            <select class="form-select" id="discountType" required>
                                                <option value="percentage">Phần trăm (%)</option>
                                                <option value="fixed">Số tiền cố định</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Số lượng</label>
                                            <input type="number" class="form-control" id="discountQuantity" required min="1">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Giá trị đơn hàng tối thiểu</label>
                                            <input type="number" class="form-control" id="minimumOrder" required min="0">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Ngày bắt đầu</label>
                                            <input type="datetime-local" class="form-control" id="startDate" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Ngày kết thúc</label>
                                            <input type="datetime-local" class="form-control" id="endDate" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Mô tả</label>
                                        <textarea class="form-control" id="discountDescription" rows="3"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button type="button" class="btn btn-primary" onclick="saveDiscount()">Lưu</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Biến để lưu trữ danh sách mã giảm giá
let discounts = [];

// Hàm định dạng tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Hàm định dạng ngày giờ
function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('vi-VN');
}

// Hàm tạo mã giảm giá ngẫu nhiên
function generateCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('discountCode').value = code;
}

// Hàm tải danh sách mã giảm giá
async function loadDiscounts() {
    try {
        const response = await fetch('/lotso/api/discounts');
        const result = await response.json();
        if (result.success) {
            discounts = result.data;
            displayDiscounts();
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải danh sách mã giảm giá');
    }
}

// Hàm hiển thị danh sách mã giảm giá
function displayDiscounts() {
    const tbody = document.getElementById('discountsTableBody');
    tbody.innerHTML = discounts.map(discount => `
        <tr>
            <td class="text-center">${discount.id}</td>
            <td><code>${discount.code}</code></td>
            <td>${discount.type === 'percentage' ? discount.value + '%' : formatCurrency(discount.value)}</td>
            <td>${discount.type === 'percentage' ? 'Phần trăm' : 'Số tiền'}</td>
            <td class="text-center">${discount.quantity}</td>
            <td class="text-center">${discount.used}</td>
            <td>
                ${formatDateTime(discount.start_date)}<br>
                ${formatDateTime(discount.end_date)}
            </td>
            <td class="text-center">
                ${new Date() < new Date(discount.start_date) 
                    ? '<span class="badge bg-info">Chưa bắt đầu</span>'
                    : new Date() > new Date(discount.end_date)
                        ? '<span class="badge bg-danger">Hết hạn</span>'
                        : discount.quantity <= discount.used
                            ? '<span class="badge bg-warning">Hết lượt</span>'
                            : '<span class="badge bg-success">Đang chạy</span>'
                }
            </td>
            <td class="text-center">
                <button class="btn btn-success btn-sm" onclick="showDiscount(${discount.id})">
                    👁️ Xem
                </button>
                <button class="btn btn-warning btn-sm" onclick="openDiscountModal(${discount.id})">
                    ✏️ Sửa
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteDiscount(${discount.id})">
                    🗑️ Xóa
                </button>
            </td>
        </tr>
    `).join('');
}

// Hàm mở modal thêm/sửa mã giảm giá
async function openDiscountModal(id = null) {
    const modal = new bootstrap.Modal(document.getElementById('discountModal'));
    const form = document.getElementById('discountForm');
    form.reset();
    document.getElementById('discountId').value = '';

    if (id) {
        try {
            const response = await fetch(`/lotso/api/discounts/${id}`);
            const result = await response.json();
            if (result.success) {
                const discount = result.data;
                document.getElementById('discountId').value = discount.id;
                document.getElementById('discountCode').value = discount.code;
                document.getElementById('discountValue').value = discount.value;
                document.getElementById('discountType').value = discount.type;
                document.getElementById('discountQuantity').value = discount.quantity;
                document.getElementById('minimumOrder').value = discount.minimum_order;
                document.getElementById('startDate').value = discount.start_date.slice(0, 16);
                document.getElementById('endDate').value = discount.end_date.slice(0, 16);
                document.getElementById('discountDescription').value = discount.description || '';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin mã giảm giá');
            return;
        }
    }

    modal.show();
}

// Hàm lưu mã giảm giá
async function saveDiscount() {
    const id = document.getElementById('discountId').value;
    const data = {
        code: document.getElementById('discountCode').value,
        value: parseFloat(document.getElementById('discountValue').value),
        type: document.getElementById('discountType').value,
        quantity: parseInt(document.getElementById('discountQuantity').value),
        minimum_order: parseFloat(document.getElementById('minimumOrder').value),
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        description: document.getElementById('discountDescription').value
    };

    try {
        const response = await fetch(`/lotso/api/discounts${id ? '/' + id : ''}`, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('discountModal')).hide();
            loadDiscounts();
            alert(id ? 'Cập nhật mã giảm giá thành công!' : 'Thêm mã giảm giá thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lưu mã giảm giá');
    }
}

// Hàm xóa mã giảm giá
async function deleteDiscount(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?')) {
        return;
    }

    try {
        const response = await fetch(`/lotso/api/discounts/${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (result.success) {
            loadDiscounts();
            alert('Xóa mã giảm giá thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa mã giảm giá');
    }
}

// Hàm xem chi tiết mã giảm giá
async function showDiscount(id) {
    try {
        const response = await fetch(`/lotso/api/discounts/${id}`);
        const result = await response.json();
        if (result.success) {
            const discount = result.data;
            const modal = new bootstrap.Modal(document.getElementById('discountModal'));
            document.getElementById('discountId').value = discount.id;
            document.getElementById('discountCode').value = discount.code;
            document.getElementById('discountValue').value = discount.value;
            document.getElementById('discountType').value = discount.type;
            document.getElementById('discountQuantity').value = discount.quantity;
            document.getElementById('minimumOrder').value = discount.minimum_order;
            document.getElementById('startDate').value = discount.start_date.slice(0, 16);
            document.getElementById('endDate').value = discount.end_date.slice(0, 16);
            document.getElementById('discountDescription').value = discount.description || '';
            
            // Disable all inputs for view mode
            const inputs = document.querySelectorAll('#discountForm input, #discountForm select, #discountForm textarea');
            inputs.forEach(input => input.disabled = true);
            
            // Hide save button
            document.querySelector('#discountModal .modal-footer .btn-primary').style.display = 'none';
            
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải thông tin mã giảm giá');
    }
}

// Tải dữ liệu khi trang được load
document.addEventListener('DOMContentLoaded', () => {
    loadDiscounts();
});
</script>

<?php require_once '../templates/footer.php'; ?>
