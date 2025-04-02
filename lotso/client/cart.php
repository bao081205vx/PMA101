<?php
require_once '../templates/client/header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="container py-5">
    <h1 class="mb-4">Giỏ hàng</h1>

    <div class="row">
        <!-- Cart Items -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div id="cartItems">
                        <!-- Cart items will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Tổng đơn hàng</h5>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tạm tính:</span>
                        <span id="subtotal">0đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Phí vận chuyển:</span>
                        <span id="shipping">0đ</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Tổng cộng:</strong>
                        <strong id="total" class="text-primary">0đ</strong>
                    </div>
                    <div class="d-grid gap-2">
                        <button id="checkoutBtn" class="btn btn-primary" disabled>
                            Thanh toán
                        </button>
                        <button id="clearCartBtn" class="btn btn-outline-danger" disabled>
                            Xóa giỏ hàng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Load cart items
async function loadCart() {
    try {
        const response = await fetch('/lotso/api/cart/get.php');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('cartItems');
            const subtotalEl = document.getElementById('subtotal');
            const shippingEl = document.getElementById('shipping');
            const totalEl = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkoutBtn');
            const clearCartBtn = document.getElementById('clearCartBtn');
            
            if (data.data.items.length > 0) {
                container.innerHTML = data.data.items.map(item => `
                    <div class="d-flex mb-4">
                        <img src="/lotso/uploads/products/${item.image}" 
                             alt="${item.name}" 
                             class="rounded me-3" 
                             style="width: 100px; height: 100px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0">
                                    <a href="product.php?id=${item.product_id}" class="text-decoration-none text-dark">
                                        ${item.name}
                                    </a>
                                </h5>
                                <button class="btn btn-link text-danger p-0" 
                                        onclick="removeFromCart(${item.product_id})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <p class="text-primary mb-2">${formatCurrency(item.price)}</p>
                            <div class="d-flex align-items-center">
                                <div class="input-group" style="width: 120px;">
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="updateQuantity(${item.product_id}, ${item.quantity - 1})">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" 
                                           value="${item.quantity}" min="1" max="${item.stock}"
                                           onchange="updateQuantity(${item.product_id}, this.value)">
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="updateQuantity(${item.product_id}, ${item.quantity + 1})">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <p class="ms-3 mb-0">Còn ${item.stock} sản phẩm</p>
                            </div>
                        </div>
                    </div>
                `).join('');

                // Calculate totals
                const subtotal = data.data.total_amount;
                const shipping = subtotal > 0 ? 30000 : 0; // Free shipping for orders over 500,000đ
                const total = subtotal + shipping;

                // Update summary
                subtotalEl.textContent = formatCurrency(subtotal);
                shippingEl.textContent = formatCurrency(shipping);
                totalEl.textContent = formatCurrency(total);

                // Enable buttons
                checkoutBtn.disabled = false;
                clearCartBtn.disabled = false;
            } else {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>Giỏ hàng trống</h5>
                        <p class="mb-4">Hãy thêm sản phẩm vào giỏ hàng của bạn</p>
                        <a href="products.php" class="btn btn-primary">
                            Tiếp tục mua sắm
                        </a>
                    </div>
                `;

                // Update summary
                subtotalEl.textContent = formatCurrency(0);
                shippingEl.textContent = formatCurrency(0);
                totalEl.textContent = formatCurrency(0);

                // Disable buttons
                checkoutBtn.disabled = true;
                clearCartBtn.disabled = true;
            }

            // Update cart count
            document.querySelector('.cart-count').textContent = data.data.total_items;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: error.message || 'Đã có lỗi xảy ra, vui lòng thử lại sau.'
        });
    }
}

// Update quantity
async function updateQuantity(productId, quantity) {
    try {
        const response = await fetch('/lotso/api/cart/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: parseInt(quantity)
            })
        });

        const data = await response.json();
        if (data.success) {
            loadCart();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: error.message || 'Đã có lỗi xảy ra, vui lòng thử lại sau.'
        });
    }
}

// Remove from cart
async function removeFromCart(productId) {
    try {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận',
            text: 'Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        });

        if (result.isConfirmed) {
            const response = await fetch('/lotso/api/cart/remove.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId
                })
            });

            const data = await response.json();
            if (data.success) {
                loadCart();
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Đã xóa sản phẩm khỏi giỏ hàng',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                throw new Error(data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: error.message || 'Đã có lỗi xảy ra, vui lòng thử lại sau.'
        });
    }
}

// Clear cart
document.getElementById('clearCartBtn').addEventListener('click', async () => {
    try {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Xác nhận',
            text: 'Bạn có chắc chắn muốn xóa tất cả sản phẩm khỏi giỏ hàng?',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        });

        if (result.isConfirmed) {
            const response = await fetch('/lotso/api/cart/clear.php', {
                method: 'POST'
            });

            const data = await response.json();
            if (data.success) {
                loadCart();
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Đã xóa tất cả sản phẩm khỏi giỏ hàng',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                throw new Error(data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: error.message || 'Đã có lỗi xảy ra, vui lòng thử lại sau.'
        });
    }
});

// Checkout
document.getElementById('checkoutBtn').addEventListener('click', () => {
    window.location.href = 'checkout.php';
});

// Load cart when page loads
document.addEventListener('DOMContentLoaded', loadCart);
</script>

<?php require_once '../templates/client/footer.php'; ?>
