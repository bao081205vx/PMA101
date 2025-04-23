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
                                            <input type="text" inputmode="decimal" class="form-control" id="discountValue" required>
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
                                            <input type="text" inputmode="numeric" class="form-control" id="discountQuantity" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Giá trị đơn hàng tối thiểu</label>
                                            <input type="text" inputmode="decimal" class="form-control" id="minimumOrder" required>
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
        console.log('Đang tải danh sách mã giảm giá...');
        const response = await fetch('/lotso/api/discounts.php');
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.success) {
            discounts = result.data.discounts || [];
            console.log('Danh sách mã giảm giá:', discounts);
            displayDiscounts();
        } else {
            throw new Error(result.message || 'Không thể tải danh sách mã giảm giá');
        }
    } catch (error) {
        console.error('Error loading discounts:', error);
        const tbody = document.getElementById('discountsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    Có lỗi xảy ra khi tải danh sách mã giảm giá: ${error.message}
                </td>
            </tr>
        `;
    }
}

// Hàm hiển thị danh sách mã giảm giá
function displayDiscounts() {
    const tbody = document.getElementById('discountsTableBody');
    
    if (!Array.isArray(discounts) || discounts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center">
                    <i class="fas fa-info-circle"></i> 
                    Chưa có mã giảm giá nào
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = discounts.map(discount => `
        <tr>
            <td class="text-center">${discount.id}</td>
            <td><code>${discount.code}</code></td>
            <td>${discount.type === 'percentage' ? discount.value + '%' : formatCurrency(discount.value)}</td>
            <td>${discount.type === 'percentage' ? 'Phần trăm' : 'Số tiền'}</td>
            <td class="text-center">${discount.quantity || 0}</td>
            <td class="text-center">${discount.used || 0}</td>
            <td>
                ${formatDateTime(discount.start_date)}<br>
                ${formatDateTime(discount.end_date)}
            </td>
            <td class="text-center">
                ${new Date() < new Date(discount.start_date) 
                    ? '<span class="badge bg-info">Chưa bắt đầu</span>'
                    : new Date() > new Date(discount.end_date)
                        ? '<span class="badge bg-danger">Hết hạn</span>'
                        : discount.quantity <= (discount.used || 0)
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
            const response = await fetch(`/lotso/api/discounts.php?id=${id}`);
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
            console.error('Error loading discount:', error);
            alert('Có lỗi xảy ra khi tải thông tin mã giảm giá');
            return;
        }
    }

    modal.show();
}

// Hàm lưu mã giảm giá
async function saveDiscount() {
    const id = document.getElementById('discountId').value;
    
    // Format ngày tháng sang định dạng Y-m-d H:i:s
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    };

    const startDate = new Date(document.getElementById('startDate').value);
    const endDate = new Date(document.getElementById('endDate').value);

    // Chuyển đổi giá trị từ string sang number và xóa dấu phẩy
    const value = document.getElementById('discountValue').value.replace(/[.,]/g, '');
    const minimumOrder = document.getElementById('minimumOrder').value.replace(/[.,]/g, '');
    const quantity = document.getElementById('discountQuantity').value.replace(/[.,]/g, '');

    const data = {
        code: document.getElementById('discountCode').value.trim(),
        type: document.getElementById('discountType').value,
        value: parseFloat(value),
        quantity: parseInt(quantity),
        minimum_order: parseFloat(minimumOrder),
        start_date: formatDate(startDate),
        end_date: formatDate(endDate),
        description: document.getElementById('discountDescription').value.trim()
    };

    console.log('Data being sent:', JSON.stringify(data, null, 2));

    // Validate dữ liệu trước khi gửi
    if (!data.code) {
        alert('Vui lòng nhập mã giảm giá');
        return;
    }
    if (!data.value || isNaN(data.value) || data.value <= 0) {
        alert('Vui lòng nhập giá trị giảm giá hợp lệ');
        return;
    }
    if (data.type === 'percentage' && (data.value < 0 || data.value > 100)) {
        alert('Giá trị phần trăm phải từ 0 đến 100');
        return;
    }
    if (!data.quantity || isNaN(data.quantity) || data.quantity < 0) {
        alert('Vui lòng nhập số lượng hợp lệ');
        return;
    }
    if (!data.start_date || !data.end_date) {
        alert('Vui lòng chọn thời gian hiệu lực');
        return;
    }
    if (startDate > endDate) {
        alert('Ngày kết thúc phải sau ngày bắt đầu');
        return;
    }

    try {
        const url = id 
            ? `/lotso/api/discounts.php?id=${id}`
            : '/lotso/api/discounts.php';
            
        console.log('API URL:', url);
        
        const response = await fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Error parsing JSON response:', e);
            alert('Lỗi khi xử lý phản hồi từ máy chủ');
            return;
        }

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('discountModal')).hide();
            await loadDiscounts(); // Đợi tải lại danh sách
            alert(id ? 'Cập nhật mã giảm giá thành công!' : 'Thêm mã giảm giá thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Detailed error:', error);
        alert('Có lỗi xảy ra khi lưu mã giảm giá: ' + error.message);
    }
}

// Thêm xử lý format số tiền khi nhập
document.addEventListener('DOMContentLoaded', function() {
    const formatNumber = (input) => {
        // Xóa các ký tự không phải số
        let value = input.value.replace(/[^\d]/g, '');
        
        // Chuyển thành số và format với dấu phẩy
        if (value) {
            value = parseInt(value);
            input.value = new Intl.NumberFormat('vi-VN').format(value);
        }
    };

    // Áp dụng cho các input số
    ['discountValue', 'minimumOrder', 'discountQuantity'].forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener('input', () => formatNumber(input));
    });
});

// Hàm xóa mã giảm giá
async function deleteDiscount(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?')) {
        return;
    }

    try {
        const response = await fetch(`/lotso/api/discounts.php?id=${id}`, {
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
        console.error('Error deleting discount:', error);
        alert('Có lỗi xảy ra khi xóa mã giảm giá');
    }
}

// Hàm xem chi tiết mã giảm giá
async function showDiscount(id) {
    try {
        const response = await fetch(`/lotso/api/discounts.php?id=${id}`);
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
        console.error('Error viewing discount:', error);
        alert('Có lỗi xảy ra khi tải thông tin mã giảm giá');
    }
}

// Tải dữ liệu khi trang được load
document.addEventListener('DOMContentLoaded', () => {
    loadDiscounts();
});
</script>

<?php require_once '../templates/footer.php'; ?>
