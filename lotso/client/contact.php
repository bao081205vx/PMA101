<?php
require_once 'header.php';

// Xử lý gửi liên hệ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $errors = [];

    // Validate
    if (empty($name)) {
        $errors[] = "Vui lòng nhập họ tên";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    if (empty($subject)) {
        $errors[] = "Vui lòng nhập tiêu đề";
    }
    if (empty($message)) {
        $errors[] = "Vui lòng nhập nội dung";
    }

    if (empty($errors)) {
        // Lưu vào database
        $stmt = $conn->prepare("
            INSERT INTO contacts (name, email, subject, message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            // Gửi email thông báo
            $to = $email;
            $subject = "Cảm ơn bạn đã liên hệ với LOTSO";
            $message = "
                <p>Xin chào $name,</p>
                <p>Cảm ơn bạn đã liên hệ với LOTSO. Chúng tôi đã nhận được thông tin của bạn và sẽ phản hồi trong thời gian sớm nhất.</p>
                <p>Nội dung liên hệ của bạn:</p>
                <p><strong>Tiêu đề:</strong> $subject</p>
                <p><strong>Nội dung:</strong><br>$message</p>
                <p>Trân trọng,<br>LOTSO Team</p>
            ";

            // Headers
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: LOTSO <no-reply@lotso.com>' . "\r\n";

            // Gửi email
            mail($to, $subject, $message, $headers);

            // Thông báo thành công
            $success = "Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi trong thời gian sớm nhất!";
        } else {
            $errors[] = "Có lỗi xảy ra, vui lòng thử lại sau";
        }
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <h1 class="mb-4">Liên hệ với chúng tôi</h1>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Họ tên</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nội dung</label>
                        <textarea class="form-control" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi liên hệ</button>
                </div>
            </form>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Thông tin liên hệ</h5>
                    
                    <div class="mb-4">
                        <h6><i class="fas fa-map-marker-alt text-primary me-2"></i> Địa chỉ:</h6>
                        <p class="mb-0">123 Đường ABC, Quận XYZ<br>TP. Hồ Chí Minh, Việt Nam</p>
                    </div>

                    <div class="mb-4">
                        <h6><i class="fas fa-phone text-primary me-2"></i> Điện thoại:</h6>
                        <p class="mb-0">
                            <a href="tel:0123456789" class="text-decoration-none">0123 456 789</a>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h6><i class="fas fa-envelope text-primary me-2"></i> Email:</h6>
                        <p class="mb-0">
                            <a href="mailto:info@lotso.com" class="text-decoration-none">info@lotso.com</a>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h6><i class="fas fa-clock text-primary me-2"></i> Giờ làm việc:</h6>
                        <p class="mb-0">
                            Thứ 2 - Thứ 6: 8:00 - 17:00<br>
                            Thứ 7: 8:00 - 12:00<br>
                            Chủ nhật: Nghỉ
                        </p>
                    </div>

                    <div>
                        <h6><i class="fas fa-share-alt text-primary me-2"></i> Mạng xã hội:</h6>
                        <div class="social-links">
                            <a href="#" class="text-primary me-3"><i class="fab fa-facebook fa-2x"></i></a>
                            <a href="#" class="text-primary me-3"><i class="fab fa-instagram fa-2x"></i></a>
                            <a href="#" class="text-primary"><i class="fab fa-tiktok fa-2x"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bản đồ -->
    <div class="mt-5">
        <h5 class="mb-4">Bản đồ</h5>
        <div class="ratio ratio-16x9">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.1254674541184!2d106.71237671533417!3d10.801617561697442!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317528a459cb43ab%3A0x6c3d29d370b52a7e!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBIdXRlY2g!5e0!3m2!1svi!2s!4v1650123456789!5m2!1svi!2s" 
                    style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
