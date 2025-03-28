-- Tạo database (chỉ khi chưa có)
CREATE DATABASE IF NOT EXISTS shoe_store;
USE shoe_store;

-- Xóa bảng cũ nếu tồn tại để tránh lỗi khi nhập lại dữ liệu
DROP TABLE IF EXISTS orders, products, users;

-- Tạo bảng sản phẩm
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng đơn hàng
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'canceled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Chèn dữ liệu mẫu vào bảng sản phẩm
INSERT INTO products (name, price, stock) VALUES
('Nike Air Max', 150.00, 50),
('Adidas Ultraboost', 180.00, 30),
('Puma RS-X', 130.00, 20),
('Vans Old Skool', 90.00, 40),
('Converse Chuck Taylor', 85.00, 25);

-- Chèn dữ liệu mẫu vào bảng người dùng (mật khẩu đã băm SHA-256)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', SHA2('admin123', 256), 'admin'),
('john_doe', 'john@example.com', SHA2('password123', 256), 'customer'),
('jane_smith', 'jane@example.com', SHA2('mypassword', 256), 'customer');

-- Chèn dữ liệu mẫu vào bảng đơn hàng
INSERT INTO orders (user_id, total_price, status) VALUES
(2, 150.00, 'completed'),
(3, 180.00, 'pending'),
(2, 130.00, 'processing');

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

