<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotso - Cửa hàng đồ chơi</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/lotso/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/lotso/assets/css/client.css">
</head>
<body>
    <!-- Header -->
    <header class="bg-primary">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="/lotso/client">
                    <img src="/lotso/assets/images/logo.png" alt="Lotso" height="40">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/lotso/client">Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/lotso/client/products.php">Sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/lotso/client/posts.php">Tin tức</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/lotso/client/contact.php">Liên hệ</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <form class="d-flex me-3" action="/lotso/client/search.php" method="GET">
                            <input class="form-control me-2" type="search" name="q" placeholder="Tìm kiếm...">
                            <button class="btn btn-light" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                        <a href="/lotso/client/cart.php" class="btn btn-light position-relative me-2">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count">
                                0
                            </span>
                        </a>
                        <?php if (isset($_SESSION['user'])): ?>
                            <div class="dropdown">
                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i>
                                    <?= htmlspecialchars($_SESSION['user']['username']) ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/lotso/client/profile.php">Tài khoản</a></li>
                                    <li><a class="dropdown-item" href="/lotso/client/orders.php">Đơn hàng</a></li>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/lotso/public"><i class="fas fa-cogs"></i> Quản trị hệ thống</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/lotso/client/logout.php">Đăng xuất</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="/lotso/client/login.php" class="btn btn-light me-2">
                                <i class="fas fa-sign-in-alt me-1"></i> Đăng nhập
                            </a>
                            <a href="/lotso/client/register.php" class="btn btn-outline-light">
                                <i class="fas fa-user-plus me-1"></i> Đăng ký
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
