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

// Valid order statuses
$validStatuses = ['pending', 'processing', 'completed', 'canceled'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['export'])) {
            // Export orders to Excel
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="orders.xls"');
            
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $whereClause = $status ? "WHERE o.status = ?" : "";
            
            $query = "SELECT o.id, u.username, u.email, o.total_price, o.status, o.created_at
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     $whereClause 
                     ORDER BY o.created_at DESC";
            
            if ($status) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $status);
            } else {
                $stmt = $conn->prepare($query);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            
            echo "ID\tKhách hàng\tEmail\tTổng tiền\tTrạng thái\tNgày đặt\n";
            foreach ($orders as $order) {
                echo "{$order['id']}\t{$order['username']}\t{$order['email']}\t{$order['total_price']}\t{$order['status']}\t{$order['created_at']}\n";
            }
            exit;
        }
        
        if (isset($_GET['id'])) {
            // Get single order with its items
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(400, false, 'ID đơn hàng không hợp lệ');
            }
            
            // Get order details
            $stmt = $conn->prepare("SELECT o.*, u.username, u.email 
                                  FROM orders o 
                                  JOIN users u ON o.user_id = u.id 
                                  WHERE o.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            if (!$order) {
                sendResponse(404, false, 'Không tìm thấy đơn hàng');
            }
            
            // Get order items
            $stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.price as unit_price 
                                  FROM order_items oi 
                                  JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);
            
            $order['items'] = $items;
            sendResponse(200, true, 'Lấy thông tin đơn hàng thành công', $order);
        } else {
            // Get all orders with filtering and pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            
            $query = "SELECT o.*, u.username, u.email 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id";
            $countQuery = "SELECT COUNT(*) as total FROM orders o";
            
            $params = [];
            $types = "";
            
            if ($status) {
                if (!in_array($status, $validStatuses)) {
                    sendResponse(400, false, 'Trạng thái đơn hàng không hợp lệ');
                }
                $query .= " WHERE o.status = ?";
                $countQuery .= " WHERE o.status = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            $query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            // Get total count
            if ($status) {
                $countStmt = $conn->prepare($countQuery);
                $countStmt->bind_param("s", $status);
            } else {
                $countStmt = $conn->prepare($countQuery);
            }
            $countStmt->execute();
            $totalResult = $countStmt->get_result()->fetch_assoc();
            $total = $totalResult['total'];
            
            // Get orders
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            
            $totalPages = ceil($total / $limit);
            
            sendResponse(200, true, 'Lấy danh sách đơn hàng thành công', [
                'orders' => $orders,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages
                ]
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['user_id', 'items'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                sendResponse(400, false, "Thiếu trường $field");
            }
        }
        
        // Validate items
        if (!is_array($data['items']) || empty($data['items'])) {
            sendResponse(400, false, 'Đơn hàng phải có ít nhất một sản phẩm');
        }
        
        foreach ($data['items'] as $item) {
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                sendResponse(400, false, 'Thông tin sản phẩm không hợp lệ');
            }
            if ($item['quantity'] <= 0) {
                sendResponse(400, false, 'Số lượng sản phẩm phải lớn hơn 0');
            }
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, status) VALUES (?, 'pending')");
            $stmt->bind_param("i", $data['user_id']);
            $stmt->execute();
            $orderId = $stmt->insert_id;
            
            // Calculate total price and insert order items
            $totalPrice = 0;
            foreach ($data['items'] as $item) {
                // Check product availability
                $stmt = $conn->prepare("SELECT price, quantity FROM products WHERE id = ?");
                $stmt->bind_param("i", $item['product_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                
                if (!$product) {
                    throw new Exception("Sản phẩm không tồn tại");
                }
                if ($product['quantity'] < $item['quantity']) {
                    throw new Exception("Số lượng sản phẩm không đủ");
                }
                
                // Insert order item
                $price = $product['price'];
                $subtotal = $price * $item['quantity'];
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $price);
                $stmt->execute();
                
                // Update product quantity
                $newQuantity = $product['quantity'] - $item['quantity'];
                $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $stmt->bind_param("ii", $newQuantity, $item['product_id']);
                $stmt->execute();
                
                $totalPrice += $subtotal;
            }
            
            // Update order total
            $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
            $stmt->bind_param("di", $totalPrice, $orderId);
            $stmt->execute();
            
            $conn->commit();
            sendResponse(201, true, 'Tạo đơn hàng thành công', ['id' => $orderId]);
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(400, false, $e->getMessage());
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Thiếu ID đơn hàng');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'ID đơn hàng không hợp lệ');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['status'])) {
            sendResponse(400, false, 'Thiếu trạng thái đơn hàng');
        }
        
        if (!in_array($data['status'], $validStatuses)) {
            sendResponse(400, false, 'Trạng thái đơn hàng không hợp lệ');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get current order status
            $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            if (!$order) {
                throw new Exception('Không tìm thấy đơn hàng');
            }
            
            // Handle status change
            if ($data['status'] === 'canceled' && $order['status'] !== 'pending') {
                throw new Exception('Chỉ có thể hủy đơn hàng ở trạng thái chờ xử lý');
            }
            
            if ($data['status'] === 'canceled') {
                // Restore product quantities
                $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $items = $result->fetch_all(MYSQLI_ASSOC);
                
                foreach ($items as $item) {
                    $stmt = $conn->prepare("UPDATE products 
                                          SET quantity = quantity + ? 
                                          WHERE id = ?");
                    $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt->execute();
                }
            }
            
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $data['status'], $id);
            $stmt->execute();
            
            $conn->commit();
            sendResponse(200, true, 'Cập nhật trạng thái đơn hàng thành công');
            
        } catch (Exception $e) {
            $conn->rollback();
            sendResponse(400, false, $e->getMessage());
        }
        break;

    case 'DELETE':
        sendResponse(405, false, 'Không cho phép xóa đơn hàng');
        break;

    default:
        sendResponse(405, false, 'Phương thức không được hỗ trợ');
}