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

// Valid user roles
$validRoles = ['admin', 'user'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single user
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(400, false, 'ID người dùng không hợp lệ');
            }

            $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                // Get user's orders with pagination
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $offset = ($page - 1) * $limit;
                
                // Get total orders count
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $totalResult = $stmt->get_result()->fetch_assoc();
                $total = $totalResult['total'];
                
                // Get orders for current page
                $stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) as total_items 
                                      FROM orders o 
                                      LEFT JOIN order_items oi ON o.id = oi.order_id 
                                      WHERE o.user_id = ? 
                                      GROUP BY o.id 
                                      ORDER BY o.created_at DESC 
                                      LIMIT ? OFFSET ?");
                $stmt->bind_param("iii", $id, $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();
                $orders = $result->fetch_all(MYSQLI_ASSOC);
                
                $user['orders'] = [
                    'data' => $orders,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ];
                
                sendResponse(200, true, 'Lấy thông tin người dùng thành công', $user);
            } else {
                sendResponse(404, false, 'Không tìm thấy người dùng');
            }
        } else {
            // Get all users with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Get total users count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $totalResult = $stmt->get_result()->fetch_assoc();
            $total = $totalResult['total'];
            
            // Get users for current page
            $stmt = $conn->prepare("SELECT id, username, email, role, created_at 
                                  FROM users 
                                  ORDER BY id DESC 
                                  LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            
            sendResponse(200, true, 'Lấy danh sách người dùng thành công', [
                'users' => $users,
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
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'password'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                sendResponse(400, false, "Thiếu trường $field");
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendResponse(400, false, 'Email không hợp lệ');
        }
        
        // Check username length
        if (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            sendResponse(400, false, 'Tên người dùng phải từ 3-50 ký tự');
        }
        
        // Check password strength
        if (strlen($data['password']) < 6) {
            sendResponse(400, false, 'Mật khẩu phải có ít nhất 6 ký tự');
        }
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $data['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            sendResponse(400, false, 'Tên người dùng đã tồn tại');
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            sendResponse(400, false, 'Email đã được sử dụng');
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role if not provided
        $role = isset($data['role']) && in_array($data['role'], $validRoles) ? $data['role'] : 'user';
        
        // Create user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $data['username'], $data['email'], $hashedPassword, $role);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            
            // Get created user
            $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            sendResponse(201, true, 'Tạo tài khoản thành công', $user);
        } else {
            sendResponse(500, false, 'Lỗi khi tạo tài khoản');
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Thiếu ID người dùng');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'ID người dùng không hợp lệ');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            sendResponse(400, false, 'Không có dữ liệu cập nhật');
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        $types = "";
        
        if (isset($data['username'])) {
            if (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
                sendResponse(400, false, 'Tên người dùng phải từ 3-50 ký tự');
            }
            
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $data['username'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                sendResponse(400, false, 'Tên người dùng đã tồn tại');
            }
            
            $updateFields[] = "username = ?";
            $params[] = $data['username'];
            $types .= "s";
        }
        
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                sendResponse(400, false, 'Email không hợp lệ');
            }
            
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $data['email'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                sendResponse(400, false, 'Email đã được sử dụng');
            }
            
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
            $types .= "s";
        }
        
        if (isset($data['password'])) {
            if (strlen($data['password']) < 6) {
                sendResponse(400, false, 'Mật khẩu phải có ít nhất 6 ký tự');
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $updateFields[] = "password = ?";
            $params[] = $hashedPassword;
            $types .= "s";
        }
        
        if (isset($data['role'])) {
            if (!in_array($data['role'], $validRoles)) {
                sendResponse(400, false, 'Vai trò không hợp lệ');
            }
            
            $updateFields[] = "role = ?";
            $params[] = $data['role'];
            $types .= "s";
        }
        
        if (empty($updateFields)) {
            sendResponse(400, false, 'Không có trường nào được cập nhật');
        }
        
        // Update user
        $query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Get updated user
            $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            sendResponse(200, true, 'Cập nhật thông tin thành công', $user);
        } else {
            sendResponse(500, false, 'Lỗi khi cập nhật thông tin');
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Thiếu ID người dùng');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'ID người dùng không hợp lệ');
        }
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            sendResponse(404, false, 'Không tìm thấy người dùng');
        }
        
        // Prevent deleting admin users
        if ($user['role'] === 'admin') {
            sendResponse(403, false, 'Không thể xóa tài khoản admin');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete user's orders and order items
            $stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($order = $result->fetch_assoc()) {
                // Delete order items
                $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                $stmt->bind_param("i", $order['id']);
                $stmt->execute();
            }
            
            // Delete orders
            $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $conn->commit();
            sendResponse(200, true, 'Xóa tài khoản thành công');
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(500, false, 'Lỗi khi xóa tài khoản');
        }
        break;

    default:
        sendResponse(405, false, 'Phương thức không được hỗ trợ');
}