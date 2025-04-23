<?php
require_once 'header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Lấy thông báo lỗi từ session nếu có
if (isset($_SESSION['register_error'])) {
    $error = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    // Create username from email
    $username = strtolower(explode('@', $email)[0]);
    // Remove special characters
    $username = preg_replace('/[^a-z0-9]/', '', $username);
    
    // Validate
    if (empty($firstname) || empty($lastname)) {
        $error = 'Vui lòng nhập đầy đủ họ và tên';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email này đã được đăng ký';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // Add new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, firstname, lastname, address, city, phone, newsletter) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssi", $username, $email, $hashed_password, $firstname, $lastname, $address, $city, $phone, $newsletter);
            
            if ($stmt->execute()) {
                $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
                    // Use JavaScript for redirect instead of PHP header
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 2000);
                    </script>';
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại';
                }
            } catch (Exception $e) {
                $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }
    }
}
?>

<!-- Voucher Banner -->
<div class="voucher-banner">
    Nhận ngay voucher 100K khi đăng ký tài khoản tại Lotso
                </div>

<div class="container py-4">
    <div class="register-container">
        <h1 class="text-center mb-4">ĐĂNG KÝ</h1>
        
        <p class="text-center mb-5">
            Đăng ký để sử dụng các tính năng tiện lợi và thanh toán nhanh chóng. 
            Có một tài khoản giúp bạn dễ dàng hơn trong việc theo dõi các đơn đặt hàng và trả hàng
        </p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="registration-form">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Họ *</label>
                    <input type="text" class="form-control form-control-lg" name="lastname" required>
                        </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tên *</label>
                    <input type="text" class="form-control form-control-lg" name="firstname" required>
                                </div>
                            </div>

                            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" class="form-control form-control-lg" name="email" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mật khẩu *</label>
                <input type="password" class="form-control form-control-lg" name="password" required minlength="6">
            </div>

            <div class="mb-3">
                <label class="form-label">Xác nhận mật khẩu *</label>
                <input type="password" class="form-control form-control-lg" name="confirm_password" required minlength="6">
            </div>

            <div class="mb-3">
                <label class="form-label">Địa chỉ</label>
                <input type="text" class="form-control form-control-lg" name="address">
            </div>

            <div class="mb-3">
                <label class="form-label">Tỉnh / Thành phố</label>
                <input type="text" class="form-control form-control-lg" name="city">
            </div>

            <div class="mb-3">
                <label class="form-label">Số điện thoại (Tùy chọn)</label>
                <input type="tel" class="form-control form-control-lg" name="phone">
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">Đăng ký nhận Email Quảng Cáo</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-dark btn-lg px-5" onclick="history.back()">HỦY</button>
                <button type="submit" class="btn btn-dark btn-lg px-5">Tạo Tài Khoản</button>
            </div>
        </form>
    </div>
</div>

<style>
.voucher-banner {
    background-color: #3b5998;
    color: white;
    text-align: center;
    padding: 10px;
}

.register-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.registration-form {
    max-width: 100%;
}

.form-control {
    border-radius: 0;
    padding: 12px;
    border: 1px solid #ddd;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
}

.btn {
    border-radius: 0;
    padding: 12px 30px;
    font-weight: bold;
    min-width: 200px;
}

.form-check-input:checked {
    background-color: #000;
    border-color: #000;
}

h1 {
    font-weight: bold;
    font-size: 2rem;
}

@media (max-width: 768px) {
    .d-flex {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php require_once 'footer.php'; ?>


            <div class="mb-3">
                <label class="form-label">Số điện thoại (Tùy chọn)</label>
                <input type="tel" class="form-control form-control-lg" name="phone">
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">Đăng ký nhận Email Quảng Cáo</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-dark btn-lg px-5" onclick="history.back()">HỦY</button>
                <button type="submit" class="btn btn-dark btn-lg px-5">Tạo Tài Khoản</button>
            </div>
        </form>
    </div>
</div>

<style>
.voucher-banner {
    background-color: #3b5998;
    color: white;
    text-align: center;
    padding: 10px;
}

.register-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.registration-form {
    max-width: 100%;
}

.form-control {
    border-radius: 0;
    padding: 12px;
    border: 1px solid #ddd;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
}

.btn {
    border-radius: 0;
    padding: 12px 30px;
    font-weight: bold;
    min-width: 200px;
}

.form-check-input:checked {
    background-color: #000;
    border-color: #000;
}

h1 {
    font-weight: bold;
    font-size: 2rem;
}

@media (max-width: 768px) {
    .d-flex {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php require_once 'footer.php'; ?>


            <div class="mb-3">
                <label class="form-label">Số điện thoại (Tùy chọn)</label>
                <input type="tel" class="form-control form-control-lg" name="phone">
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">Đăng ký nhận Email Quảng Cáo</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-dark btn-lg px-5" onclick="history.back()">HỦY</button>
                <button type="submit" class="btn btn-dark btn-lg px-5">Tạo Tài Khoản</button>
            </div>
        </form>
                                </div>
                            </div>

<style>
.voucher-banner {
    background-color: #3b5998;
    color: white;
    text-align: center;
    padding: 10px;
}

.register-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.registration-form {
    max-width: 100%;
}

.form-control {
    border-radius: 0;
    padding: 12px;
    border: 1px solid #ddd;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
}

.btn {
    border-radius: 0;
    padding: 12px 30px;
    font-weight: bold;
    min-width: 200px;
}

.form-check-input:checked {
    background-color: #000;
    border-color: #000;
}

h1 {
    font-weight: bold;
    font-size: 2rem;
}

@media (max-width: 768px) {
    .d-flex {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php require_once 'footer.php'; ?>


                            <div class="mb-3">
                <label class="form-label">Số điện thoại (Tùy chọn)</label>
                <input type="tel" class="form-control form-control-lg" name="phone">
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">Đăng ký nhận Email Quảng Cáo</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-dark btn-lg px-5" onclick="history.back()">HỦY</button>
                <button type="submit" class="btn btn-dark btn-lg px-5">Tạo Tài Khoản</button>
            </div>
        </form>
                                </div>
                            </div>

<style>
.voucher-banner {
    background-color: #3b5998;
    color: white;
    text-align: center;
    padding: 10px;
}

.register-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.registration-form {
    max-width: 100%;
}

.form-control {
    border-radius: 0;
    padding: 12px;
    border: 1px solid #ddd;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
}

.btn {
    border-radius: 0;
    padding: 12px 30px;
    font-weight: bold;
    min-width: 200px;
}

.form-check-input:checked {
    background-color: #000;
    border-color: #000;
}

h1 {
    font-weight: bold;
    font-size: 2rem;
}

@media (max-width: 768px) {
    .d-flex {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php require_once 'footer.php'; ?>


                            <div class="mb-3">
                <label class="form-label">Số điện thoại (Tùy chọn)</label>
                <input type="tel" class="form-control form-control-lg" name="phone">
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">Đăng ký nhận Email Quảng Cáo</label>
                                </div>
                            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-dark btn-lg px-5" onclick="history.back()">HỦY</button>
                <button type="submit" class="btn btn-dark btn-lg px-5">Tạo Tài Khoản</button>
            </div>
                        </form>
    </div>
</div>

<style>
.voucher-banner {
    background-color: #3b5998;
    color: white;
    text-align: center;
    padding: 10px;
}

.register-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.registration-form {
    max-width: 100%;
}

.form-control {
    border-radius: 0;
    padding: 12px;
    border: 1px solid #ddd;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
}

.btn {
    border-radius: 0;
    padding: 12px 30px;
    font-weight: bold;
    min-width: 200px;
}

.form-check-input:checked {
    background-color: #000;
    border-color: #000;
}

h1 {
    font-weight: bold;
    font-size: 2rem;
}

@media (max-width: 768px) {
    .d-flex {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php require_once 'footer.php'; ?>


            <div class="mb-3">
                <label class="form-label">Số điện thoại (Tùy chọn)</label>
                <input type="tel" class="form-control form-control-lg" name="phone">
                        </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">Đăng ký nhận Email Quảng Cáo</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-dark btn-lg px-5" onclick="history.back()">HỦY</button>
                <button type="submit" class="btn btn-dark btn-lg px-5">Tạo Tài Khoản</button>
        </div>
        </form>
    </div>
</div>

<style>
.voucher-banner {
    background-color: #3b5998;
    color: white;
    text-align: center;
    padding: 10px;
}

.register-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.registration-form {
    max-width: 100%;
}

.form-control {
    border-radius: 0;
    padding: 12px;
    border: 1px solid #ddd;
}

.form-control:focus {
    box-shadow: none;
    border-color: #000;
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
}

.btn {
    border-radius: 0;
    padding: 12px 30px;
    font-weight: bold;
    min-width: 200px;
}

.form-check-input:checked {
    background-color: #000;
    border-color: #000;
}

h1 {
    font-weight: bold;
    font-size: 2rem;
}

@media (max-width: 768px) {
    .d-flex {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php require_once 'footer.php'; ?>
