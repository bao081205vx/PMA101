<?php
require_once 'header.php';

// Lấy 8 sản phẩm mới nhất
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC 
    LIMIT 8
");
$stmt->execute();
$latest_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy 4 bài viết mới nhất
$stmt = $conn->prepare("
    SELECT p.*, u.username as author_name 
    FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC 
    LIMIT 4
");
$stmt->execute();
$latest_posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Kiểm tra bảng reviews có tồn tại không
$reviews_exist = $conn->query("SHOW TABLES LIKE 'reviews'")->num_rows > 0;
$top_reviews = [];

if ($reviews_exist) {
    // Lấy 10 đánh giá có điểm cao nhất
    $stmt = $conn->prepare("
        SELECT r.*, p.name as product_name, u.username
        FROM reviews r
        LEFT JOIN products p ON r.product_id = p.id
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.rating DESC
        LIMIT 10
    ");
    $stmt->execute();
    $top_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- Banner Carousel -->
<div id="mainCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2"></button>
    </div>
    
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="../assets/images/banners/banner1.jpg.png" class="d-block w-100" alt="Banner 1">
            <div class="carousel-caption d-none d-md-block">
                <h2>Bộ Sưu Tập Mới Nhất</h2>
                <p>Khám phá các mẫu giày thời trang mới nhất</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="../assets/images/banners/banner2.jpg.png" class="d-block w-100" alt="Banner 2">
            <div class="carousel-caption d-none d-md-block">
                <h2>Ưu Đãi Đặc Biệt</h2>
                <p>Giảm giá lên đến 50% cho các sản phẩm hot</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="../assets/images/banners/banner.jpg.jpg" class="d-block w-100" alt="Banner 3">
            <div class="carousel-caption d-none d-md-block">
                <h2>Phong Cách Độc Đáo</h2>
                <p>Thể hiện cá tính với bộ sưu tập độc quyền</p>
            </div>
        </div>
    </div>
    
    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<script>
// Khởi tạo carousel với các tùy chọn
document.addEventListener('DOMContentLoaded', function() {
    var myCarousel = new bootstrap.Carousel(document.getElementById('mainCarousel'), {
        interval: 5000, // Thời gian chuyển slide (5 giây)
        wrap: true, // Cho phép quay vòng
        keyboard: true, // Cho phép điều khiển bằng bàn phím
        pause: 'hover' // Tạm dừng khi hover
    });
});
</script>

<style>
/* Banner Carousel Styles */
#mainCarousel {
    margin-top: -1.5rem;
}

.carousel-item {
    height: 500px;
    background-color: #000;
}

.carousel-item img {
    height: 100%;
    width: 100%;
    object-fit: cover;
    object-position: center;
    opacity: 0.9;
}

.carousel-caption {
    background: rgba(0, 0, 0, 0.4);
    padding: 20px;
    border-radius: 10px;
    max-width: 80%;
    margin: auto;
    left: 10%;
    right: 10%;
    bottom: 20%;
}

.carousel-caption h2 {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    font-family: 'Arial Rounded MT Bold', 'Helvetica Rounded', Arial, sans-serif;
}

.carousel-caption p {
    font-size: 1.2rem;
    margin-bottom: 0;
}

.carousel-indicators {
    margin-bottom: 2rem;
}

.carousel-indicators button {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin: 0 6px;
}

.carousel-control-prev,
.carousel-control-next {
    width: 5%;
    opacity: 0.8;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .carousel-item {
        height: 300px;
    }
    
    .carousel-caption h2 {
        font-size: 1.8rem;
    }
    
    .carousel-caption p {
        font-size: 1rem;
    }
}

/* Product Card Styles */
.product-card {
    transition: transform 0.2s;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-card .card-img-top {
    height: 200px;
    width: auto;
    object-fit: contain;
    padding: 1rem;
    display: block;
    margin: 0 auto;
}

.product-card .card-body {
    padding: 1rem;
}

.product-card .product-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    height: 2.4rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .price-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff4081;
    margin: 0.5rem 0;
}

.product-card .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 4px;
    width: 100%;
    background-color: #ff4081;
    border-color: #ff4081;
}

.product-card .btn:hover {
    background-color: #f50057;
    border-color: #f50057;
}

.product-card .text-muted {
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .product-card .card-img-top {
        height: 180px;
    }
}
</style>

<!-- Sản phẩm mới nhất -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card h-100">
                    <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                        </p>
                        <p class="card-text price-text"><?= number_format($product['price']) ?> đ</p>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary mt-auto">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<!-- Bài viết mới nhất -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Bài viết mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_posts as $post): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?= isset($post['image']) ? '../uploads/' . htmlspecialchars($post['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($post['excerpt']), 0, 100) ?>...</p>
                    </div>
                    <div class="card-footer bg-white">
                        <small class="text-muted">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?> |
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="blog.php" class="btn btn-outline-primary">Xem tất cả bài viết</a>
        </div>
    </div>
</section>

<!-- Đánh giá từ khách hàng -->
<?php if (!empty($top_reviews)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Đánh giá từ khách hàng</h2>
        <div class="row">
            <?php foreach ($top_reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <h6 class="text-muted">Sản phẩm: <?= htmlspecialchars($review['product_name']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'footer.php'; ?>

    height: 12px;
    border-radius: 50%;
    margin: 0 6px;
}

.carousel-control-prev,
.carousel-control-next {
    width: 5%;
    opacity: 0.8;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .carousel-item {
        height: 300px;
    }
    
    .carousel-caption h2 {
        font-size: 1.8rem;
    }
    
    .carousel-caption p {
        font-size: 1rem;
    }
}

/* Product Card Styles */
.product-card {
    transition: transform 0.2s;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-card .card-img-top {
    height: 200px;
    width: auto;
    object-fit: contain;
    padding: 1rem;
    display: block;
    margin: 0 auto;
}

.product-card .card-body {
    padding: 1rem;
}

.product-card .product-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    height: 2.4rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .price-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff4081;
    margin: 0.5rem 0;
}

.product-card .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 4px;
    width: 100%;
    background-color: #ff4081;
    border-color: #ff4081;
}

.product-card .btn:hover {
    background-color: #f50057;
    border-color: #f50057;
}

.product-card .text-muted {
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .product-card .card-img-top {
        height: 180px;
    }
}
</style>

<!-- Sản phẩm mới nhất -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card h-100">
                    <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                        </p>
                        <p class="card-text price-text"><?= number_format($product['price']) ?> đ</p>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary mt-auto">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<!-- Bài viết mới nhất -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Bài viết mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_posts as $post): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?= isset($post['image']) ? '../uploads/' . htmlspecialchars($post['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($post['excerpt']), 0, 100) ?>...</p>
                    </div>
                    <div class="card-footer bg-white">
                        <small class="text-muted">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?> |
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="blog.php" class="btn btn-outline-primary">Xem tất cả bài viết</a>
        </div>
    </div>
</section>

<!-- Đánh giá từ khách hàng -->
<?php if (!empty($top_reviews)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Đánh giá từ khách hàng</h2>
        <div class="row">
            <?php foreach ($top_reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <h6 class="text-muted">Sản phẩm: <?= htmlspecialchars($review['product_name']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'footer.php'; ?>

    height: 12px;
    border-radius: 50%;
    margin: 0 6px;
}

.carousel-control-prev,
.carousel-control-next {
    width: 5%;
    opacity: 0.8;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .carousel-item {
        height: 300px;
    }
    
    .carousel-caption h2 {
        font-size: 1.8rem;
    }
    
    .carousel-caption p {
        font-size: 1rem;
    }
}

/* Product Card Styles */
.product-card {
    transition: transform 0.2s;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-card .card-img-top {
    height: 200px;
    width: auto;
    object-fit: contain;
    padding: 1rem;
    display: block;
    margin: 0 auto;
}

.product-card .card-body {
    padding: 1rem;
}

.product-card .product-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    height: 2.4rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .price-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff4081;
    margin: 0.5rem 0;
}

.product-card .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 4px;
    width: 100%;
    background-color: #ff4081;
    border-color: #ff4081;
}

.product-card .btn:hover {
    background-color: #f50057;
    border-color: #f50057;
}

.product-card .text-muted {
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .product-card .card-img-top {
        height: 180px;
    }
}
</style>

<!-- Sản phẩm mới nhất -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card h-100">
                    <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                        </p>
                        <p class="card-text price-text"><?= number_format($product['price']) ?> đ</p>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary mt-auto">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<!-- Bài viết mới nhất -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Bài viết mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_posts as $post): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?= isset($post['image']) ? '../uploads/' . htmlspecialchars($post['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($post['excerpt']), 0, 100) ?>...</p>
                    </div>
                    <div class="card-footer bg-white">
                        <small class="text-muted">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?> |
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="blog.php" class="btn btn-outline-primary">Xem tất cả bài viết</a>
        </div>
    </div>
</section>

<!-- Đánh giá từ khách hàng -->
<?php if (!empty($top_reviews)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Đánh giá từ khách hàng</h2>
        <div class="row">
            <?php foreach ($top_reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <h6 class="text-muted">Sản phẩm: <?= htmlspecialchars($review['product_name']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'footer.php'; ?>

    height: 12px;
    border-radius: 50%;
    margin: 0 6px;
}

.carousel-control-prev,
.carousel-control-next {
    width: 5%;
    opacity: 0.8;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .carousel-item {
        height: 300px;
    }
    
    .carousel-caption h2 {
        font-size: 1.8rem;
    }
    
    .carousel-caption p {
        font-size: 1rem;
    }
}

/* Product Card Styles */
.product-card {
    transition: transform 0.2s;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-card .card-img-top {
    height: 200px;
    width: auto;
    object-fit: contain;
    padding: 1rem;
    display: block;
    margin: 0 auto;
}

.product-card .card-body {
    padding: 1rem;
}

.product-card .product-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    height: 2.4rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .price-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff4081;
    margin: 0.5rem 0;
}

.product-card .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 4px;
    width: 100%;
    background-color: #ff4081;
    border-color: #ff4081;
}

.product-card .btn:hover {
    background-color: #f50057;
    border-color: #f50057;
}

.product-card .text-muted {
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .product-card .card-img-top {
        height: 180px;
    }
}
</style>

<!-- Sản phẩm mới nhất -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card h-100">
                    <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                        </p>
                        <p class="card-text price-text"><?= number_format($product['price']) ?> đ</p>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary mt-auto">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<!-- Bài viết mới nhất -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Bài viết mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_posts as $post): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?= isset($post['image']) ? '../uploads/' . htmlspecialchars($post['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($post['excerpt']), 0, 100) ?>...</p>
                    </div>
                    <div class="card-footer bg-white">
                        <small class="text-muted">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?> |
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="blog.php" class="btn btn-outline-primary">Xem tất cả bài viết</a>
        </div>
    </div>
</section>

<!-- Đánh giá từ khách hàng -->
<?php if (!empty($top_reviews)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Đánh giá từ khách hàng</h2>
        <div class="row">
            <?php foreach ($top_reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <h6 class="text-muted">Sản phẩm: <?= htmlspecialchars($review['product_name']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'footer.php'; ?>

    height: 12px;
    border-radius: 50%;
    margin: 0 6px;
}

.carousel-control-prev,
.carousel-control-next {
    width: 5%;
    opacity: 0.8;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .carousel-item {
        height: 300px;
    }
    
    .carousel-caption h2 {
        font-size: 1.8rem;
    }
    
    .carousel-caption p {
        font-size: 1rem;
    }
}

/* Product Card Styles */
.product-card {
    transition: transform 0.2s;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-card .card-img-top {
    height: 200px;
    width: auto;
    object-fit: contain;
    padding: 1rem;
    display: block;
    margin: 0 auto;
}

.product-card .card-body {
    padding: 1rem;
}

.product-card .product-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    height: 2.4rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .price-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff4081;
    margin: 0.5rem 0;
}

.product-card .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 4px;
    width: 100%;
    background-color: #ff4081;
    border-color: #ff4081;
}

.product-card .btn:hover {
    background-color: #f50057;
    border-color: #f50057;
}

.product-card .text-muted {
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .product-card .card-img-top {
        height: 180px;
    }
}
</style>

<!-- Sản phẩm mới nhất -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card h-100">
                    <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                        </p>
                        <p class="card-text price-text"><?= number_format($product['price']) ?> đ</p>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary mt-auto">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<!-- Bài viết mới nhất -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Bài viết mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_posts as $post): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?= isset($post['image']) ? '../uploads/' . htmlspecialchars($post['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($post['excerpt']), 0, 100) ?>...</p>
                    </div>
                    <div class="card-footer bg-white">
                        <small class="text-muted">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?> |
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="blog.php" class="btn btn-outline-primary">Xem tất cả bài viết</a>
        </div>
    </div>
</section>

<!-- Đánh giá từ khách hàng -->
<?php if (!empty($top_reviews)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Đánh giá từ khách hàng</h2>
        <div class="row">
            <?php foreach ($top_reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <h6 class="text-muted">Sản phẩm: <?= htmlspecialchars($review['product_name']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'footer.php'; ?>

    height: 12px;
    border-radius: 50%;
    margin: 0 6px;
}

.carousel-control-prev,
.carousel-control-next {
    width: 5%;
    opacity: 0.8;
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .carousel-item {
        height: 300px;
    }
    
    .carousel-caption h2 {
        font-size: 1.8rem;
    }
    
    .carousel-caption p {
        font-size: 1rem;
    }
}

/* Product Card Styles */
.product-card {
    transition: transform 0.2s;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-card .card-img-top {
    height: 200px;
    width: auto;
    object-fit: contain;
    padding: 1rem;
    display: block;
    margin: 0 auto;
}

.product-card .card-body {
    padding: 1rem;
}

.product-card .product-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    height: 2.4rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .price-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ff4081;
    margin: 0.5rem 0;
}

.product-card .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    border-radius: 4px;
    width: 100%;
    background-color: #ff4081;
    border-color: #ff4081;
}

.product-card .btn:hover {
    background-color: #f50057;
    border-color: #f50057;
}

.product-card .text-muted {
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .product-card .card-img-top {
        height: 180px;
    }
}
</style>

<!-- Sản phẩm mới nhất -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card product-card h-100">
                    <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text">
                            <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                        </p>
                        <p class="card-text price-text"><?= number_format($product['price']) ?> đ</p>
                        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary mt-auto">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>

<!-- Bài viết mới nhất -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Bài viết mới nhất</h2>
        <div class="row">
            <?php foreach ($latest_posts as $post): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?= isset($post['image']) ? '../uploads/' . htmlspecialchars($post['image']) : '../assets/images/no-image.jpg' ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($post['excerpt']), 0, 100) ?>...</p>
                    </div>
                    <div class="card-footer bg-white">
                        <small class="text-muted">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?> |
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="blog.php" class="btn btn-outline-primary">Xem tất cả bài viết</a>
        </div>
    </div>
</section>

<!-- Đánh giá từ khách hàng -->
<?php if (!empty($top_reviews)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Đánh giá từ khách hàng</h2>
        <div class="row">
            <?php foreach ($top_reviews as $review): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title"><?= htmlspecialchars($review['username']) ?></h5>
                            <div class="text-warning">
                                <?php for($i = 0; $i < $review['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <h6 class="text-muted">Sản phẩm: <?= htmlspecialchars($review['product_name']) ?></h6>
                        <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
