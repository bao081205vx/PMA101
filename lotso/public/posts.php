<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý bài viết</h2>
            <button class="btn btn-primary" onclick="openPostModal()">
                <i class="fas fa-plus"></i> Thêm bài viết mới
            </button>
        </div>

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="categoryFilter">Lọc theo danh mục:</label>
                            <select class="form-select" id="categoryFilter" onchange="filterPosts()">
                                <option value="">Tất cả danh mục</option>
                                <!-- Danh mục sẽ được thêm vào đây bằng JavaScript -->
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">ID</th>
                                <th width="25%">Tiêu đề</th>
                                <th width="15%">Danh mục</th>
                                <th width="15%">Tác giả</th>
                                <th width="10%">Lượt xem</th>
                                <th width="10%">Trạng thái</th>
                                <th width="20%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="postsTableBody">
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

        <!-- Modal Thêm/Sửa bài viết -->
        <div class="modal fade" id="postModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="postModalTitle">Thêm bài viết mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="postForm">
                            <input type="hidden" id="postId">
                            <div class="mb-3">
                                <label class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="postTitle" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select class="form-select" id="postCategory" name="category_id" required>
                                    <!-- Danh mục sẽ được thêm vào đây bằng JavaScript -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nội dung</label>
                                <textarea class="form-control" id="postContent" name="content" rows="10" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-select" id="postStatus" name="status" required>
                                    <option value="draft">Nháp</option>
                                    <option value="published">Công khai</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" onclick="savePost()">Lưu</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Xem chi tiết bài viết -->
        <div class="modal fade" id="viewPostModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Chi tiết bài viết</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="fw-bold">Tiêu đề:</label>
                            <p id="viewPostTitle"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Danh mục:</label>
                            <p id="viewPostCategory"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Tác giả:</label>
                            <p id="viewPostAuthor"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Lượt xem:</label>
                            <p id="viewPostViews"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Trạng thái:</label>
                            <p id="viewPostStatus"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Nội dung:</label>
                            <div id="viewPostContent" class="border rounded p-3 bg-light"></div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Ngày tạo:</label>
                            <p id="viewPostCreatedAt"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Xác nhận xóa -->
        <div class="modal fade" id="deletePostModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bạn có chắc chắn muốn xóa bài viết này không?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let selectedPostId = null;

// Hàm tải danh sách bài viết
async function loadPosts(page = 1, categoryId = '') {
    try {
        currentPage = page;
        const url = new URL('/lotso/api/posts', window.location.origin);
        url.searchParams.append('page', page);
        if (categoryId) {
            url.searchParams.append('category_id', categoryId);
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            const posts = result.data.posts;
            const tableBody = document.getElementById('postsTableBody');
            tableBody.innerHTML = '';
            
            posts.forEach(post => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${post.id}</td>
                    <td>${post.title}</td>
                    <td>${post.category_name || 'Chưa phân loại'}</td>
                    <td>${post.author_name || 'Không xác định'}</td>
                    <td class="text-center">${post.views || 0}</td>
                    <td class="text-center">
                        <span class="badge ${post.status === 'published' ? 'bg-success' : 'bg-warning'}">
                            ${post.status === 'published' ? 'Công khai' : 'Nháp'}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info" onclick="viewPost(${post.id})" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="editPost(${post.id})" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deletePost(${post.id})" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            // Cập nhật phân trang
            if (result.data.pagination) {
                totalPages = result.data.pagination.total_pages;
                updatePagination();
            }

            // Cập nhật danh mục trong bộ lọc (nếu chưa có)
            if (!document.getElementById('categoryFilter').children.length) {
                loadCategories();
            }
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể tải danh sách bài viết');
    }
}

// Hàm tải danh mục
async function loadCategories() {
    try {
        const response = await fetch('/lotso/api/categories');
        const result = await response.json();
        
        if (result.success) {
            const categories = result.data;
            const filterSelect = document.getElementById('categoryFilter');
            const modalSelect = document.getElementById('postCategory');
            
            // Cập nhật select filter
            filterSelect.innerHTML = '<option value="">Tất cả danh mục</option>' +
                categories.map(category => 
                    `<option value="${category.id}">${category.name}</option>`
                ).join('');
            
            // Cập nhật select trong modal
            modalSelect.innerHTML = '<option value="">Chọn danh mục</option>' +
                categories.map(category => 
                    `<option value="${category.id}">${category.name}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể tải danh mục');
    }
}

// Hàm mở modal thêm bài viết mới
function openPostModal() {
    document.getElementById('postId').value = '';
    document.getElementById('postForm').reset();
    document.getElementById('postModalTitle').textContent = 'Thêm bài viết mới';
    const modal = new bootstrap.Modal(document.getElementById('postModal'));
    modal.show();
}

// Hàm xem chi tiết bài viết
async function viewPost(id) {
    try {
        const response = await fetch(`/lotso/api/posts?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const post = result.data;
            
            document.getElementById('viewPostTitle').textContent = post.title;
            document.getElementById('viewPostCategory').textContent = post.category_name || 'Chưa phân loại';
            document.getElementById('viewPostAuthor').textContent = post.author_name || 'Không xác định';
            document.getElementById('viewPostViews').textContent = post.views || 0;
            document.getElementById('viewPostStatus').textContent = post.status === 'published' ? 'Công khai' : 'Nháp';
            document.getElementById('viewPostContent').innerHTML = post.content;
            document.getElementById('viewPostCreatedAt').textContent = new Date(post.created_at).toLocaleString('vi-VN');
            
            const modal = new bootstrap.Modal(document.getElementById('viewPostModal'));
            modal.show();
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể tải thông tin bài viết');
    }
}

// Hàm chỉnh sửa bài viết
async function editPost(id) {
    try {
        const response = await fetch(`/lotso/api/posts?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const post = result.data;
            
            document.getElementById('postId').value = post.id;
            document.getElementById('postTitle').value = post.title;
            document.getElementById('postCategory').value = post.category_id || '';
            document.getElementById('postContent').value = post.content;
            document.getElementById('postStatus').value = post.status;
            
            document.getElementById('postModalTitle').textContent = 'Chỉnh sửa bài viết';
            const modal = new bootstrap.Modal(document.getElementById('postModal'));
            modal.show();
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể tải thông tin bài viết');
    }
}

// Hàm lưu bài viết
async function savePost() {
    const form = document.getElementById('postForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const id = document.getElementById('postId').value;
    const formData = new FormData(form);
    
    try {
        const response = await fetch(`/lotso/api/posts${id ? '?id=' + id : ''}`, {
            method: id ? 'PUT' : 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('postModal'));
            modal.hide();
            loadPosts(currentPage);
            showToast('success', 'Thành công', result.message);
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể lưu bài viết');
    }
}

// Hàm xóa bài viết
function deletePost(id) {
    selectedPostId = id;
    const modal = new bootstrap.Modal(document.getElementById('deletePostModal'));
    modal.show();
}

// Hàm xác nhận xóa bài viết
async function confirmDelete() {
    if (!selectedPostId) return;
    
    try {
        const response = await fetch(`/lotso/api/posts?id=${selectedPostId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deletePostModal'));
            modal.hide();
            loadPosts(currentPage);
            showToast('success', 'Thành công', result.message);
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể xóa bài viết');
    }
    
    selectedPostId = null;
}

// Hàm cập nhật phân trang
function updatePagination() {
    const pagination = document.getElementById('pagination');
    let html = '';
    
    // Nút Previous
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadPosts(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Các nút số trang
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${currentPage === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadPosts(${i})">${i}</a>
            </li>
        `;
    }
    
    // Nút Next
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadPosts(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    pagination.innerHTML = html;
}

// Hàm lọc bài viết
function filterPosts() {
    const categoryId = document.getElementById('categoryFilter').value;
    loadPosts(1, categoryId);
}

// Khởi tạo trang
document.addEventListener('DOMContentLoaded', () => {
    loadPosts();
});
</script>

<?php require_once '../templates/footer.php'; ?>
