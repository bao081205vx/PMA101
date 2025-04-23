<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';
require_once __DIR__ . '/../functions/validate.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_log("Starting posts API request");

// Đảm bảo luôn trả về JSON
header('Content-Type: application/json; charset=UTF-8');

try {
    error_log("Initializing database connection");
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Không thể kết nối đến database");
    }

    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("GET params: " . json_encode($_GET));

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Kiểm tra kết nối
        if ($conn->connect_error) {
            throw new Exception("Lỗi kết nối database: " . $conn->connect_error);
        }

        // Kiểm tra bảng tồn tại
        $tableCheck = $conn->query("SHOW TABLES LIKE 'posts'");
        if ($tableCheck->num_rows === 0) {
            throw new Exception("Bảng posts không tồn tại");
        }

        if (isset($_GET['id'])) {
            error_log("Fetching single post with ID: " . $_GET['id']);
            $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                                  FROM posts p 
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  WHERE p.id = ?");
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
            }
            $stmt->bind_param("i", $_GET['id']);
        } else {
            error_log("Fetching all posts");
            $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                                  FROM posts p 
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  ORDER BY p.created_at DESC");
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
            }
        }

        error_log("Executing query");
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            throw new Exception("Lỗi thực thi truy vấn: " . $stmt->error);
        }

        error_log("Getting result");
            $result = $stmt->get_result();
        if (!$result) {
            error_log("Get result failed: " . $stmt->error);
            throw new Exception("Lỗi lấy kết quả: " . $stmt->error);
        }

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            error_log("Processing row: " . json_encode($row));
            $post = [
                'id' => $row['id'],
                'title' => $row['title'],
                'slug' => $row['slug'],
                'content' => $row['content'],
                'excerpt' => isset($row['excerpt']) ? $row['excerpt'] : '',
                'featured_image' => isset($row['featured_image']) ? $row['featured_image'] : '',
                'status' => isset($row['status']) ? (int)$row['status'] : 1,
                'category_id' => isset($row['category_id']) ? $row['category_id'] : null,
                'category_name' => isset($row['category_name']) ? $row['category_name'] : '',
                'author_id' => isset($row['author_id']) ? $row['author_id'] : null,
                'created_at' => isset($row['created_at']) ? $row['created_at'] : null,
                'updated_at' => isset($row['updated_at']) ? $row['updated_at'] : null
            ];
            $posts[] = $post;
        }

        error_log("Sending response with " . count($posts) . " posts");
        sendResponse(200, true, "Lấy danh sách bài viết thành công", [
                'posts' => $posts,
            'total' => count($posts)
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        if (!$input) {
            throw new Exception("Không có dữ liệu đầu vào");
        }
        
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Dữ liệu JSON không hợp lệ: " . json_last_error_msg());
        }

        error_log("Received data: " . json_encode($data));
        
        // Validate required fields
        $requiredFields = ['title', 'content', 'category_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                throw new Exception("Thiếu trường bắt buộc: {$field}");
            }
        }

        // Sanitize and validate data
        $title = trim($data['title']);
        $content = trim($data['content']);
        $category_id = (int)$data['category_id'];
        $excerpt = isset($data['excerpt']) ? trim($data['excerpt']) : '';
        $featured_image = isset($data['featured_image']) ? trim($data['featured_image']) : '';
        $status = isset($data['status']) ? (int)$data['status'] : 1;
        $author_id = isset($data['author_id']) ? (int)$data['author_id'] : 1;
        $slug = isset($data['slug']) ? trim($data['slug']) : createSlug($title);

        // Check if category exists
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND type = 'post'");
        if (!$checkStmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra danh mục");
        }
        $checkStmt->bind_param("i", $category_id);
        if (!$checkStmt->execute()) {
            throw new Exception("Lỗi kiểm tra danh mục");
        }
        if ($checkStmt->get_result()->num_rows === 0) {
            throw new Exception("Danh mục không tồn tại");
        }

        // Insert new post
        $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, excerpt, featured_image, status, category_id, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh thêm bài viết");
        }

        $stmt->bind_param("sssssiii", $title, $slug, $content, $excerpt, $featured_image, $status, $category_id, $author_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi thêm bài viết: " . $stmt->error);
        }

        $newId = $stmt->insert_id;
        sendResponse(201, true, "Thêm bài viết thành công", ['id' => $newId]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!isset($_GET['id'])) {
            throw new Exception("Thiếu ID bài viết");
        }

        $input = file_get_contents('php://input');
        if (!$input) {
            throw new Exception("Không có dữ liệu đầu vào");
        }
        
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Dữ liệu JSON không hợp lệ: " . json_last_error_msg());
        }

        $id = (int)$_GET['id'];
        if ($id <= 0) {
            throw new Exception("ID bài viết không hợp lệ");
        }
        
        // Check if post exists
        $checkStmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra bài viết");
        }
        $checkStmt->bind_param("i", $id);
        if (!$checkStmt->execute()) {
            throw new Exception("Lỗi kiểm tra bài viết");
        }
        if ($checkStmt->get_result()->num_rows === 0) {
            throw new Exception("Bài viết không tồn tại");
        }

        // Validate required fields
        $requiredFields = ['title', 'content', 'category_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                throw new Exception("Thiếu trường bắt buộc: {$field}");
            }
        }

        // Sanitize and validate data
        $title = trim($data['title']);
        $content = trim($data['content']);
        $category_id = (int)$data['category_id'];
        $excerpt = isset($data['excerpt']) ? trim($data['excerpt']) : '';
        $featured_image = isset($data['featured_image']) ? trim($data['featured_image']) : '';
        $status = isset($data['status']) ? (int)$data['status'] : 1;
        $slug = isset($data['slug']) ? trim($data['slug']) : createSlug($title);

        // Check if category exists
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND type = 'post'");
        if (!$checkStmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra danh mục");
        }
        $checkStmt->bind_param("i", $category_id);
        if (!$checkStmt->execute()) {
            throw new Exception("Lỗi kiểm tra danh mục");
        }
        if ($checkStmt->get_result()->num_rows === 0) {
            throw new Exception("Danh mục không tồn tại");
        }

        // Update post
        $stmt = $conn->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, status = ?, category_id = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh cập nhật bài viết");
        }

        $stmt->bind_param("sssssiii", $title, $slug, $content, $excerpt, $featured_image, $status, $category_id, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi cập nhật bài viết: " . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("Không có thay đổi nào được thực hiện");
        }

        sendResponse(200, true, "Cập nhật bài viết thành công");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            throw new Exception("Thiếu ID bài viết");
        }

        $id = (int)$_GET['id'];
        if ($id <= 0) {
            throw new Exception("ID bài viết không hợp lệ");
        }

        // Check if post exists
        $checkStmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra bài viết");
        }
        $checkStmt->bind_param("i", $id);
        if (!$checkStmt->execute()) {
            throw new Exception("Lỗi kiểm tra bài viết");
        }
        if ($checkStmt->get_result()->num_rows === 0) {
            throw new Exception("Bài viết không tồn tại");
        }

        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh xóa bài viết");
        }

        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi xóa bài viết: " . $stmt->error);
        }

        sendResponse(200, true, "Xóa bài viết thành công");
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi xử lý yêu cầu: " . $e->getMessage());
}

// Hàm tạo slug từ title
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

        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh xóa bài viết");
        }

        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi xóa bài viết: " . $stmt->error);
        }

        sendResponse(200, true, "Xóa bài viết thành công");
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi xử lý yêu cầu: " . $e->getMessage());
}

// Hàm tạo slug từ title
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

        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh xóa bài viết");
        }

            $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi xóa bài viết: " . $stmt->error);
        }

        sendResponse(200, true, "Xóa bài viết thành công");
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi xử lý yêu cầu: " . $e->getMessage());
}

// Hàm tạo slug từ title
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

        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh xóa bài viết");
        }

        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi xóa bài viết: " . $stmt->error);
        }

        sendResponse(200, true, "Xóa bài viết thành công");
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi xử lý yêu cầu: " . $e->getMessage());
}

// Hàm tạo slug từ title
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

        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh xóa bài viết");
        }

        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi xóa bài viết: " . $stmt->error);
        }

        sendResponse(200, true, "Xóa bài viết thành công");
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi xử lý yêu cầu: " . $e->getMessage());
}

// Hàm tạo slug từ title
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
        
        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị câu lệnh xóa bài viết");
        }

        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi xóa bài viết: " . $stmt->error);
        }

        sendResponse(200, true, "Xóa bài viết thành công");
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi xử lý yêu cầu: " . $e->getMessage());
}

// Hàm tạo slug từ title
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
