<?php
// Đảm bảo xử lý tiếng Việt
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Tắt hiển thị lỗi PHP
ini_set('display_errors', 0);
error_reporting(0);

// Đảm bảo luôn trả về JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Hàm xử lý lỗi chung
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    sendResponse(500, false, 'Lỗi hệ thống: ' . $errstr);
    exit;
}

// Đăng ký error handler
set_error_handler('handleError');

// Hàm xử lý exception
function handleException($e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
    exit;
}

// Đăng ký exception handler
set_exception_handler('handleException');

require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';
require_once __DIR__ . '/../functions/validate.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception("Không thể kết nối database");
    }

    // Kiểm tra method override
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single product
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    sendResponse(400, false, 'ID sản phẩm không hợp lệ');
                }

                $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                                      FROM products p 
                                      LEFT JOIN categories c ON p.category_id = c.id 
                                      WHERE p.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();

                if ($product) {
                    sendResponse(200, true, 'Lấy thông tin sản phẩm thành công', $product);
                } else {
                    sendResponse(404, false, 'Không tìm thấy sản phẩm');
                }
            } else {
                // Get all products with filtering and pagination
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $offset = ($page - 1) * $limit;
                $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
                
                // Base queries
                $query = "SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id";
                $countQuery = "SELECT COUNT(*) as total FROM products p";
                
                // Add category filter if specified
                $whereClause = "";
                $params = [];
                $types = "";
                
                if ($category_id) {
                    $whereClause = " WHERE p.category_id = ?";
                    $params[] = $category_id;
                    $types .= "i";
                }
                
                // Add search filter if specified
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = "%" . $_GET['search'] . "%";
                    $whereClause .= $whereClause ? " AND" : " WHERE";
                    $whereClause .= " (p.name LIKE ? OR p.description LIKE ?)";
                    $params[] = $search;
                    $params[] = $search;
                    $types .= "ss";
                }
                
                // Complete queries with where clause
                $query .= $whereClause;
                $countQuery .= $whereClause;
                
                // Get total count
                $stmt = $conn->prepare($countQuery);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $totalResult = $stmt->get_result()->fetch_assoc();
                $total = $totalResult['total'];
                
                // Add sorting and pagination
                $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                $types .= "ii";
                
                // Get products
                $stmt = $conn->prepare($query);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $products = $result->fetch_all(MYSQLI_ASSOC);
                
                // Đảm bảo products là một mảng
                if (!is_array($products)) {
                    $products = [];
                }
                
                sendResponse(200, true, 'Lấy danh sách sản phẩm thành công', [
                    'products' => $products,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;

        case 'POST':
            // Debug thông tin nhận được
            error_log("POST - Received data: " . print_r($_POST, true));
            error_log("POST - Files: " . print_r($_FILES, true));
            
            // Validate required fields
            $required = ['name', 'price', 'quantity', 'category_id'];
            foreach ($required as $field) {
                if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                    sendResponse(400, false, "Thiếu trường $field");
                }
            }
            
            // Validate numeric fields
            if (!is_numeric($_POST['price']) || $_POST['price'] < 0) {
                sendResponse(400, false, 'Giá không hợp lệ');
            }
            
            if (!is_numeric($_POST['quantity']) || $_POST['quantity'] < 0) {
                sendResponse(400, false, 'Số lượng không hợp lệ');
            }
            
            if (!is_numeric($_POST['category_id'])) {
                sendResponse(400, false, 'Danh mục không hợp lệ');
            }
            
            // Check if category exists
            $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->bind_param("i", $_POST['category_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendResponse(400, false, 'Danh mục không tồn tại');
            }
            
            // Xử lý upload ảnh
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Kiểm tra định dạng ảnh
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = $_FILES['image']['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    sendResponse(400, false, 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG và GIF');
                }
                
                // Kiểm tra kích thước ảnh (tối đa 5MB)
                $maxSize = 5 * 1024 * 1024; // 5MB
                if ($_FILES['image']['size'] > $maxSize) {
                    sendResponse(400, false, 'Kích thước ảnh quá lớn. Tối đa 5MB');
                }
                
                // Lấy phần mở rộng của file
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                
                // Tạo tên file mới với timestamp để tránh trùng lặp
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    error_log("Upload error: " . print_r($_FILES['image']['error'], true));
                    sendResponse(500, false, 'Không thể upload ảnh');
                }
                
                $image = $fileName;
            }
            
            try {
                // Create product
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity, category_id, image) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $description = $_POST['description'] ?? '';
                $stmt->bind_param("ssdiis", 
                    $_POST['name'], 
                    $description, 
                    $_POST['price'], 
                    $_POST['quantity'],
                    $_POST['category_id'],
                    $image
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $productId = $stmt->insert_id;
                
                // Get created product
                $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                                      FROM products p 
                                      LEFT JOIN categories c ON p.category_id = c.id 
                                      WHERE p.id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                sendResponse(201, true, 'Tạo sản phẩm thành công', $product);
            } catch (Exception $e) {
                error_log("SQL Error: " . $e->getMessage());
                sendResponse(500, false, 'Lỗi khi tạo sản phẩm: ' . $e->getMessage());
            }
            break;

        case 'PUT':
            // Debug thông tin nhận được
            error_log("PUT - Received data: " . print_r($_POST, true));
            error_log("PUT - Files: " . print_r($_FILES, true));
            
            // Validate product ID
            if (!isset($_GET['id'])) {
                sendResponse(400, false, 'Thiếu ID sản phẩm');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(400, false, 'ID sản phẩm không hợp lệ');
            }
            
            // Check if product exists
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendResponse(404, false, 'Không tìm thấy sản phẩm');
            }
            
            // Validate required fields
            $required = ['name', 'price', 'quantity', 'category_id'];
            foreach ($required as $field) {
                if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                    sendResponse(400, false, "Thiếu trường $field");
                }
            }
            
            // Validate numeric fields
            if (!is_numeric($_POST['price']) || $_POST['price'] < 0) {
                sendResponse(400, false, 'Giá không hợp lệ');
            }
            
            if (!is_numeric($_POST['quantity']) || $_POST['quantity'] < 0) {
                sendResponse(400, false, 'Số lượng không hợp lệ');
            }
            
            if (!is_numeric($_POST['category_id'])) {
                sendResponse(400, false, 'Danh mục không hợp lệ');
            }
            
            // Check if category exists
            $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->bind_param("i", $_POST['category_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendResponse(400, false, 'Danh mục không tồn tại');
            }
            
            // Xử lý upload ảnh
            $image = $_POST['current_image'] ?? null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Kiểm tra định dạng ảnh
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = $_FILES['image']['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    sendResponse(400, false, 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG và GIF');
                }
                
                // Kiểm tra kích thước ảnh (tối đa 5MB)
                $maxSize = 5 * 1024 * 1024; // 5MB
                if ($_FILES['image']['size'] > $maxSize) {
                    sendResponse(400, false, 'Kích thước ảnh quá lớn. Tối đa 5MB');
                }
                
                // Lấy phần mở rộng của file
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                
                // Tạo tên file mới với timestamp để tránh trùng lặp
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    error_log("Upload error: " . print_r($_FILES['image']['error'], true));
                    sendResponse(500, false, 'Không thể upload ảnh');
                }
                
                // Xóa ảnh cũ nếu có
                if ($image && file_exists($uploadDir . $image)) {
                    unlink($uploadDir . $image);
                }
                
                $image = $fileName;
            }
            
            try {
                // Update product
                $stmt = $conn->prepare("UPDATE products SET 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    quantity = ?, 
                    category_id = ?, 
                    image = ? 
                    WHERE id = ?");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $description = $_POST['description'] ?? '';
                $stmt->bind_param("ssdiisi", 
                    $_POST['name'],
                    $description,
                    $_POST['price'],
                    $_POST['quantity'],
                    $_POST['category_id'],
                    $image,
                    $id
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                if ($stmt->affected_rows === 0) {
                    sendResponse(400, false, 'Không có thay đổi nào được cập nhật');
                }
                
                // Get updated product
                $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                                      FROM products p 
                                      LEFT JOIN categories c ON p.category_id = c.id 
                                      WHERE p.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                sendResponse(200, true, 'Cập nhật sản phẩm thành công', $product);
            } catch (Exception $e) {
                error_log("Error updating product: " . $e->getMessage());
                sendResponse(500, false, 'Không thể cập nhật sản phẩm: ' . $e->getMessage());
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                sendResponse(400, false, 'Thiếu ID sản phẩm');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(400, false, 'ID sản phẩm không hợp lệ');
            }
            
            // Check if product exists
            $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendResponse(404, false, 'Không tìm thấy sản phẩm');
            }
            
            // Delete product
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                sendResponse(200, true, 'Xóa sản phẩm thành công');
            } else {
                sendResponse(500, false, 'Lỗi khi xóa sản phẩm');
            }
            break;

        default:
            sendResponse(405, false, 'Method không được hỗ trợ');
    }
} catch (Exception $e) {
    error_log("System Error: " . $e->getMessage());
    sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
}