<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý sản phẩm</h2>
            <button class="btn btn-primary" onclick="openProductModal()">
                <i class="fas fa-plus"></i> Thêm sản phẩm mới
            </button>
        </div>

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="categoryFilter">Lọc theo danh mục:</label>
                            <select class="form-select" id="categoryFilter" onchange="filterProducts()">
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
                                <th class="text-center" width="5%">ID</th>
                                <th width="25%">Tên sản phẩm</th>
                                <th width="15%">Danh mục</th>
                                <th class="text-end" width="15%">Giá</th>
                                <th class="text-center" width="10%">Số lượng</th>
                                <th width="15%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
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

        <!-- Modal Thêm/Sửa sản phẩm -->
        <div class="modal fade" id="productModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productModalTitle">Thêm sản phẩm mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="productForm" enctype="multipart/form-data">
                            <input type="hidden" id="productId">
                            <div class="mb-3">
                                <label class="form-label">Tên sản phẩm</label>
                                <input type="text" class="form-control" id="productName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select class="form-select" id="productCategory" name="category_id" required>
                                    <!-- Danh mục sẽ được thêm vào đây bằng JavaScript -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giá</label>
                                <input type="number" class="form-control" id="productPrice" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số lượng</label>
                                <input type="number" class="form-control" id="productQuantity" name="quantity" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh</label>
                                <input type="file" class="form-control" id="productImage" name="image" accept="image/*">
                                <div id="currentImage" class="mt-2"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" id="productDescription" name="description" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" onclick="saveProduct()">Lưu</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Xem chi tiết sản phẩm -->
        <div class="modal fade" id="viewProductModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Chi tiết sản phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="fw-bold">Tên sản phẩm:</label>
                            <p id="viewProductName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Danh mục:</label>
                            <p id="viewProductCategory"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Giá:</label>
                            <p id="viewProductPrice"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Số lượng:</label>
                            <p id="viewProductQuantity"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Hình ảnh:</label>
                            <div id="viewProductImage"></div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Mô tả:</label>
                            <p id="viewProductDescription"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Xác nhận xóa -->
        <div class="modal fade" id="deleteProductModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bạn có chắc chắn muốn xóa sản phẩm này không?</p>
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
let selectedProductId = null;

// Hàm tải danh sách sản phẩm
async function loadProducts(page = 1, categoryId = '') {
    try {
        currentPage = page;
        const url = new URL('/lotso/api/products', window.location.origin);
        url.searchParams.append('page', page);
        if (categoryId) {
            url.searchParams.append('category_id', categoryId);
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            const products = result.data.products;
            const tableBody = document.getElementById('productsTableBody');
            tableBody.innerHTML = '';
            
            products.forEach(product => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">${product.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${product.image ? 
                                `<img src="/lotso/uploads/${product.image}" class="rounded me-2" style="width: 50px; height: 50px; object-fit: cover;">` : 
                                '<div class="bg-light rounded me-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>'
                            }
                            <span>${product.name}</span>
                        </div>
                    </td>
                    <td>${product.category_name || 'Chưa phân loại'}</td>
                    <td class="text-end">${formatPrice(product.price)}</td>
                    <td class="text-center">${product.quantity}</td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info" onclick="viewProduct(${product.id})" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="editProduct(${product.id})" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})" title="Xóa">
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
        showToast('error', 'Lỗi', 'Không thể tải danh sách sản phẩm');
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
            const modalSelect = document.getElementById('productCategory');
            
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

// Hàm định dạng tiền tệ
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
}

// Hàm mở modal thêm sản phẩm mới
function openProductModal() {
    document.getElementById('productId').value = '';
    document.getElementById('productForm').reset();
    document.getElementById('currentImage').innerHTML = '';
    document.getElementById('productModalTitle').textContent = 'Thêm sản phẩm mới';
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

// Hàm xem chi tiết sản phẩm
async function viewProduct(id) {
    try {
        const response = await fetch(`/lotso/api/products?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const product = result.data;
            
            document.getElementById('viewProductName').textContent = product.name;
            document.getElementById('viewProductCategory').textContent = product.category_name || 'Chưa phân loại';
            document.getElementById('viewProductPrice').textContent = formatPrice(product.price);
            document.getElementById('viewProductQuantity').textContent = product.quantity;
            document.getElementById('viewProductDescription').textContent = product.description || 'Không có mô tả';
            
            const imageContainer = document.getElementById('viewProductImage');
            if (product.image) {
                imageContainer.innerHTML = `<img src="/lotso/uploads/${product.image}" class="img-fluid rounded" alt="${product.name}">`;
            } else {
                imageContainer.innerHTML = '<p class="text-muted">Không có hình ảnh</p>';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
            modal.show();
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể tải thông tin sản phẩm');
    }
}

// Hàm chỉnh sửa sản phẩm
async function editProduct(id) {
    try {
        const response = await fetch(`/lotso/api/products?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const product = result.data;
            
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productCategory').value = product.category_id || '';
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productQuantity').value = product.quantity;
            document.getElementById('productDescription').value = product.description || '';
            
            const currentImage = document.getElementById('currentImage');
            if (product.image) {
                currentImage.innerHTML = `
                    <img src="/lotso/uploads/${product.image}" class="img-thumbnail mt-2" style="height: 100px;" alt="${product.name}">
                `;
            } else {
                currentImage.innerHTML = '';
            }
            
            document.getElementById('productModalTitle').textContent = 'Chỉnh sửa sản phẩm';
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể tải thông tin sản phẩm');
    }
}

// Hàm lưu sản phẩm
async function saveProduct() {
    const form = document.getElementById('productForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const id = document.getElementById('productId').value;
    const formData = new FormData(form);
    
    try {
        const response = await fetch(`/lotso/api/products${id ? '?id=' + id : ''}`, {
            method: id ? 'PUT' : 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            modal.hide();
            loadProducts(currentPage);
            showToast('success', 'Thành công', result.message);
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể lưu sản phẩm');
    }
}

// Hàm xóa sản phẩm
function deleteProduct(id) {
    selectedProductId = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
    modal.show();
}

// Hàm xác nhận xóa sản phẩm
async function confirmDelete() {
    if (!selectedProductId) return;
    
    try {
        const response = await fetch(`/lotso/api/products?id=${selectedProductId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteProductModal'));
            modal.hide();
            loadProducts(currentPage);
            showToast('success', 'Thành công', result.message);
        } else {
            showToast('error', 'Lỗi', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể xóa sản phẩm');
    }
    
    selectedProductId = null;
}

// Hàm cập nhật phân trang
function updatePagination() {
    const pagination = document.getElementById('pagination');
    let html = '';
    
    // Nút Previous
    html += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadProducts(${currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Các nút số trang
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${currentPage === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadProducts(${i})">${i}</a>
            </li>
        `;
    }
    
    // Nút Next
    html += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadProducts(${currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    pagination.innerHTML = html;
}

// Hàm lọc sản phẩm
function filterProducts() {
    const categoryId = document.getElementById('categoryFilter').value;
    loadProducts(1, categoryId);
}

// Khởi tạo trang
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});
</script>

<?php require_once '../templates/footer.php'; ?>