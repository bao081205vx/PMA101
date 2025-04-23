USE shoe_store;

-- Thêm các cột mới vào bảng orders
ALTER TABLE orders
ADD COLUMN shipping_name VARCHAR(100) NOT NULL AFTER total_price,
ADD COLUMN shipping_phone VARCHAR(20) NOT NULL AFTER shipping_name,
ADD COLUMN shipping_address TEXT NOT NULL AFTER shipping_phone,
ADD COLUMN payment_method ENUM('cod', 'momo', 'zalopay') NOT NULL AFTER shipping_address,
ADD COLUMN payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending' AFTER payment_method;

-- Cập nhật ENUM status để thêm giá trị waiting_payment
ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'canceled', 'waiting_payment') DEFAULT 'pending';
