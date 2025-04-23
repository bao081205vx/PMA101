<?php
require_once 'header.php';

// Lấy thông báo lỗi từ session nếu có
$error = '';
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>

<!-- Voucher Banner -->
<div class="voucher-banner">
    Nhận ngay voucher 100K khi đăng ký tài khoản tại Lotso
</div>

<div class="login-container">
    <div class="row justify-content-center">
        <!-- Login Section -->
        <div class="col-md-5">
            <h2>Đăng nhập</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="process_login.php">
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>

                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Mật khẩu" required>
                </div>

                <div class="text-end mb-3">
                    <a href="forgot-password.php" class="text-primary text-decoration-none">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn btn-dark w-100">ĐĂNG NHẬP</button>
            </form>
        </div>

        <!-- Register Section -->
        <div class="col-md-5">
            <h2>Đăng ký</h2>
            <p class="text-muted">Bạn chưa có tài khoản?</p>
            <a href="register.php" class="btn btn-dark w-100">ĐĂNG KÝ</a>
        </div>
    </div>
</div>

<style>
.voucher-banner {
    background-color: #3b5998;
    color: white;
    text-align: center;
    padding: 10px;
}

.login-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 15px;
}

.row {
    margin: 0 100px;
}

h2 {
    margin-bottom: 24px;
    font-size: 24px;
    font-weight: bold;
}

.form-control {
    height: 45px;
    border-radius: 0;
    border: 1px solid #ddd;
    padding: 10px 15px;
    font-size: 16px;
    margin-bottom: 16px;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.btn {
    height: 45px;
    border-radius: 0;
    font-weight: 500;
    font-size: 16px;
}

.text-primary {
    color: #007bff !important;
}

@media (max-width: 768px) {
    .login-container {
        margin: 20px auto;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-5 + .col-md-5 {
        margin-top: 2rem;
    }
}
</style>

<?php require_once 'footer.php'; ?>

    padding: 0 15px;
}

.row {
    margin: 0 100px;
}

h2 {
    margin-bottom: 24px;
    font-size: 24px;
    font-weight: bold;
}

.form-control {
    height: 45px;
    border-radius: 0;
    border: 1px solid #ddd;
    padding: 10px 15px;
    font-size: 16px;
    margin-bottom: 16px;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.btn {
    height: 45px;
    border-radius: 0;
    font-weight: 500;
    font-size: 16px;
}

.text-primary {
    color: #007bff !important;
}

@media (max-width: 768px) {
    .login-container {
        margin: 20px auto;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-5 + .col-md-5 {
        margin-top: 2rem;
    }
}
</style>

<?php require_once 'footer.php'; ?>

    padding: 0 15px;
}

.row {
    margin: 0 100px;
}

h2 {
    margin-bottom: 24px;
    font-size: 24px;
    font-weight: bold;
}

.form-control {
    height: 45px;
    border-radius: 0;
    border: 1px solid #ddd;
    padding: 10px 15px;
    font-size: 16px;
    margin-bottom: 16px;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.btn {
    height: 45px;
    border-radius: 0;
    font-weight: 500;
    font-size: 16px;
}

.text-primary {
    color: #007bff !important;
}

@media (max-width: 768px) {
    .login-container {
        margin: 20px auto;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-5 + .col-md-5 {
        margin-top: 2rem;
    }
}
</style>

<?php require_once 'footer.php'; ?>

    padding: 0 15px;
}

.row {
    margin: 0 100px;
}

h2 {
    margin-bottom: 24px;
    font-size: 24px;
    font-weight: bold;
}

.form-control {
    height: 45px;
    border-radius: 0;
    border: 1px solid #ddd;
    padding: 10px 15px;
    font-size: 16px;
    margin-bottom: 16px;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.btn {
    height: 45px;
    border-radius: 0;
    font-weight: 500;
    font-size: 16px;
}

.text-primary {
    color: #007bff !important;
}

@media (max-width: 768px) {
    .login-container {
        margin: 20px auto;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-5 + .col-md-5 {
        margin-top: 2rem;
    }
}
</style>

<?php require_once 'footer.php'; ?>

    padding: 0 15px;
}

.row {
    margin: 0 100px;
}

h2 {
    margin-bottom: 24px;
    font-size: 24px;
    font-weight: bold;
}

.form-control {
    height: 45px;
    border-radius: 0;
    border: 1px solid #ddd;
    padding: 10px 15px;
    font-size: 16px;
    margin-bottom: 16px;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.btn {
    height: 45px;
    border-radius: 0;
    font-weight: 500;
    font-size: 16px;
}

.text-primary {
    color: #007bff !important;
}

@media (max-width: 768px) {
    .login-container {
        margin: 20px auto;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-5 + .col-md-5 {
        margin-top: 2rem;
    }
}
</style>

<?php require_once 'footer.php'; ?>

    padding: 0 15px;
}

.row {
    margin: 0 100px;
}

h2 {
    margin-bottom: 24px;
    font-size: 24px;
    font-weight: bold;
}

.form-control {
    height: 45px;
    border-radius: 0;
    border: 1px solid #ddd;
    padding: 10px 15px;
    font-size: 16px;
    margin-bottom: 16px;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.btn {
    height: 45px;
    border-radius: 0;
    font-weight: 500;
    font-size: 16px;
}

.text-primary {
    color: #007bff !important;
}

@media (max-width: 768px) {
    .login-container {
        margin: 20px auto;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-5 + .col-md-5 {
        margin-top: 2rem;
    }
}
</style>

<?php require_once 'footer.php'; ?>
