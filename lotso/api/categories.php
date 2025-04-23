<?php
// Bật báo cáo lỗi
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// Đảm bảo luôn trả về JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

error_log("Starting categories API request");

try {
    // Kết nối trực tiếp đến database lotso
    $conn = new mysqli("localhost", "root", "", "lotso");
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Không thể kết nối đến database: " . $conn->connect_error);
    }

    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
        throw new Exception("Lỗi thiết lập charset utf8mb4");
    }

    error_log("Database connection successful");

    // Xử lý GET request
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Processing GET request");
        
        // Kiểm tra bảng categories có tồn tại
        $result = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($result->num_rows === 0) {
            error_log("Categories table does not exist");
            throw new Exception("Bảng categories không tồn tại");
        }

        // Lấy cấu trúc bảng
        $result = $conn->query("DESCRIBE categories");
        error_log("Table structure: " . json_encode($result->fetch_all(MYSQLI_ASSOC)));

        // Xây dựng câu query với điều kiện type nếu có
        $sql = "SELECT * FROM categories";
        if (isset($_GET['type'])) {
            $type = $conn->real_escape_string($_GET['type']);
            $sql .= " WHERE type = '$type'";
            error_log("Filtering by type: " . $type);
        }
        $sql .= " ORDER BY created_at DESC";
        
        error_log("Executing query: " . $sql);
        
        $result = $conn->query($sql);
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            throw new Exception("Lỗi truy vấn: " . $conn->error);
        }

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            error_log("Found category: " . json_encode($row));
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => isset($row['description']) ? $row['description'] : '',
                'status' => isset($row['status']) ? (int)$row['status'] : 1,
                'type' => isset($row['type']) ? $row['type'] : 'product'
            ];
        }

        error_log("Total categories found: " . count($categories));
        error_log("Categories data: " . json_encode($categories));

        echo json_encode([
            'success' => true,
            'message' => 'Lấy danh sách danh mục thành công',
            'data' => $categories
        ]);
        exit;
    }

    // Xử lý POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new Exception("Tên danh mục không được để trống");
        }

        $name = trim($data['name']);
        $slug = isset($data['slug']) ? trim($data['slug']) : strtolower(str_replace(' ', '-', $name));
        $description = isset($data['description']) ? trim($data['description']) : '';
        $status = isset($data['status']) ? (int)$data['status'] : 1;
        $type = isset($data['type']) ? trim($data['type']) : 'product';

        $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, status, type) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
        }

        $stmt->bind_param("sssis", $name, $slug, $description, $status, $type);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi thêm danh mục: " . $stmt->error);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Thêm danh mục thành công',
            'data' => ['id' => $stmt->insert_id]
        ]);
        exit;
    }

    // Xử lý PUT request
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!isset($_GET['id'])) {
            throw new Exception("Thiếu ID danh mục");
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new Exception("Tên danh mục không được để trống");
        }

        $id = (int)$_GET['id'];
        $name = trim($data['name']);
        $slug = isset($data['slug']) ? trim($data['slug']) : strtolower(str_replace(' ', '-', $name));
        $description = isset($data['description']) ? trim($data['description']) : '';
        $status = isset($data['status']) ? (int)$data['status'] : 1;
        $type = isset($data['type']) ? trim($data['type']) : 'product';

        $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, status = ?, type = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
        }

        $stmt->bind_param("sssisi", $name, $slug, $description, $status, $type, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi cập nhật danh mục: " . $stmt->error);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công'
        ]);
        exit;
    }

    // Xử lý DELETE request
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            throw new Exception("Thiếu ID danh mục");
        }

        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh: " . $conn->error);
        }

        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi xóa danh mục: " . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("Không tìm thấy danh mục");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
        exit;
    }

    throw new Exception("Phương thức không được hỗ trợ");
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
                // Lấy thông tin một danh mục
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                    sendResponse(400, false, 'ID danh mục không hợp lệ');
                }

                $stmt = $conn->prepare("
                    SELECT c.*, p.name as parent_name 
                    FROM categories c 
                    LEFT JOIN categories p ON c.parent_id = p.id 
                    WHERE c.id = ?
                ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();

                if ($category) {
                    sendResponse(200, true, 'Lấy thông tin danh mục thành công', $category);
                } else {
                    sendResponse(404, false, 'Không tìm thấy danh mục');
                }
        } else {
                // Lấy danh sách tất cả danh mục
                $type = isset($_GET['type']) ? $_GET['type'] : 'product'; // Mặc định lấy danh mục sản phẩm
                $stmt = $conn->prepare("
                    SELECT c.*, p.name as parent_name
                                  FROM categories c 
                    LEFT JOIN categories p ON c.parent_id = p.id
                    WHERE c.type = ?
                    ORDER BY c.id DESC
                ");
                $stmt->bind_param("s", $type);
            $stmt->execute();
            $result = $stmt->get_result();
            $categories = $result->fetch_all(MYSQLI_ASSOC);
            
                if (!is_array($categories)) {
                    $categories = [];
                }
                
                // Định dạng lại dữ liệu trả về
                $formattedCategories = array_map(function($category) {
                    return [
                        'id' => (int)$category['id'],
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'status' => $category['status'],
                        'parent_id' => $category['parent_id'] ? (int)$category['parent_id'] : null,
                        'parent_name' => $category['parent_name'],
                        'slug' => $category['slug'],
                        'type' => $category['type'],
                        'created_at' => $category['created_at']
                    ];
                }, $categories);
                
                sendResponse(200, true, 'Lấy danh sách danh mục thành công', [
                    'categories' => $formattedCategories,
                    'total' => count($formattedCategories)
                ]);
        }
        break;

    case 'POST':
            // Debug log
            error_log('Received POST data: ' . print_r($data, true));
            
            // Validate dữ liệu đầu vào
            if (!isset($data['name']) || empty(trim($data['name']))) {
                sendResponse(400, false, 'Tên danh mục không được để trống');
            }

            // Tạo slug từ tên
            $slug = createSlug($data['name']);
            
            // Kiểm tra slug đã tồn tại chưa
            $checkSql = "SELECT id FROM categories WHERE slug = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $slug);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                sendResponse(400, false, 'Slug đã tồn tại');
            }

            // Thêm danh mục mới
            $sql = "INSERT INTO categories (name, slug, description, parent_id, status, type) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log('Prepare statement failed: ' . $conn->error);
                sendResponse(500, false, 'Lỗi khi chuẩn bị câu lệnh SQL');
            }

            $parentId = isset($data['parent_id']) ? $data['parent_id'] : null;
            $status = isset($data['status']) ? $data['status'] : 'active';
            $description = isset($data['description']) ? $data['description'] : null;
            $type = isset($data['type']) ? $data['type'] : 'product';

            $stmt->bind_param("ssisis", 
            $data['name'],
                $slug,
                $description,
                $parentId,
                $status,
                $type
        );
        
        if ($stmt->execute()) {
                sendResponse(201, true, 'Thêm danh mục thành công', ['id' => $stmt->insert_id]);
        } else {
                error_log('Execute statement failed: ' . $stmt->error);
                sendResponse(500, false, 'Lỗi khi thêm danh mục: ' . $stmt->error);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
                sendResponse(400, false, 'Thiếu ID danh mục');
            }
            
            $id = $_GET['id'];
            
            // Validate dữ liệu đầu vào
            if (!isset($data['name']) || empty(trim($data['name']))) {
                sendResponse(400, false, 'Tên danh mục không được để trống');
            }

            // Tạo slug từ tên
            $slug = createSlug($data['name']);
            
            // Kiểm tra slug đã tồn tại chưa (trừ danh mục hiện tại)
            $checkSql = "SELECT id FROM categories WHERE slug = ? AND id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $slug, $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                sendResponse(400, false, 'Slug đã tồn tại');
            }

            // Cập nhật danh mục
            $sql = "UPDATE categories SET 
                    name = ?, 
                    slug = ?,
                    description = ?,
                    parent_id = ?,
                    status = ?,
                    type = ?
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $parentId = isset($data['parent_id']) ? $data['parent_id'] : null;
            $status = isset($data['status']) ? $data['status'] : 'active';
            $description = isset($data['description']) ? $data['description'] : null;
            $type = isset($data['type']) ? $data['type'] : 'product';

            $stmt->bind_param("sssisi", 
                $data['name'],
                $slug,
                $description,
                $parentId,
                $status,
                $type,
                $id
            );
        
        if ($stmt->execute()) {
                sendResponse(200, true, 'Cập nhật danh mục thành công');
            } else {
                sendResponse(500, false, 'Lỗi khi cập nhật danh mục: ' . $stmt->error);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
                sendResponse(400, false, 'Thiếu ID danh mục');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
                sendResponse(400, false, 'ID danh mục không hợp lệ');
            }

            // Kiểm tra xem có bài viết nào thuộc danh mục này không
            $checkSql = "SELECT COUNT(*) as count FROM posts WHERE category_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                sendResponse(400, false, 'Không thể xóa danh mục đang có bài viết');
            }

            // Kiểm tra xem có danh mục con không
            $checkSql = "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                sendResponse(400, false, 'Không thể xóa danh mục đang có danh mục con');
        }
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                    sendResponse(200, true, 'Xóa danh mục thành công');
                } else {
                    sendResponse(404, false, 'Không tìm thấy danh mục');
                }
            } else {
                sendResponse(500, false, 'Lỗi khi xóa danh mục: ' . $stmt->error);
        }
        break;

    default:
            sendResponse(405, false, 'Phương thức không được hỗ trợ');
        break;
    }
} catch (Exception $e) {
    sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
}

// Hàm tạo slug từ tên
function createSlug($str) {
    $str = trim(mb_strtolower($str));
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return $str;
}