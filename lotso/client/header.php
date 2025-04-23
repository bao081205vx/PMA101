<?php
require_once 'init.php';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotso - Cửa hàng giày</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: #ff4081;
        --secondary-color: #ff80ab;
        --hover-color: #f50057;
    }

    .navbar {
        background-color: var(--primary-color) !important;
        height: 70px;
        padding: 0;
    }

    .navbar .container {
        height: 100%;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        font-size: 1.5rem;
        font-weight: bold;
        color: white !important;
        text-decoration: none;
        padding: 0;
        margin-right: 2rem;
    }

    .logo-text {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .brand-name {
        font-family: 'Arial Rounded MT Bold', 'Helvetica Rounded', Arial, sans-serif;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .brand-desc {
        font-size: 0.8rem;
        letter-spacing: 0.05em;
    }

    .navbar-brand img {
        height: 40px;
        margin-right: 10px;
    }

    .navbar-brand span {
        font-family: 'Arial Rounded MT Bold', 'Helvetica Rounded', Arial, sans-serif;
    }

    .navbar-brand, .nav-link {
        color: white !important;
    }

    .nav-link:hover {
        color: var(--secondary-color) !important;
    }

    .btn-primary {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .btn-primary:hover {
        background-color: var(--hover-color) !important;
        border-color: var(--hover-color) !important;
    }

    .btn-outline-primary {
        color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .btn-outline-primary:hover {
        color: white !important;
        background-color: var(--primary-color) !important;
    }

    .text-primary {
        color: var(--primary-color) !important;
    }

    .dropdown-menu {
        border-radius: 4px;
        margin-top: 8px;
    }

    .dropdown-item:hover {
        background-color: var(--secondary-color) !important;
        color: white !important;
    }

    .cart-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: var(--hover-color);
        color: white;
        border-radius: 50%;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    /* Custom styles for white buttons in pink navbar */
    .navbar .btn-outline-primary {
        color: white !important;
        border-color: white !important;
    }

    .navbar .btn-outline-primary:hover {
        color: var(--primary-color) !important;
        background-color: white !important;
    }

    .navbar .btn-primary {
        background-color: white !important;
        border-color: white !important;
        color: var(--primary-color) !important;
    }

    .navbar .btn-primary:hover {
        background-color: var(--secondary-color) !important;
        border-color: var(--secondary-color) !important;
        color: white !important;
    }

    .navbar .cart-count {
        background-color: white;
        color: var(--primary-color);
    }

    /* Header buttons style */
    .navbar .btn {
        padding: 8px 16px;
        font-size: 14px;
        line-height: 1;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        margin-left: 8px;
        border-radius: 4px;
    }

    .navbar .cart-btn {
        padding: 8px;
        min-width: auto;
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 4px;
    }

    .navbar .cart-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: white;
        color: var(--primary-color);
        border-radius: 50%;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        min-width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .navbar-brand {
        font-size: 1.5rem;
        padding: 0;
        margin-right: 2rem;
    }

    .nav-link {
        padding: 8px 16px;
    }

    .navbar-toggler {
        padding: 4px 8px;
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    @media (max-width: 768px) {
        .navbar {
            height: auto;
            padding: 10px 0;
        }
        
        .navbar-collapse {
            background-color: var(--primary-color);
            padding: 10px 0;
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-text">
                    <span class="brand-name">LOTSO</span>
                    <span class="brand-desc">SHOE STORE</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Liên hệ</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Giỏ hàng -->
                    <a href="cart.php" class="btn btn-outline-light cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php">Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light">Đăng nhập</a>
                        <a href="register.php" class="btn btn-light">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    }

    .nav-link {
        padding: 8px 16px;
    }

    .navbar-toggler {
        padding: 4px 8px;
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    @media (max-width: 768px) {
        .navbar {
            height: auto;
            padding: 10px 0;
        }
        
        .navbar-collapse {
            background-color: var(--primary-color);
            padding: 10px 0;
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-text">
                    <span class="brand-name">LOTSO</span>
                    <span class="brand-desc">SHOE STORE</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Liên hệ</a>
                                </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Giỏ hàng -->
                    <a href="cart.php" class="btn btn-outline-light cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php">Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light">Đăng nhập</a>
                        <a href="register.php" class="btn btn-light">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    }

    .nav-link {
        padding: 8px 16px;
    }

    .navbar-toggler {
        padding: 4px 8px;
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    @media (max-width: 768px) {
        .navbar {
            height: auto;
            padding: 10px 0;
        }
        
        .navbar-collapse {
            background-color: var(--primary-color);
            padding: 10px 0;
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-text">
                    <span class="brand-name">LOTSO</span>
                    <span class="brand-desc">SHOE STORE</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Liên hệ</a>
                                </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Giỏ hàng -->
                    <a href="cart.php" class="btn btn-outline-light cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php">Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light">Đăng nhập</a>
                        <a href="register.php" class="btn btn-light">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    }

    .nav-link {
        padding: 8px 16px;
    }

    .navbar-toggler {
        padding: 4px 8px;
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    @media (max-width: 768px) {
        .navbar {
            height: auto;
            padding: 10px 0;
        }
        
        .navbar-collapse {
            background-color: var(--primary-color);
            padding: 10px 0;
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-text">
                    <span class="brand-name">LOTSO</span>
                    <span class="brand-desc">SHOE STORE</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Liên hệ</a>
                                </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Giỏ hàng -->
                    <a href="cart.php" class="btn btn-outline-light cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php">Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light">Đăng nhập</a>
                        <a href="register.php" class="btn btn-light">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    }

    .nav-link {
        padding: 8px 16px;
    }

    .navbar-toggler {
        padding: 4px 8px;
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    @media (max-width: 768px) {
        .navbar {
            height: auto;
            padding: 10px 0;
        }
        
        .navbar-collapse {
            background-color: var(--primary-color);
            padding: 10px 0;
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-text">
                    <span class="brand-name">LOTSO</span>
                    <span class="brand-desc">SHOE STORE</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Liên hệ</a>
                                </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Giỏ hàng -->
                    <a href="cart.php" class="btn btn-outline-light cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php">Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light">Đăng nhập</a>
                        <a href="register.php" class="btn btn-light">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    }

    .nav-link {
        padding: 8px 16px;
    }

    .navbar-toggler {
        padding: 4px 8px;
        border: none;
    }

    .navbar-toggler:focus {
        box-shadow: none;
    }

    @media (max-width: 768px) {
        .navbar {
            height: auto;
            padding: 10px 0;
        }
        
        .navbar-collapse {
            background-color: var(--primary-color);
            padding: 10px 0;
        }
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-text">
                    <span class="brand-name">LOTSO</span>
                    <span class="brand-desc">SHOE STORE</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Liên hệ</a>
                                </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Giỏ hàng -->
                    <a href="cart.php" class="btn btn-outline-light cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User menu -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Thông tin tài khoản</a></li>
                                <li><a class="dropdown-item" href="orders.php">Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light">Đăng nhập</a>
                        <a href="register.php" class="btn btn-light">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
