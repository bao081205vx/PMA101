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
            // Get single post
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(400, false, 'ID bài viết không hợp lệ');
            }

            $stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.username as author_name 
                                  FROM posts p 
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  LEFT JOIN users u ON p.author_id = u.id 
                                  WHERE p.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $post = $result->fetch_assoc();

            if (!$post) {
                sendResponse(404, false, 'Không tìm thấy bài viết');
            }

            sendResponse(200, true, 'Lấy thông tin bài viết thành công', $post);
        } else {
            // Get all posts with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;

            // Get total posts count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM posts");
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];

            // Get posts for current page
            $stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.username as author_name 
                                  FROM posts p 
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  LEFT JOIN users u ON p.author_id = u.id 
                                  ORDER BY p.created_at DESC 
                                  LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $posts = $result->fetch_all(MYSQLI_ASSOC);

            sendResponse(200, true, 'Lấy danh sách bài viết thành công', [
                'posts' => $posts,
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
        $required = ['title', 'slug', 'content'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                sendResponse(400, false, "Thiếu trường $field");
            }
        }
        
        // Check if slug exists
        $stmt = $conn->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt->bind_param("s", $data['slug']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            sendResponse(400, false, 'Slug đã tồn tại');
        }
        
        // Set default values
        $status = isset($data['status']) && in_array($data['status'], ['draft', 'published']) 
            ? $data['status'] 
            : 'draft';
        
        $categoryId = isset($data['category_id']) && !empty($data['category_id']) 
            ? $data['category_id'] 
            : null;
            
        // Get current user ID (you should implement proper authentication)
        $authorId = 1; // Temporary hardcoded value
        
        // Create post
        $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, category_id, author_id, status) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", 
            $data['title'],
            $data['slug'],
            $data['content'],
            $categoryId,
            $authorId,
            $status
        );
        
        if ($stmt->execute()) {
            $postId = $stmt->insert_id;
            
            // Get created post
            $stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.username as author_name 
                                  FROM posts p 
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  LEFT JOIN users u ON p.author_id = u.id 
                                  WHERE p.id = ?");
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $post = $stmt->get_result()->fetch_assoc();
            
            sendResponse(201, true, 'Tạo bài viết thành công', $post);
        } else {
            sendResponse(500, false, 'Lỗi khi tạo bài viết');
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Thiếu ID bài viết');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'ID bài viết không hợp lệ');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            sendResponse(400, false, 'Không có dữ liệu cập nhật');
        }
        
        // Check if post exists
        $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(404, false, 'Không tìm thấy bài viết');
        }
        
        // Check if slug exists
        if (isset($data['slug'])) {
            $stmt = $conn->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
            $stmt->bind_param("si", $data['slug'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                sendResponse(400, false, 'Slug đã tồn tại');
            }
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        $types = "";
        
        if (isset($data['title'])) {
            $updateFields[] = "title = ?";
            $params[] = $data['title'];
            $types .= "s";
        }
        
        if (isset($data['slug'])) {
            $updateFields[] = "slug = ?";
            $params[] = $data['slug'];
            $types .= "s";
        }
        
        if (isset($data['content'])) {
            $updateFields[] = "content = ?";
            $params[] = $data['content'];
            $types .= "s";
        }
        
        if (isset($data['category_id'])) {
            $updateFields[] = "category_id = ?";
            $params[] = $data['category_id'];
            $types .= "i";
        }
        
        if (isset($data['status']) && in_array($data['status'], ['draft', 'published'])) {
            $updateFields[] = "status = ?";
            $params[] = $data['status'];
            $types .= "s";
        }
        
        if (empty($updateFields)) {
            sendResponse(400, false, 'Không có trường nào được cập nhật');
        }
        
        // Update post
        $query = "UPDATE posts SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Get updated post
            $stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.username as author_name 
                                  FROM posts p 
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  LEFT JOIN users u ON p.author_id = u.id 
                                  WHERE p.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $post = $stmt->get_result()->fetch_assoc();
            
            sendResponse(200, true, 'Cập nhật bài viết thành công', $post);
        } else {
            sendResponse(500, false, 'Lỗi khi cập nhật bài viết');
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Thiếu ID bài viết');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'ID bài viết không hợp lệ');
        }
        
        // Check if post exists
        $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            sendResponse(404, false, 'Không tìm thấy bài viết');
        }
        
        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            sendResponse(200, true, 'Xóa bài viết thành công');
        } else {
            sendResponse(500, false, 'Lỗi khi xóa bài viết');
        }
        break;

    default:
        sendResponse(405, false, 'Phương thức không được hỗ trợ');
}
