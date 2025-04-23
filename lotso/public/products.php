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
        <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productModalTitle">Thêm sản phẩm mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="productForm" enctype="multipart/form-data">
                            <input type="hidden" id="productId" name="id">
                            <div class="mb-3">
                                <label class="form-label">Tên sản phẩm</label>
                                <input type="text" class="form-control" id="productName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select class="form-select" id="productCategory" name="category_id" required>
                                    <option value="">Chọn danh mục</option>
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

// Hàm định dạng tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Hàm tải danh sách sản phẩm
async function loadProducts(page = 1, categoryId = '') {
    try {
        currentPage = page;
        const response = await fetch(`../api/products.php?page=${page}${categoryId ? `&category_id=${categoryId}` : ''}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.success) {
            const products = result.data.products;
            const tableBody = document.getElementById('productsTableBody');
            tableBody.innerHTML = '';
            
            if (!Array.isArray(products)) {
                throw new Error('Products data is not an array');
            }
            
            products.forEach(product => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">${product.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${product.image ? 
                                `<img src="../uploads/${product.image}" alt="${product.name}" class="product-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">` 
                                : '<div class="no-image me-2">No image</div>'
                            }
                            <span>${product.name}</span>
                        </div>
                    </td>
                    <td>${product.category_name || 'Chưa phân loại'}</td>
                    <td class="text-end">${formatCurrency(product.price)}</td>
                    <td class="text-center">${product.quantity}</td>
                    <td>
                        <button class="btn btn-info btn-sm" onclick="viewProduct(${product.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="editProduct(${product.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${product.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
            
            // Cập nhật phân trang
            if (result.data.pagination) {
                updatePagination(result.data.pagination);
            } else {
                console.warn('Pagination data is missing');
            }
        } else {
            throw new Error(result.message || 'Unknown error occurred');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showToast('danger', 'Lỗi', `Không thể tải danh sách sản phẩm: ${error.message}`);
        
        // Clear table body and show error message
        const tableBody = document.getElementById('productsTableBody');
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    Có lỗi xảy ra khi tải danh sách sản phẩm
                </td>
            </tr>
        `;
    }
}

// Hàm tải danh mục
async function loadCategories() {
    try {
        const response = await fetch('../api/categories.php?type=product');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Categories API Response:', result);
        
        if (result.success && Array.isArray(result.data)) {
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
        } else {
            console.error('API Error:', result.message);
            showToast('error', 'Lỗi', result.message || 'Không thể tải danh mục');
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        showToast('error', 'Lỗi', 'Không thể tải danh mục: ' + error.message);
    }
}

// Hàm mở modal thêm sản phẩm mới
async function openProductModal() {
    try {
        // Tải danh mục trước khi mở modal
        await loadCategories();
        
        document.getElementById('productId').value = '';
        document.getElementById('productForm').reset();
        document.getElementById('currentImage').innerHTML = '';
        document.getElementById('productModalTitle').textContent = 'Thêm sản phẩm mới';
        
        const modalElement = document.getElementById('productModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Không thể mở form thêm sản phẩm');
    }
}

// Hàm xem chi tiết sản phẩm
async function viewProduct(id) {
    try {
        const response = await fetch(`/lotso/api/products.php?id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const product = result.data;
            document.getElementById('viewProductName').textContent = product.name;
            document.getElementById('viewProductCategory').textContent = product.category_name || 'Chưa phân loại';
            document.getElementById('viewProductPrice').textContent = formatCurrency(product.price);
            document.getElementById('viewProductQuantity').textContent = product.quantity;
            document.getElementById('viewProductDescription').textContent = product.description || 'Không có mô tả';
            
            const imageContainer = document.getElementById('viewProductImage');
            if (product.image) {
                imageContainer.innerHTML = `<img src="/lotso/uploads/${product.image}" alt="${product.name}" class="img-fluid">`;
            } else {
                imageContainer.innerHTML = '<div class="no-image">Không có hình ảnh</div>';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
            modal.show();
        } else {
            alert('Không thể tải thông tin sản phẩm');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải thông tin sản phẩm');
    }
}

// Hàm chỉnh sửa sản phẩm
async function editProduct(id) {
    try {
        // Show loading state
        showToast('info', 'Đang tải', 'Đang tải thông tin sản phẩm...');
        
        // Load categories first
        await loadCategories();
        
        // Then load product data
        const response = await fetch(`/lotso/api/products.php?id=${id}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success) {
            const product = data.data;
            
            // Set form values
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name || '';
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productPrice').value = product.price || '';
            document.getElementById('productQuantity').value = product.quantity || '';
            document.getElementById('productCategory').value = product.category_id || '';
            
            // Handle image preview
            const currentImage = document.getElementById('currentImage');
            currentImage.innerHTML = '';
            
            if (product.image) {
                const img = document.createElement('img');
                img.src = `/lotso/uploads/${product.image}`;
                img.className = 'img-thumbnail mt-2';
                img.style.maxHeight = '200px';
                currentImage.appendChild(img);
                
                // Add hidden input for current image
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'current_image';
                input.value = product.image;
                currentImage.appendChild(input);
            }
            
            // Update modal title
            document.getElementById('productModalTitle').textContent = 'Chỉnh sửa sản phẩm';
            
            // Show modal
            const productModal = new bootstrap.Modal(document.getElementById('productModal'));
            productModal.show();
        } else {
            showToast('danger', 'Lỗi', data.message || 'Không thể tải thông tin sản phẩm');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('danger', 'Lỗi', 'Không thể tải thông tin sản phẩm: ' + error.message);
    }
}

// Hàm lưu sản phẩm
function saveProduct() {
    const form = document.getElementById('productForm');
    const formData = new FormData(form);
    const productId = formData.get('id');
    
    // Validate required fields
    const requiredFields = ['name', 'price', 'quantity', 'category_id'];
    for (const field of requiredFields) {
        if (!formData.get(field)) {
            showToast('danger', 'Lỗi', `Vui lòng nhập ${field === 'category_id' ? 'danh mục' : field}`);
            return;
        }
    }
    
    // Validate price and quantity are numbers
    const price = parseFloat(formData.get('price'));
    const quantity = parseInt(formData.get('quantity'));
    if (isNaN(price) || price <= 0) {
        showToast('danger', 'Lỗi', 'Giá sản phẩm phải lớn hơn 0');
        return;
    }
    if (isNaN(quantity) || quantity < 0) {
        showToast('danger', 'Lỗi', 'Số lượng sản phẩm không hợp lệ');
        return;
    }
    
    // Show loading state
    showToast('info', 'Đang lưu', 'Đang lưu thông tin sản phẩm...');
    
    const method = productId ? 'PUT' : 'POST';
    const url = `/lotso/api/products.php${productId ? `?id=${productId}` : ''}`;
    
    // For PUT requests, we need to send data as form data
    if (method === 'PUT') {
        // Add _method=PUT to form data
        formData.append('_method', 'PUT');
        
        fetch(url, {
            method: 'POST', // Always use POST for form data
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => handleSaveResponse(data, productId))
        .catch(error => {
            console.error('Error:', error);
            showToast('danger', 'Lỗi', 'Không thể lưu sản phẩm: ' + error.message);
        });
    } else {
        // For POST requests, send form data directly
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => handleSaveResponse(data, productId))
        .catch(error => {
            console.error('Error:', error);
            showToast('danger', 'Lỗi', 'Không thể lưu sản phẩm: ' + error.message);
        });
    }
}

// Helper function to handle save response
function handleSaveResponse(data, productId) {
    if (data.success) {
        const successMessage = productId ? 'Cập nhật sản phẩm thành công' : 'Thêm sản phẩm thành công';
        showToast('success', 'Thành công', successMessage);
        const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
        modal.hide();
        loadProducts(); // Reload product list
    } else {
        showToast('danger', 'Lỗi', data.message || 'Không thể lưu sản phẩm');
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
        const response = await fetch(`/lotso/api/products.php?id=${selectedProductId}`, {
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
function updatePagination(paginationData) {
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
    for (let i = 1; i <= paginationData.total_pages; i++) {
        html += `
            <li class="page-item ${currentPage === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadProducts(${i})">${i}</a>
            </li>
        `;
    }
    
    // Nút Next
    html += `
        <li class="page-item ${currentPage === paginationData.total_pages ? 'disabled' : ''}">
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

// Hàm hiển thị thông báo
function showToast(type, title, message) {
    if (!type || !message) {
        console.error('Invalid toast parameters:', { type, title, message });
        return;
    }

    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title || ''}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Khởi tạo trang
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});
</script>

<?php require_once '../templates/footer.php'; ?>