<?php
require_once 'header.php';

// Xử lý các tham số tìm kiếm và lọc
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : PHP_FLOAT_MAX;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Xây dựng câu truy vấn
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "p.name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($category > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if ($min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price < PHP_FLOAT_MAX) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$where_clause = implode(" AND ", $where_conditions);

// Xác định cách sắp xếp
$order_by = match($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest' => 'p.created_at DESC',
    default => 'p.created_at DESC'
};

// Lấy tổng số sản phẩm
$count_sql = "
    SELECT COUNT(*) 
    FROM products p 
    WHERE $where_clause
";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_products = $stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_products / $limit);

// Lấy danh sách sản phẩm
$sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $where_clause 
    ORDER BY $order_by 
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types . "ii", ...[...$params, $limit, $offset]);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM categories WHERE status = 1")->fetch_all(MYSQLI_ASSOC);
?>

<style>
.product-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s;
    border: 1px solid rgba(0,0,0,0.1);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.product-card .card-img-top {
    height: 250px;
    object-fit: contain;
    padding: 1rem;
    background-color: #fff;
}

.product-card .card-body {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 1rem;
}

.product-card .card-title {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
    height: 40px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card .category-name {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.product-card .price {
    font-size: 1.25rem;
    font-weight: 600;
    color: #ff4081;
    margin: auto 0 1rem 0;
}

.product-card .btn-primary {
    width: 100%;
    padding: 0.5rem;
    background-color: #ff4081;
    border-color: #ff4081;
}

.product-card .btn-primary:hover {
    background-color: #f50057;
    border-color: #f50057;
}
</style>

<div class="container py-5">
    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tên sản phẩm...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Danh mục</label>
                    <select class="form-select" name="category">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Giá từ</label>
                    <input type="number" class="form-control" name="min_price" value="<?= $min_price ?>" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Giá đến</label>
                    <input type="number" class="form-control" name="max_price" value="<?= $max_price < PHP_FLOAT_MAX ? $max_price : '' ?>" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-3">
            <div class="card product-card">
                <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="category-name">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </p>
                    <p class="price"><?= number_format($product['price']) ?> đ</p>
                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-3">
            <div class="card product-card">
                <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="category-name">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </p>
                    <p class="price"><?= number_format($product['price']) ?> đ</p>
                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-3">
            <div class="card product-card">
                <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="category-name">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </p>
                    <p class="price"><?= number_format($product['price']) ?> đ</p>
                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-3">
            <div class="card product-card">
                <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="category-name">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </p>
                    <p class="price"><?= number_format($product['price']) ?> đ</p>
                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-3">
            <div class="card product-card">
                <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="category-name">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </p>
                    <p class="price"><?= number_format($product['price']) ?> đ</p>
                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-6 col-md-3">
            <div class="card product-card">
                <img src="<?= isset($product['image']) ? '../uploads/' . htmlspecialchars($product['image']) : '../assets/images/no-image.jpg' ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="category-name">
                        <?= htmlspecialchars($product['category_name']) ?>
                    </p>
                    <p class="price"><?= number_format($product['price']) ?> đ</p>
                    <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
