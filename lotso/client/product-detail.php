<?php
require_once 'header.php';

// Kiểm tra ID sản phẩm
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Lấy sản phẩm liên quan (cùng danh mục và có giá tương tự)
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE (p.category_id = ? OR ABS(p.price - ?) <= 500000)
    AND p.id != ? 
    ORDER BY 
        CASE 
            WHEN p.category_id = ? THEN 0 
            ELSE 1 
        END,
        ABS(p.price - ?) 
    LIMIT 8
");
$stmt->bind_param("idiid", 
    $product['category_id'], 
    $product['price'],
    $product_id,
    $product['category_id'],
    $product['price']
);
$stmt->execute();
$related_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Kiểm tra bảng reviews có tồn tại không
$reviews_exist = $conn->query("SHOW TABLES LIKE 'reviews'")->num_rows > 0;
$reviews = [];

if ($reviews_exist) {
// Lấy đánh giá sản phẩm
$stmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Xử lý đánh giá mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt = $conn->prepare("
            INSERT INTO reviews (product_id, user_id, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $product_id, $_SESSION['user_id'], $rating, $comment);
        
        if ($stmt->execute()) {
            header("Location: product-detail.php?id=$product_id&reviewed=1");
            exit;
            }
        }
    }
}
?>

<style>
.product-detail-img {
    max-height: 500px;
    width: 100%;
    object-fit: contain;
    background-color: #fff;
    padding: 1rem;
    border-radius: 0.5rem;
}

.product-title {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.product-price {
    font-size: 1.5rem;
    color: #ff4081;
    font-weight: 600;
}

.product-description {
    margin: 2rem 0;
    line-height: 1.6;
}

.product-meta {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.related-products {
    background-color: #f8f9fa;
    padding: 3rem 0;
    margin-top: 4rem;
}

.related-products h3 {
    position: relative;
    text-align: center;
    margin-bottom: 2rem;
    font-weight: 600;
}

.related-products h3:after {
    content: '';
    display: block;
    width: 50px;
    height: 3px;
    background-color: #ff4081;
    margin: 1rem auto;
}

.related-product-card {
    background: white;
    border: none;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.related-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.related-product-card .card-img-top {
    height: 200px;
    object-fit: contain;
    padding: 1rem;
    background-color: #fff;
}

.related-product-card .card-body {
    padding: 1rem;
}

.related-product-card .card-title {
    font-size: 1rem;
    font-weight: 500;
    height: 40px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    margin-bottom: 0.5rem;
}

.related-product-card .price {
    color: #ff4081;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.related-product-card .btn-primary {
    width: 100%;
    border-radius: 5px;
    padding: 0.5rem;
    font-size: 0.9rem;
}

.review-card {
    margin-bottom: 1rem;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.rating {
    margin-bottom: 1rem;
}

.btn-primary {
    background-color: #ff4081;
    border-color: #ff4081;
}

.btn-primary:hover {
    background-color: #f50057;
    border-color: #f50057;
}

.product-actions {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.btn-outline-primary {
    color: #ff4081;
    border-color: #ff4081;
}

.btn-outline-primary:hover {
    background-color: #ff4081;
    border-color: #ff4081;
    color: white;
}

.quantity .form-control {
    border-radius: 0;
}

.quantity .btn {
    border-radius: 0;
    width: 40px;
    padding: 0;
}

.input-group {
    width: 150px;
}
</style>

<div class="container py-5">
    <div class="row">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-md-6 mb-4">
            <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                 class="product-detail-img" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>

            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price mb-4">
                <?= number_format($product['price']) ?> đ
            </div>
            
            <div class="product-description">
                <h5>Mô tả sản phẩm:</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <div class="product-meta">
                <p class="mb-2">
                    <strong>Danh mục:</strong> <?= htmlspecialchars($product['category_name']) ?>
                </p>
                <p class="mb-2">
                    <strong>Tình trạng:</strong> 
                    <?php if (isset($product['stock'])): ?>
                        <?= $product['stock'] > 0 ? '<span class="text-success">Còn hàng</span>' : '<span class="text-danger">Hết hàng</span>' ?>
                    <?php else: ?>
                        <span class="text-warning">Đang cập nhật</span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity mb-3">
                    <label class="form-label">Số lượng:</label>
                    <div class="input-group" style="width: 150px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">-</button>
                        <input type="number" class="form-control text-center" id="quantity" value="1" min="1">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                    <button onclick="buyNow(<?= $product['id'] ?>)" class="btn btn-primary btn-lg">
                        <i class="fas fa-bolt"></i> Mua ngay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products">
        <div class="container">
            <h3>Sản phẩm liên quan</h3>
            <div class="row g-4">
                <?php foreach ($related_products as $related): ?>
                <div class="col-6 col-md-3">
                    <div class="card related-product-card">
                        <img src="<?= isset($related['image']) ? '../uploads/' . htmlspecialchars($related['image']) : '../assets/images/no-image.jpg' ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($related['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>
                            <p class="price"><?= number_format($related['price']) ?> đ</p>
                            <a href="product-detail.php?id=<?= $related['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Đánh giá sản phẩm -->
    <?php if ($reviews_exist): ?>
    <section class="mt-5">
        <h3 class="mb-4">Đánh giá sản phẩm</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Form đánh giá -->
        <div class="card review-card mb-4">
            <div class="card-body">
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label">Đánh giá của bạn</label>
                        <div class="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="<?= $i ?>" required>
                                <label class="form-check-label">
                                    <?php for ($j = 1; $j <= $i; $j++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét của bạn</label>
                        <textarea class="form-control" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm
        </div>
        <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <?php if (!empty($reviews)): ?>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<script>
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    if (newValue < 1) newValue = 1;
    quantityInput.value = newValue;
}

function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Gửi request thêm vào giỏ hàng
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            // Cập nhật số lượng trong giỏ hàng trên header
            const cartCount = document.getElementById('cart-count');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
            }
            // Hiển thị thông báo thành công
            showToast('success', 'Thành công', data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Thêm vào giỏ hàng và chuyển đến trang thanh toán
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&buy_now=1`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'checkout.php';
            }
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Hàm hiển thị thông báo
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        delay: 3000
    });
    bsToast.show();
    
    // Xóa toast sau khi ẩn
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Tạo container cho toast nếu chưa có
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php require_once 'footer.php'; ?>


.product-actions {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.btn-outline-primary {
    color: #ff4081;
    border-color: #ff4081;
}

.btn-outline-primary:hover {
    background-color: #ff4081;
    border-color: #ff4081;
    color: white;
}

.quantity .form-control {
    border-radius: 0;
}

.quantity .btn {
    border-radius: 0;
    width: 40px;
    padding: 0;
}

.input-group {
    width: 150px;
}
</style>

<div class="container py-5">
    <div class="row">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-md-6 mb-4">
            <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                 class="product-detail-img" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>

            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price mb-4">
                <?= number_format($product['price']) ?> đ
            </div>
            
            <div class="product-description">
                <h5>Mô tả sản phẩm:</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <div class="product-meta">
                <p class="mb-2">
                    <strong>Danh mục:</strong> <?= htmlspecialchars($product['category_name']) ?>
                </p>
                <p class="mb-2">
                    <strong>Tình trạng:</strong> 
                    <?php if (isset($product['stock'])): ?>
                    <?= $product['stock'] > 0 ? '<span class="text-success">Còn hàng</span>' : '<span class="text-danger">Hết hàng</span>' ?>
                    <?php else: ?>
                        <span class="text-warning">Đang cập nhật</span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity mb-3">
                    <label class="form-label">Số lượng:</label>
                    <div class="input-group" style="width: 150px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">-</button>
                        <input type="number" class="form-control text-center" id="quantity" value="1" min="1">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                        </button>
                    <button onclick="buyNow(<?= $product['id'] ?>)" class="btn btn-primary btn-lg">
                        <i class="fas fa-bolt"></i> Mua ngay
                    </button>
                    </div>
                </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products">
        <div class="container">
            <h3>Sản phẩm liên quan</h3>
            <div class="row g-4">
                <?php foreach ($related_products as $related): ?>
                <div class="col-6 col-md-3">
                    <div class="card related-product-card">
                        <img src="<?= isset($related['image']) ? '../uploads/' . htmlspecialchars($related['image']) : '../assets/images/no-image.jpg' ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($related['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>
                            <p class="price"><?= number_format($related['price']) ?> đ</p>
                            <a href="product-detail.php?id=<?= $related['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Đánh giá sản phẩm -->
    <?php if ($reviews_exist): ?>
    <section class="mt-5">
        <h3 class="mb-4">Đánh giá sản phẩm</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Form đánh giá -->
        <div class="card review-card mb-4">
            <div class="card-body">
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label">Đánh giá của bạn</label>
                        <div class="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="<?= $i ?>" required>
                                <label class="form-check-label">
                                    <?php for ($j = 1; $j <= $i; $j++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét của bạn</label>
                        <textarea class="form-control" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
            </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm
        </div>
            <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <?php if (!empty($reviews)): ?>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<script>
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    if (newValue < 1) newValue = 1;
    quantityInput.value = newValue;
}

function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Gửi request thêm vào giỏ hàng
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            // Cập nhật số lượng trong giỏ hàng trên header
            const cartCount = document.getElementById('cart-count');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
            }
            // Hiển thị thông báo thành công
            showToast('success', 'Thành công', data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Thêm vào giỏ hàng và chuyển đến trang thanh toán
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&buy_now=1`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'checkout.php';
            }
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Hàm hiển thị thông báo
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        delay: 3000
    });
    bsToast.show();
    
    // Xóa toast sau khi ẩn
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Tạo container cho toast nếu chưa có
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php require_once 'footer.php'; ?>


.product-actions {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.btn-outline-primary {
    color: #ff4081;
    border-color: #ff4081;
}

.btn-outline-primary:hover {
    background-color: #ff4081;
    border-color: #ff4081;
    color: white;
}

.quantity .form-control {
    border-radius: 0;
}

.quantity .btn {
    border-radius: 0;
    width: 40px;
    padding: 0;
}

.input-group {
    width: 150px;
}
</style>

<div class="container py-5">
    <div class="row">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-md-6 mb-4">
            <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                 class="product-detail-img" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>

            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price mb-4">
                <?= number_format($product['price']) ?> đ
            </div>
            
            <div class="product-description">
                <h5>Mô tả sản phẩm:</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <div class="product-meta">
                <p class="mb-2">
                    <strong>Danh mục:</strong> <?= htmlspecialchars($product['category_name']) ?>
                </p>
                <p class="mb-2">
                    <strong>Tình trạng:</strong> 
                    <?php if (isset($product['stock'])): ?>
                        <?= $product['stock'] > 0 ? '<span class="text-success">Còn hàng</span>' : '<span class="text-danger">Hết hàng</span>' ?>
                    <?php else: ?>
                        <span class="text-warning">Đang cập nhật</span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity mb-3">
                    <label class="form-label">Số lượng:</label>
                    <div class="input-group" style="width: 150px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">-</button>
                        <input type="number" class="form-control text-center" id="quantity" value="1" min="1">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                    <button onclick="buyNow(<?= $product['id'] ?>)" class="btn btn-primary btn-lg">
                        <i class="fas fa-bolt"></i> Mua ngay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products">
        <div class="container">
            <h3>Sản phẩm liên quan</h3>
            <div class="row g-4">
                <?php foreach ($related_products as $related): ?>
                <div class="col-6 col-md-3">
                    <div class="card related-product-card">
                        <img src="<?= isset($related['image']) ? '../uploads/' . htmlspecialchars($related['image']) : '../assets/images/no-image.jpg' ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($related['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>
                            <p class="price"><?= number_format($related['price']) ?> đ</p>
                            <a href="product-detail.php?id=<?= $related['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Đánh giá sản phẩm -->
    <?php if ($reviews_exist): ?>
    <section class="mt-5">
        <h3 class="mb-4">Đánh giá sản phẩm</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Form đánh giá -->
        <div class="card review-card mb-4">
            <div class="card-body">
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label">Đánh giá của bạn</label>
                        <div class="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="<?= $i ?>" required>
                                <label class="form-check-label">
                                    <?php for ($j = 1; $j <= $i; $j++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét của bạn</label>
                        <textarea class="form-control" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm
        </div>
        <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <?php if (!empty($reviews)): ?>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<script>
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    if (newValue < 1) newValue = 1;
    quantityInput.value = newValue;
}

function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Gửi request thêm vào giỏ hàng
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            // Cập nhật số lượng trong giỏ hàng trên header
            const cartCount = document.getElementById('cart-count');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
            }
            // Hiển thị thông báo thành công
            showToast('success', 'Thành công', data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Thêm vào giỏ hàng và chuyển đến trang thanh toán
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&buy_now=1`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'checkout.php';
            }
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Hàm hiển thị thông báo
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        delay: 3000
    });
    bsToast.show();
    
    // Xóa toast sau khi ẩn
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Tạo container cho toast nếu chưa có
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php require_once 'footer.php'; ?>


.product-actions {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.btn-outline-primary {
    color: #ff4081;
    border-color: #ff4081;
}

.btn-outline-primary:hover {
    background-color: #ff4081;
    border-color: #ff4081;
    color: white;
}

.quantity .form-control {
    border-radius: 0;
}

.quantity .btn {
    border-radius: 0;
    width: 40px;
    padding: 0;
}

.input-group {
    width: 150px;
}
</style>

<div class="container py-5">
    <div class="row">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-md-6 mb-4">
            <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                 class="product-detail-img" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>

            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price mb-4">
                <?= number_format($product['price']) ?> đ
            </div>
            
            <div class="product-description">
                <h5>Mô tả sản phẩm:</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <div class="product-meta">
                <p class="mb-2">
                    <strong>Danh mục:</strong> <?= htmlspecialchars($product['category_name']) ?>
                </p>
                <p class="mb-2">
                    <strong>Tình trạng:</strong> 
                    <?php if (isset($product['stock'])): ?>
                        <?= $product['stock'] > 0 ? '<span class="text-success">Còn hàng</span>' : '<span class="text-danger">Hết hàng</span>' ?>
                    <?php else: ?>
                        <span class="text-warning">Đang cập nhật</span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity mb-3">
                    <label class="form-label">Số lượng:</label>
                    <div class="input-group" style="width: 150px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">-</button>
                        <input type="number" class="form-control text-center" id="quantity" value="1" min="1">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                    <button onclick="buyNow(<?= $product['id'] ?>)" class="btn btn-primary btn-lg">
                        <i class="fas fa-bolt"></i> Mua ngay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products">
        <div class="container">
            <h3>Sản phẩm liên quan</h3>
            <div class="row g-4">
            <?php foreach ($related_products as $related): ?>
                <div class="col-6 col-md-3">
                    <div class="card related-product-card">
                        <img src="<?= isset($related['image']) ? '../uploads/' . htmlspecialchars($related['image']) : '../assets/images/no-image.jpg' ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($related['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>
                            <p class="price"><?= number_format($related['price']) ?> đ</p>
                            <a href="product-detail.php?id=<?= $related['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Đánh giá sản phẩm -->
    <?php if ($reviews_exist): ?>
    <section class="mt-5">
        <h3 class="mb-4">Đánh giá sản phẩm</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Form đánh giá -->
        <div class="card review-card mb-4">
            <div class="card-body">
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label">Đánh giá của bạn</label>
                        <div class="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="<?= $i ?>" required>
                                <label class="form-check-label">
                                    <?php for ($j = 1; $j <= $i; $j++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét của bạn</label>
                        <textarea class="form-control" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm
        </div>
        <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <?php if (!empty($reviews)): ?>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<script>
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    if (newValue < 1) newValue = 1;
    quantityInput.value = newValue;
}

function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Gửi request thêm vào giỏ hàng
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            // Cập nhật số lượng trong giỏ hàng trên header
            const cartCount = document.getElementById('cart-count');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
            }
            // Hiển thị thông báo thành công
            showToast('success', 'Thành công', data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Thêm vào giỏ hàng và chuyển đến trang thanh toán
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&buy_now=1`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'checkout.php';
            }
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Hàm hiển thị thông báo
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        delay: 3000
    });
    bsToast.show();
    
    // Xóa toast sau khi ẩn
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Tạo container cho toast nếu chưa có
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php require_once 'footer.php'; ?>


.product-actions {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.btn-outline-primary {
    color: #ff4081;
    border-color: #ff4081;
}

.btn-outline-primary:hover {
    background-color: #ff4081;
    border-color: #ff4081;
    color: white;
}

.quantity .form-control {
    border-radius: 0;
}

.quantity .btn {
    border-radius: 0;
    width: 40px;
    padding: 0;
}

.input-group {
    width: 150px;
}
</style>

<div class="container py-5">
    <div class="row">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-md-6 mb-4">
            <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                 class="product-detail-img" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>

            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price mb-4">
                <?= number_format($product['price']) ?> đ
            </div>
            
            <div class="product-description">
                <h5>Mô tả sản phẩm:</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <div class="product-meta">
                <p class="mb-2">
                    <strong>Danh mục:</strong> <?= htmlspecialchars($product['category_name']) ?>
                </p>
                <p class="mb-2">
                    <strong>Tình trạng:</strong> 
                    <?php if (isset($product['stock'])): ?>
                        <?= $product['stock'] > 0 ? '<span class="text-success">Còn hàng</span>' : '<span class="text-danger">Hết hàng</span>' ?>
        <?php else: ?>
                        <span class="text-warning">Đang cập nhật</span>
        <?php endif; ?>
                </p>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity mb-3">
                    <label class="form-label">Số lượng:</label>
                    <div class="input-group" style="width: 150px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">-</button>
                        <input type="number" class="form-control text-center" id="quantity" value="1" min="1">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                    <button onclick="buyNow(<?= $product['id'] ?>)" class="btn btn-primary btn-lg">
                        <i class="fas fa-bolt"></i> Mua ngay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products">
        <div class="container">
            <h3>Sản phẩm liên quan</h3>
            <div class="row g-4">
                <?php foreach ($related_products as $related): ?>
                <div class="col-6 col-md-3">
                    <div class="card related-product-card">
                        <img src="<?= isset($related['image']) ? '../uploads/' . htmlspecialchars($related['image']) : '../assets/images/no-image.jpg' ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($related['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>
                            <p class="price"><?= number_format($related['price']) ?> đ</p>
                            <a href="product-detail.php?id=<?= $related['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Đánh giá sản phẩm -->
    <?php if ($reviews_exist): ?>
    <section class="mt-5">
        <h3 class="mb-4">Đánh giá sản phẩm</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Form đánh giá -->
        <div class="card review-card mb-4">
            <div class="card-body">
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label">Đánh giá của bạn</label>
                        <div class="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="<?= $i ?>" required>
                                <label class="form-check-label">
                                    <?php for ($j = 1; $j <= $i; $j++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </label>
</div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét của bạn</label>
                        <textarea class="form-control" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm
        </div>
        <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <?php if (!empty($reviews)): ?>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<script>
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    if (newValue < 1) newValue = 1;
    quantityInput.value = newValue;
}

function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Gửi request thêm vào giỏ hàng
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            // Cập nhật số lượng trong giỏ hàng trên header
            const cartCount = document.getElementById('cart-count');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
            }
            // Hiển thị thông báo thành công
            showToast('success', 'Thành công', data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Thêm vào giỏ hàng và chuyển đến trang thanh toán
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&buy_now=1`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'checkout.php';
            }
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Hàm hiển thị thông báo
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        delay: 3000
    });
    bsToast.show();
    
    // Xóa toast sau khi ẩn
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Tạo container cho toast nếu chưa có
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php require_once 'footer.php'; ?>


.product-actions {
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.btn-outline-primary {
    color: #ff4081;
    border-color: #ff4081;
}

.btn-outline-primary:hover {
    background-color: #ff4081;
    border-color: #ff4081;
    color: white;
}

.quantity .form-control {
    border-radius: 0;
}

.quantity .btn {
    border-radius: 0;
    width: 40px;
    padding: 0;
}

.input-group {
    width: 150px;
}
</style>

<div class="container py-5">
    <div class="row">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-md-6 mb-4">
            <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                 class="product-detail-img" 
                 alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>

            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-price mb-4">
                <?= number_format($product['price']) ?> đ
            </div>
            
            <div class="product-description">
                <h5>Mô tả sản phẩm:</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <div class="product-meta">
                <p class="mb-2">
                    <strong>Danh mục:</strong> <?= htmlspecialchars($product['category_name']) ?>
                </p>
                <p class="mb-2">
                    <strong>Tình trạng:</strong> 
                    <?php if (isset($product['stock'])): ?>
                        <?= $product['stock'] > 0 ? '<span class="text-success">Còn hàng</span>' : '<span class="text-danger">Hết hàng</span>' ?>
                    <?php else: ?>
                        <span class="text-warning">Đang cập nhật</span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity mb-3">
                    <label class="form-label">Số lượng:</label>
                    <div class="input-group" style="width: 150px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">-</button>
                        <input type="number" class="form-control text-center" id="quantity" value="1" min="1">
                        <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button onclick="addToCart(<?= $product['id'] ?>)" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                    <button onclick="buyNow(<?= $product['id'] ?>)" class="btn btn-primary btn-lg">
                        <i class="fas fa-bolt"></i> Mua ngay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products">
        <div class="container">
            <h3>Sản phẩm liên quan</h3>
            <div class="row g-4">
                <?php foreach ($related_products as $related): ?>
                <div class="col-6 col-md-3">
                    <div class="card related-product-card">
                        <img src="<?= isset($related['image']) ? '../uploads/' . htmlspecialchars($related['image']) : '../assets/images/no-image.jpg' ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($related['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>
                            <p class="price"><?= number_format($related['price']) ?> đ</p>
                            <a href="product-detail.php?id=<?= $related['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Đánh giá sản phẩm -->
    <?php if ($reviews_exist): ?>
    <section class="mt-5">
        <h3 class="mb-4">Đánh giá sản phẩm</h3>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Form đánh giá -->
        <div class="card review-card mb-4">
            <div class="card-body">
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label class="form-label">Đánh giá của bạn</label>
                        <div class="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" value="<?= $i ?>" required>
                                <label class="form-check-label">
                                    <?php for ($j = 1; $j <= $i; $j++): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nhận xét của bạn</label>
                        <textarea class="form-control" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm
        </div>
        <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <?php if (!empty($reviews)): ?>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<script>
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    if (newValue < 1) newValue = 1;
    quantityInput.value = newValue;
}

function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Gửi request thêm vào giỏ hàng
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            // Cập nhật số lượng trong giỏ hàng trên header
            const cartCount = document.getElementById('cart-count');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
            }
            // Hiển thị thông báo thành công
            showToast('success', 'Thành công', data.message || 'Đã thêm sản phẩm vào giỏ hàng!');
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
    // Disable buttons while processing
    const addToCartBtn = document.querySelector('button[onclick="addToCart(' + productId + ')"]');
    const buyNowBtn = document.querySelector('button[onclick="buyNow(' + productId + ')"]');
    if (addToCartBtn) addToCartBtn.disabled = true;
    if (buyNowBtn) buyNowBtn.disabled = true;
    
    // Thêm vào giỏ hàng và chuyển đến trang thanh toán
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&buy_now=1`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'checkout.php';
            }
        } else {
            showToast('error', 'Lỗi', data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(error => {
        // Re-enable buttons
        if (addToCartBtn) addToCartBtn.disabled = false;
        if (buyNowBtn) buyNowBtn.disabled = false;

        console.error('Error:', error);
        showToast('error', 'Lỗi', 'Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Hàm hiển thị thông báo
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        delay: 3000
    });
    bsToast.show();
    
    // Xóa toast sau khi ẩn
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Tạo container cho toast nếu chưa có
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<?php require_once 'footer.php'; ?>
