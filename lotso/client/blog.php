<?php
require_once 'header.php';

// Xử lý phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lấy tổng số bài viết
$total_posts = $conn->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetch_row()[0];
$total_pages = ceil($total_posts / $limit);

// Lấy danh sách bài viết
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, u.username as author_name 
    FROM posts p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN users u ON p.author_id = u.id 
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy danh mục bài viết
$categories = $conn->query("SELECT * FROM categories WHERE status = 1")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-5">
    <h1 class="text-center mb-5">Blog</h1>

    <div class="row">
        <!-- Danh sách bài viết -->
        <div class="col-md-8">
            <?php foreach ($posts as $post): ?>
            <article class="card mb-4">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="<?= htmlspecialchars($post['image']) ?>" class="img-fluid rounded-start h-100" alt="<?= htmlspecialchars($post['title']) ?>">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body">
                            <h2 class="card-title h5">
                                <a href="blog-detail.php?slug=<?= $post['slug'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h2>
                            <p class="card-text"><?= htmlspecialchars($post['excerpt']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name']) ?> |
                                        <i class="fas fa-folder"></i> <?= htmlspecialchars($post['category_name']) ?> |
                                        <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                                    </small>
                                </p>
                                <a href="blog-detail.php?slug=<?= $post['slug'] ?>" class="btn btn-primary btn-sm">
                                    Đọc thêm
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>

            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Danh mục -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Danh mục</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($categories as $category): ?>
                        <li class="mb-2">
                            <a href="?category=<?= $category['id'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Bài viết xem nhiều -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Bài viết xem nhiều</h5>
                </div>
                <div class="card-body">
                    <?php
                    $popular_posts = $conn->query("
                        SELECT * FROM posts 
                        WHERE status = 'published'
                        ORDER BY views DESC 
                        LIMIT 5
                    ")->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($popular_posts as $post): ?>
                        <li class="mb-2">
                            <a href="blog-detail.php?slug=<?= $post['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-eye"></i> <?= number_format($post['views']) ?> lượt xem
                            </small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
