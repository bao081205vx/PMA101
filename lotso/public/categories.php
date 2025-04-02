<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="container-fluid">
    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý danh mục</h2>
            <button class="btn btn-primary" onclick="openCategoryModal()">
                ➕ Thêm danh mục mới
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" width="5%">ID</th>
                        <th width="20%">Tên danh mục</th>
                        <th width="15%">Slug</th>
                        <th width="10%">Số sản phẩm</th>
                        <th width="10%">Hình ảnh</th>
                        <th>Mô tả</th>
                        <th class="text-center" width="15%">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="categoriesTableBody">
                    <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Modal Thêm/Sửa Danh mục -->
        <div class="modal fade" id="categoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Thêm / Sửa Danh mục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="categoryForm" enctype="multipart/form-data">
                            <input type="hidden" id="categoryId">
                            <div class="mb-3">
                                <label class="form-label">Tên danh mục</label>
                                <input type="text" class="form-control" id="categoryName" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" class="form-control" id="categorySlug" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh</label>
                                <input type="file" class="form-control" id="categoryImage" accept="image/*">
                                <div id="currentImage" class="mt-2"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" id="categoryDescription" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" onclick="saveCategory()">Lưu</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Biến để lưu trữ danh sách danh mục
let categories = [];

// Hàm tạo slug từ tên
function createSlug(str) {
    str = str.toLowerCase();
    str = str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    str = str.replace(/[đĐ]/g, 'd');
    str = str.replace(/([^0-9a-z-\s])/g, '');
    str = str.replace(/(\s+)/g, '-');
    str = str.replace(/-+/g, '-');
    str = str.replace(/^-+|-+$/g, '');
    return str;
}

// Tự động tạo slug khi nhập tên
document.getElementById('categoryName').addEventListener('input', function() {
    document.getElementById('categorySlug').value = createSlug(this.value);
});

// Hàm tải danh sách danh mục
async function loadCategories() {
    try {
        const response = await fetch('/lotso/api/categories');
        const result = await response.json();
        if (result.success) {
            categories = result.data;
            displayCategories();
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải danh sách danh mục');
    }
}

// Hàm hiển thị danh sách danh mục
function displayCategories() {
    const tbody = document.getElementById('categoriesTableBody');
    tbody.innerHTML = categories.map(category => `
        <tr>
            <td class="text-center">${category.id}</td>
            <td>${category.name}</td>
            <td>${category.slug}</td>
            <td class="text-center">${category.product_count || 0}</td>
            <td>
                ${category.image ? `<img src="/lotso/uploads/${category.image}" width="80" alt="${category.name}">` : 'Không có ảnh'}
            </td>
            <td>${category.description || ''}</td>
            <td class="text-center">
                <button class="btn btn-success btn-sm" onclick="showCategory(${category.id})">
                    👁️ Xem
                </button>
                <button class="btn btn-warning btn-sm" onclick="openCategoryModal(${category.id})">
                    ✏️ Sửa
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteCategory(${category.id})">
                    🗑️ Xóa
                </button>
            </td>
        </tr>
    `).join('');
}

// Hàm mở modal thêm/sửa danh mục
async function openCategoryModal(id = null) {
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const form = document.getElementById('categoryForm');
    form.reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('currentImage').innerHTML = '';

    if (id) {
        try {
            const response = await fetch(`/lotso/api/categories/${id}`);
            const result = await response.json();
            if (result.success) {
                const category = result.data;
                document.getElementById('categoryId').value = category.id;
                document.getElementById('categoryName').value = category.name;
                document.getElementById('categorySlug').value = category.slug;
                document.getElementById('categoryDescription').value = category.description || '';
                if (category.image) {
                    document.getElementById('currentImage').innerHTML = 
                        `<img src="/lotso/uploads/${category.image}" width="100" class="mt-2">`;
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin danh mục');
            return;
        }
    }

    modal.show();
}

// Hàm lưu danh mục
async function saveCategory() {
    const id = document.getElementById('categoryId').value;
    const formData = new FormData();
    formData.append('name', document.getElementById('categoryName').value);
    formData.append('slug', document.getElementById('categorySlug').value);
    formData.append('description', document.getElementById('categoryDescription').value);

    const imageFile = document.getElementById('categoryImage').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    try {
        const response = await fetch(`/lotso/api/categories${id ? '/' + id : ''}`, {
            method: id ? 'PUT' : 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
            loadCategories();
            alert(id ? 'Cập nhật danh mục thành công!' : 'Thêm danh mục thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lưu danh mục');
    }
}

// Hàm xóa danh mục
async function deleteCategory(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa danh mục này? Tất cả sản phẩm trong danh mục cũng sẽ bị xóa!')) {
        return;
    }

    try {
        const response = await fetch(`/lotso/api/categories/${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (result.success) {
            loadCategories();
            alert('Xóa danh mục thành công!');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa danh mục');
    }
}

// Hàm xem chi tiết danh mục
async function showCategory(id) {
    try {
        const response = await fetch(`/lotso/api/categories/${id}`);
        const result = await response.json();
        if (result.success) {
            const category = result.data;
            const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categorySlug').value = category.slug;
            document.getElementById('categoryDescription').value = category.description || '';
            if (category.image) {
                document.getElementById('currentImage').innerHTML = 
                    `<img src="/lotso/uploads/${category.image}" width="100" class="mt-2">`;
            }
            
            // Disable all inputs for view mode
            const inputs = document.querySelectorAll('#categoryForm input, #categoryForm textarea');
            inputs.forEach(input => input.disabled = true);
            
            // Hide save button
            document.querySelector('#categoryModal .modal-footer .btn-primary').style.display = 'none';
            
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải thông tin danh mục');
    }
}

// Tải dữ liệu khi trang được load
document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
});
</script>

<?php require_once '../templates/footer.php'; ?>
