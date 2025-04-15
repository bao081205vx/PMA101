<?php
require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../templates/sidebar.php'; ?>
        
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Quản lý người dùng</h2>
                <button class="btn btn-primary" onclick="openUserModal()">
                    ➕ Thêm người dùng mới
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Tên người dùng</th>
                            <th width="20%">Email</th>
                            <th width="15%">Số điện thoại</th>
                            <th width="10%">Vai trò</th>
                            <th width="15%">Ngày tạo</th>
                            <th width="20%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Modal Thêm/Sửa người dùng -->
            <div class="modal fade" id="userModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Thêm / Sửa Người dùng</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="userForm">
                                <input type="hidden" id="userId">
                                <div class="mb-3">
                                    <label class="form-label">Họ tên</label>
                                    <input type="text" class="form-control" id="userName" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="userEmail" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu</label>
                                    <input type="password" class="form-control" id="userPassword">
                                    <small class="text-muted">Để trống nếu không muốn thay đổi mật khẩu</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Điện thoại</label>
                                    <input type="tel" class="form-control" id="userPhone">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Vai trò</label>
                                    <select class="form-select" id="userRole" required>
                                        <option value="user">Người dùng</option>
                                        <option value="admin">Quản trị viên</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select class="form-select" id="userStatus" required>
                                        <option value="1">Hoạt động</option>
                                        <option value="0">Khóa</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Địa chỉ</label>
                                    <textarea class="form-control" id="userAddress" rows="3"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-primary" onclick="saveUser()">Lưu</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Biến để lưu trữ danh sách người dùng
let users = [];
let currentPage = 1;
let totalPages = 1;

// Hàm tải danh sách người dùng
async function loadUsers(page = 1) {
    try {
        currentPage = page;
        const response = await fetch(`/lotso/api/users?page=${page}`);
        const result = await response.json();
        
        if (result.success) {
            const users = result.data.users;
            const tableBody = document.getElementById('usersTableBody');
            tableBody.innerHTML = '';
            
            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.phone || 'Chưa cập nhật'}</td>
                    <td>
                        <span class="badge ${user.role === 'admin' ? 'bg-danger' : 'bg-primary'}">
                            ${user.role === 'admin' ? 'Admin' : 'Người dùng'}
                        </span>
                    </td>
                    <td>${new Date(user.created_at).toLocaleDateString('vi-VN')}</td>
                    <td>
                        <button class="btn btn-sm btn-primary me-1" onclick="editUser(${user.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" ${user.role === 'admin' ? 'disabled' : ''}>
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
        showToast('error', 'Lỗi', 'Không thể tải danh sách người dùng');
    }
}

// Hàm mở modal thêm/sửa người dùng
async function openUserModal(id = null) {
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const form = document.getElementById('userForm');
    form.reset();
    document.getElementById('userId').value = '';
    document.getElementById('userPassword').required = !id; // Bắt buộc nhập mật khẩu khi thêm mới

    if (id) {
        try {
            const response = await fetch(`/lotso/api/users/${id}`);
            const result = await response.json();
            if (result.success) {
                const user = result.data;
                document.getElementById('userId').value = user.id;
                document.getElementById('userName').value = user.name;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userPhone').value = user.phone || '';
                document.getElementById('userRole').value = user.role;
                document.getElementById('userStatus').value = user.status;
                document.getElementById('userAddress').value = user.address || '';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin người dùng');
            return;
        }
    }

    modal.show();
}

// Hàm lưu người dùng
async function saveUser() {
    const id = document.getElementById('userId').value;
    const data = {
        name: document.getElementById('userName').value,
        email: document.getElementById('userEmail').value,
        phone: document.getElementById('userPhone').value,
        role: document.getElementById('userRole').value,
        status: document.getElementById('userStatus').value,
        address: document.getElementById('userAddress').value
    };

    const password = document.getElementById('userPassword').value;
    if (password) {
        data.password = password;
    }

    try {
        const response = await fetch(`/lotso/api/users${id ? '/' + id : ''}`, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
            alert(id ? 'Cập nhật người dùng thành công!' : 'Thêm người dùng thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lưu người dùng');
    }
}

// Hàm xóa người dùng
async function deleteUser(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
        return;
    }

    try {
        const response = await fetch(`/lotso/api/users/${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (result.success) {
            loadUsers();
            alert('Xóa người dùng thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa người dùng');
    }
}

// Hàm xem chi tiết người dùng
async function showUser(id) {
    try {
        const response = await fetch(`/lotso/api/users/${id}`);
        const result = await response.json();
        if (result.success) {
            const user = result.data;
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPhone').value = user.phone || '';
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            document.getElementById('userAddress').value = user.address || '';
            
            // Disable all inputs for view mode
            const inputs = document.querySelectorAll('#userForm input, #userForm select, #userForm textarea');
            inputs.forEach(input => input.disabled = true);
            
            // Hide save button
            document.querySelector('#userModal .modal-footer .btn-primary').style.display = 'none';
            
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải thông tin người dùng');
    }
}

// Tải dữ liệu khi trang được load
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
});
</script>

<?php require_once '../templates/footer.php'; ?>
