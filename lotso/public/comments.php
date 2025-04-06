<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="container-fluid">
    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý bình luận</h2>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Người dùng</th>
                        <th width="15%">Sản phẩm</th>
                        <th>Nội dung</th>
                        <th width="15%">Ngày tạo</th>
                        <th width="15%">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="commentsTableBody">
                    <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Modal Xem chi tiết bình luận -->
        <div class="modal fade" id="commentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Chi tiết bình luận</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Người dùng:</strong>
                            <span id="modalUserName"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Sản phẩm:</strong>
                            <span id="modalProductName"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Nội dung:</strong>
                            <p id="modalContent"></p>
                        </div>
                        <div class="mb-3">
                            <strong>Ngày tạo:</strong>
                            <span id="modalCreatedAt"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-danger" onclick="deleteComment()">Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let comments = [];
let pagination = {
    page: 1,
    limit: 10,
    total: 0,
    total_pages: 0
};

// Hàm tải danh sách bình luận
async function loadComments(page = 1) {
    try {
        const response = await fetch(`/lotso/api/comments?page=${page}&limit=${pagination.limit}`);
        const result = await response.json();
        if (result.success) {
            comments = result.data.data;
            pagination = result.data.pagination;
            displayComments();
            displayPagination();
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải danh sách bình luận');
    }
}

// Hàm hiển thị danh sách bình luận
function displayComments() {
    const tbody = document.getElementById('commentsTableBody');
    tbody.innerHTML = comments.map(comment => `
        <tr>
            <td>${comment.id}</td>
            <td>${comment.username}</td>
            <td>${comment.post_title}</td>
            <td>${comment.content.substring(0, 100)}${comment.content.length > 100 ? '...' : ''}</td>
            <td>${new Date(comment.created_at).toLocaleString('vi-VN')}</td>
            <td>
                <button class="btn btn-success btn-sm" onclick="viewComment(${comment.id})">
                    Xem
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteComment(${comment.id})">
                    Xóa
                </button>
            </td>
        </tr>
    `).join('');
}

// Hàm hiển thị phân trang
function displayPagination() {
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'pagination-container mt-3';
    
    let paginationHtml = '<nav><ul class="pagination justify-content-center">';
    
    // Nút Previous
    paginationHtml += `
        <li class="page-item ${pagination.page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadComments(${pagination.page - 1})">&laquo;</a>
        </li>
    `;
    
    // Các nút số trang
    for (let i = 1; i <= pagination.total_pages; i++) {
        paginationHtml += `
            <li class="page-item ${pagination.page === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadComments(${i})">${i}</a>
            </li>
        `;
    }
    
    // Nút Next
    paginationHtml += `
        <li class="page-item ${pagination.page === pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadComments(${pagination.page + 1})">&raquo;</a>
        </li>
    `;
    
    paginationHtml += '</ul></nav>';
    paginationContainer.innerHTML = paginationHtml;
    
    // Thêm phân trang vào sau bảng
    const table = document.querySelector('.table-responsive');
    const existingPagination = document.querySelector('.pagination-container');
    if (existingPagination) {
        existingPagination.remove();
    }
    table.after(paginationContainer);
}

// Hàm xem chi tiết bình luận
async function viewComment(id) {
    try {
        const response = await fetch(`/lotso/api/comments/${id}`);
        const result = await response.json();
        if (result.success) {
            const comment = result.data;
            document.getElementById('modalUserName').innerText = comment.username;
            document.getElementById('modalProductName').innerText = comment.post_title;
            document.getElementById('modalContent').innerText = comment.content;
            document.getElementById('modalCreatedAt').innerText = new Date(comment.created_at).toLocaleString('vi-VN');
            
            const modal = new bootstrap.Modal(document.getElementById('commentModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải thông tin bình luận');
    }
}

// Hàm xóa bình luận
async function deleteComment(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
        return;
    }

    try {
        const response = await fetch(`/lotso/api/comments/${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (result.success) {
            loadComments(pagination.page);
            alert('Xóa bình luận thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa bình luận');
    }
}

// Tải dữ liệu khi trang được load
document.addEventListener('DOMContentLoaded', () => {
    loadComments();
});
</script>

<?php require_once '../templates/footer.php'; ?>
