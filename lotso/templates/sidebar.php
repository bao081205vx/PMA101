<div class="col-md-2">
    <div class="sidebar">
        <div class="d-flex flex-column">
            <a href="/lotso/public/dashboard.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            <a href="/lotso/public/products.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'products.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Sản phẩm
            </a>
            <a href="/lotso/public/categories.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'categories.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Danh mục
            </a>
            <a href="/lotso/public/orders.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'orders.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Đơn hàng
            </a>
            <a href="/lotso/public/users.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'users.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Người dùng
            </a>
            <a href="/lotso/public/discounts.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'discounts.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-percent"></i> Mã giảm giá
            </a>
            <a href="/lotso/public/posts.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'posts.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-newspaper"></i> Bài viết
            </a>
            <a href="/lotso/public/comments.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'comments.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> Bình luận
            </a>
            <a href="/lotso/public/settings.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'settings.php') !== false ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Cài đặt
            </a>
        </div>
    </div>
</div>