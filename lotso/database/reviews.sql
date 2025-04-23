-- Bảng ĐÁNH GIÁ SẢN PHẨM
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Thêm dữ liệu mẫu
INSERT INTO reviews (product_id, user_id, rating, comment) VALUES
(1, 2, 5, 'Sản phẩm rất tốt, đúng như mô tả'),
(1, 3, 4, 'Chất lượng ổn, giao hàng nhanh'),
(2, 2, 5, 'Giày đẹp, form vừa vặn'),
(3, 3, 5, 'Rất hài lòng với sản phẩm này'),
(4, 2, 4, 'Chất lượng tốt, giá cả hợp lý');
