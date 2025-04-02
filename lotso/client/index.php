<?php
require_once '../templates/client/header.php';
?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div class="container">
        <h1>Chào mừng đến với Lotso</h1>
        <p>Thiên đường đồ chơi cho mọi lứa tuổi</p>
        <a href="products.php" class="btn btn-light btn-lg">Mua sắm ngay</a>
    </div>
</section>

<!-- Special Sale Banner -->
<section class="mb-5">
    <div class="container">
        <div class="text-center mb-4">
            <img src="/lotso/assets/images/banners/special-sale.jpg" alt="Special Sale" class="img-fluid rounded">
        </div>
        <div class="text-center mb-4">
            <img src="/lotso/assets/images/banners/flash-sale.jpg" alt="10.10 Flash Sale" class="img-fluid rounded">
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Danh mục sản phẩm</h2>
        <div class="product-grid" id="categoriesContainer">
            <!-- Categories will be loaded here -->
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm nổi bật</h2>
        <div class="product-grid" id="featuredProducts">
            <!-- Featured products will be loaded here -->
        </div>
    </div>
</section>

<!-- Latest Products -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm mới</h2>
        <div class="product-grid" id="latestProducts">
            <!-- Latest products will be loaded here -->
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-primary btn-lg">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<script>
// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Load categories
async function loadCategories() {
    try {
        const response = await fetch('/lotso/api/categories.php');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('categoriesContainer');
            container.innerHTML = data.data.map(category => `
                <div class="product-card">
                    <a href="products.php?category=${category.id}" class="text-decoration-none">
                        <img src="/lotso/uploads/categories/${category.image}" alt="${category.name}">
                        <div class="card-body text-center">
                            <h5 class="card-title text-dark">${category.name}</h5>
                        </div>
                    </a>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load featured products
async function loadFeaturedProducts() {
    try {
        const response = await fetch('/lotso/api/products.php?featured=1&limit=8');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('featuredProducts');
            container.innerHTML = data.data.map(product => `
                <div class="product-card">
                    <a href="product.php?id=${product.id}" class="text-decoration-none">
                        <img src="/lotso/uploads/products/${product.image}" alt="${product.name}">
                        <div class="card-body">
                            <h5 class="card-title text-dark">${product.name}</h5>
                            <p class="price mb-2">${formatCurrency(product.price)}</p>
                            <div class="d-grid">
                                <button class="btn btn-primary" onclick="event.preventDefault(); addToCart(${product.id})">
                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load latest products
async function loadLatestProducts() {
    try {
        const response = await fetch('/lotso/api/products.php?sort=created_at&order=desc&limit=8');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('latestProducts');
            container.innerHTML = data.data.map(product => `
                <div class="product-card">
                    <a href="product.php?id=${product.id}" class="text-decoration-none">
                        <img src="/lotso/uploads/products/${product.image}" alt="${product.name}">
                        <div class="card-body">
                            <h5 class="card-title text-dark">${product.name}</h5>
                            <p class="price mb-2">${formatCurrency(product.price)}</p>
                            <div class="d-grid">
                                <button class="btn btn-primary" onclick="event.preventDefault(); addToCart(${product.id})">
                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Add to cart function
async function addToCart(productId) {
    try {
        const response = await fetch('/lotso/api/cart/add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });

        const data = await response.json();
        if (data.success) {
            // Update cart count
            document.querySelector('.cart-count').textContent = data.data.total_items;
            
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: 'Đã thêm sản phẩm vào giỏ hàng',
                showConfirmButton: false,
                timer: 1500
            });
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

// Load all data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadFeaturedProducts();
    loadLatestProducts();
});
</script>

<?php require_once '../templates/client/footer.php'; ?>
