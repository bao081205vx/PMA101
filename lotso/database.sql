-- Tạo database (chỉ khi chưa có)
CREATE DATABASE IF NOT EXISTS lotso;
USE lotso;

-- Xóa bảng cũ nếu tồn tại
DROP TABLE IF EXISTS order_items, orders, products, users, discounts, posts, categories, comments, settings;

-- 🔹 Bảng DANH MỤC SẢN PHẨM
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 🔹 Bảng SẢN PHẨM
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 🔹 Bảng NGƯỜI DÙNG
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 🔹 Bảng ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'canceled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 🔹 Bảng CHI TIẾT ĐƠN HÀNG
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 🔹 Bảng MÃ GIẢM GIÁ
CREATE TABLE IF NOT EXISTS discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    used INT NOT NULL DEFAULT 0,
    minimum_order DECIMAL(10,2) NOT NULL DEFAULT 0,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 🔹 Bảng BÀI VIẾT (BLOG)
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    category_id INT,
    author_id INT NOT NULL,
    image VARCHAR(255),
    views INT NOT NULL DEFAULT 0,
    status ENUM('draft', 'published') DEFAULT 'draft',
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 🔹 Bảng BÌNH LUẬN
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 🔹 Bảng CÀI ĐẶT
CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(50) NOT NULL,
  `value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 🌟 THÊM DỮ LIỆU MẪU

-- Dữ liệu mẫu cho bảng users
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', SHA2('admin123', 256), 'admin'),
('john_doe', 'john@example.com', SHA2('password123', 256), 'customer'),
('jane_smith', 'jane@example.com', SHA2('mypassword', 256), 'customer');

-- Dữ liệu mẫu cho bảng categories
INSERT INTO categories (name, slug, description, image) VALUES
('Giày thể thao', 'giay-the-thao', 'Chuyên các loại giày thể thao', 'sport.jpg'),
('Giày công sở', 'giay-cong-so', 'Chuyên các loại giày công sở', 'office.jpg'),
('Giày sneakers', 'giay-sneakers', 'Các mẫu sneakers hot nhất', 'sneakers.jpg');

-- Dữ liệu mẫu cho bảng products
INSERT INTO products (name, description, price, quantity, category_id, image) VALUES
('Nike Air Max', 'Giày thể thao Nike Air Max nhẹ, êm ái', 150.00, 50, 1, 'nike_air_max.jpg'),
('Adidas Ultraboost', 'Giày thể thao Adidas Ultraboost hiệu suất cao', 180.00, 30, 1, 'adidas_ultraboost.jpg'),
('Giày Tây Da Cao Cấp', 'Giày tây lịch lãm dành cho công sở', 200.00, 20, 2, 'giay_tay.jpg'),
('Vans Old Skool', 'Sneaker phong cách cổ điển của Vans', 90.00, 40, 3, 'vans_old_skool.jpg'),
('Converse Chuck Taylor', 'Sneaker huyền thoại Converse Chuck Taylor', 85.00, 25, 3, 'converse_chuck.jpg');

-- Dữ liệu mẫu cho bảng orders
INSERT INTO orders (user_id, total_price, status) VALUES
(2, 150.00, 'completed'),
(3, 180.00, 'pending'),
(2, 130.00, 'processing');

-- Dữ liệu mẫu cho bảng order_items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 150.00),
(2, 2, 1, 180.00),
(3, 3, 1, 130.00);

-- Dữ liệu mẫu cho bảng discounts
INSERT INTO discounts (code, type, value, quantity, minimum_order, description, start_date, end_date) VALUES
('SUMMER10', 'percentage', 10, 100, 0, 'Giảm 10% cho mùa hè', '2024-06-01 00:00:00', '2024-06-30 23:59:59'),
('WINTER50', 'fixed', 50, 50, 200000, 'Giảm 50K cho đơn từ 200K', '2024-12-01 00:00:00', '2024-12-31 23:59:59');

-- Dữ liệu mẫu cho bảng posts
INSERT INTO posts (title, slug, content, excerpt, category_id, author_id, image, status, meta_title, meta_description) VALUES
('Hướng dẫn chọn giày thể thao', 'huong-dan-chon-giay-the-thao', 'Nội dung hướng dẫn chọn giày...', 'Tổng hợp các tiêu chí quan trọng khi chọn giày thể thao', 1, 2, 'huong_dan_chon_giay.jpg', 'published', 'Cách chọn giày thể thao phù hợp', 'Hướng dẫn chi tiết cách chọn giày thể thao phù hợp với từng môn thể thao'),
('Cách phối đồ với giày sneaker', 'cach-phoi-do-voi-giay-sneaker', 'Nội dung cách phối đồ...', 'Những gợi ý phối đồ cùng sneaker cực chất', 3, 3, 'phoi_do_sneaker.jpg', 'published', 'Mix & Match với Sneaker', 'Hướng dẫn phối đồ với giày sneaker cho nam và nữ');

-- Dữ liệu mẫu cho bảng comments
INSERT INTO comments (post_id, user_id, user_name, email, content, status) VALUES
(1, 2, 'John Doe', 'john@example.com', 'Bài viết rất hữu ích!', 'approved'),
(2, 3, 'Jane Smith', 'jane@example.com', 'Mình thích phong cách sneaker!', 'approved');
