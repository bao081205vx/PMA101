<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';
require_once __DIR__ . '/../functions/validate.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

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
            
            sendResponse(200, true, 'Lấy danh sách sản phẩm thành công', [
                'data' => $products,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['name', 'price', 'quantity', 'description', 'category_id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                sendResponse(400, false, "Thiếu trường $field");
            }
        }
        
        // Validate numeric fields
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            sendResponse(400, false, 'Giá không hợp lệ');
        }
        
        if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
            sendResponse(400, false, 'Số lượng không hợp lệ');
        }
        
        if (!is_numeric($data['category_id'])) {
            sendResponse(400, false, 'Danh mục không hợp lệ');
        }
        
        // Check if category exists
        $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $data['category_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(400, false, 'Danh mục không tồn tại');
        }
        
        // Create product
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity, category_id, image) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $image = isset($data['image']) ? $data['image'] : null;
        $stmt->bind_param("ssdiis", 
            $data['name'], 
            $data['description'], 
            $data['price'], 
            $data['quantity'], 
            $data['category_id'],
            $image
        );
        
        if ($stmt->execute()) {
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
        } else {
            sendResponse(500, false, 'Lỗi khi tạo sản phẩm');
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Thiếu ID sản phẩm');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'ID sản phẩm không hợp lệ');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            sendResponse(400, false, 'Không có dữ liệu cập nhật');
        }
        
        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(404, false, 'Không tìm thấy sản phẩm');
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        $types = "";
        
        if (isset($data['name'])) {
            if (empty(trim($data['name']))) {
                sendResponse(400, false, 'Tên sản phẩm không được để trống');
            }
            $updateFields[] = "name = ?";
            $params[] = $data['name'];
            $types .= "s";
        }
        
        if (isset($data['description'])) {
            if (empty(trim($data['description']))) {
                sendResponse(400, false, 'Mô tả không được để trống');
            }
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
            $types .= "s";
        }
        
        if (isset($data['price'])) {
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                sendResponse(400, false, 'Giá không hợp lệ');
            }
            $updateFields[] = "price = ?";
            $params[] = $data['price'];
            $types .= "d";
        }
        
        if (isset($data['quantity'])) {
            if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
                sendResponse(400, false, 'Số lượng không hợp lệ');
            }
            $updateFields[] = "quantity = ?";
            $params[] = $data['quantity'];
            $types .= "i";
        }
        
        if (isset($data['category_id'])) {
            if (!is_numeric($data['category_id'])) {
                sendResponse(400, false, 'Danh mục không hợp lệ');
            }
            
            // Check if category exists
            $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->bind_param("i", $data['category_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendResponse(400, false, 'Danh mục không tồn tại');
            }
            
            $updateFields[] = "category_id = ?";
            $params[] = $data['category_id'];
            $types .= "i";
        }
        
        if (isset($data['image'])) {
            $updateFields[] = "image = ?";
            $params[] = $data['image'];
            $types .= "s";
        }
        
        if (empty($updateFields)) {
            sendResponse(400, false, 'Không có trường nào được cập nhật');
        }
        
        // Update product
        $query = "UPDATE products SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Get updated product
            $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                                  FROM products p 
                                  LEFT JOIN categories c ON p.category_id = c.id 
                                  WHERE p.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            
            sendResponse(200, true, 'Cập nhật sản phẩm thành công', $product);
        } else {
            sendResponse(500, false, 'Lỗi khi cập nhật sản phẩm');
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
        sendResponse(405, false, 'Phương thức không được hỗ trợ');
}