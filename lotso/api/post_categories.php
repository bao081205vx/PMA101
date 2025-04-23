<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_log("Starting post categories API request");

// Đảm bảo luôn trả về JSON và cho phép CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    
    error_log("Connecting to shoe_store database");
    $database = new Database(); // Kết nối tới shoe_store
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("Database connection failed");
        throw new Exception("Không thể kết nối đến database shoe_store");
    }
    error_log("Database connection successful");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Processing GET request for post categories");
        
        // Kiểm tra bảng tồn tại
        $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($tableCheck->num_rows === 0) {
            error_log("Categories table does not exist");
            throw new Exception("Bảng categories không tồn tại");
        }
        error_log("Categories table exists");
        
        $sql = "SELECT * FROM categories ORDER BY created_at DESC";
        error_log("Executing query: " . $sql);
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            throw new Exception("Lỗi truy vấn: " . $conn->error);
        }
        
        $categories = [];
        error_log("Processing results");
        while ($row = $result->fetch_assoc()) {
            error_log("Found category: " . json_encode($row));
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        error_log("Found " . count($categories) . " post categories");
        error_log("Sending response");
        sendResponse(200, true, "Lấy danh sách danh mục bài viết thành công", $categories);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi: " . $e->getMessage());
}
?> 
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_log("Starting post categories API request");

// Đảm bảo luôn trả về JSON và cho phép CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    
    error_log("Connecting to shoe_store database");
    $database = new Database(); // Kết nối tới shoe_store
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("Database connection failed");
        throw new Exception("Không thể kết nối đến database shoe_store");
    }
    error_log("Database connection successful");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Processing GET request for post categories");
        
        // Kiểm tra bảng tồn tại
        $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($tableCheck->num_rows === 0) {
            error_log("Categories table does not exist");
            throw new Exception("Bảng categories không tồn tại");
        }
        error_log("Categories table exists");
        
        $sql = "SELECT * FROM categories ORDER BY created_at DESC";
        error_log("Executing query: " . $sql);
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            throw new Exception("Lỗi truy vấn: " . $conn->error);
        }
        
        $categories = [];
        error_log("Processing results");
        while ($row = $result->fetch_assoc()) {
            error_log("Found category: " . json_encode($row));
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        error_log("Found " . count($categories) . " post categories");
        error_log("Sending response");
        sendResponse(200, true, "Lấy danh sách danh mục bài viết thành công", $categories);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi: " . $e->getMessage());
}
?> 
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_log("Starting post categories API request");

// Đảm bảo luôn trả về JSON và cho phép CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    
    error_log("Connecting to shoe_store database");
    $database = new Database(); // Kết nối tới shoe_store
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("Database connection failed");
        throw new Exception("Không thể kết nối đến database shoe_store");
    }
    error_log("Database connection successful");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Processing GET request for post categories");
        
        // Kiểm tra bảng tồn tại
        $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($tableCheck->num_rows === 0) {
            error_log("Categories table does not exist");
            throw new Exception("Bảng categories không tồn tại");
        }
        error_log("Categories table exists");
        
        $sql = "SELECT * FROM categories ORDER BY created_at DESC";
        error_log("Executing query: " . $sql);
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            throw new Exception("Lỗi truy vấn: " . $conn->error);
        }
        
        $categories = [];
        error_log("Processing results");
        while ($row = $result->fetch_assoc()) {
            error_log("Found category: " . json_encode($row));
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        error_log("Found " . count($categories) . " post categories");
        error_log("Sending response");
        sendResponse(200, true, "Lấy danh sách danh mục bài viết thành công", $categories);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi: " . $e->getMessage());
}
?> 
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_log("Starting post categories API request");

// Đảm bảo luôn trả về JSON và cho phép CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    
    error_log("Connecting to shoe_store database");
    $database = new Database(); // Kết nối tới shoe_store
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("Database connection failed");
        throw new Exception("Không thể kết nối đến database shoe_store");
    }
    error_log("Database connection successful");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Processing GET request for post categories");
        
        // Kiểm tra bảng tồn tại
        $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($tableCheck->num_rows === 0) {
            error_log("Categories table does not exist");
            throw new Exception("Bảng categories không tồn tại");
        }
        error_log("Categories table exists");
        
        $sql = "SELECT * FROM categories ORDER BY created_at DESC";
        error_log("Executing query: " . $sql);
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            throw new Exception("Lỗi truy vấn: " . $conn->error);
        }
        
        $categories = [];
        error_log("Processing results");
        while ($row = $result->fetch_assoc()) {
            error_log("Found category: " . json_encode($row));
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        error_log("Found " . count($categories) . " post categories");
        error_log("Sending response");
        sendResponse(200, true, "Lấy danh sách danh mục bài viết thành công", $categories);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi: " . $e->getMessage());
}
?> 
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_log("Starting post categories API request");

// Đảm bảo luôn trả về JSON và cho phép CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    
    error_log("Connecting to shoe_store database");
    $database = new Database(); // Kết nối tới shoe_store
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("Database connection failed");
        throw new Exception("Không thể kết nối đến database shoe_store");
    }
    error_log("Database connection successful");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Processing GET request for post categories");
        
        // Kiểm tra bảng tồn tại
        $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($tableCheck->num_rows === 0) {
            error_log("Categories table does not exist");
            throw new Exception("Bảng categories không tồn tại");
        }
        error_log("Categories table exists");
        
        $sql = "SELECT * FROM categories ORDER BY created_at DESC";
        error_log("Executing query: " . $sql);
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            throw new Exception("Lỗi truy vấn: " . $conn->error);
        }
        
        $categories = [];
        error_log("Processing results");
        while ($row = $result->fetch_assoc()) {
            error_log("Found category: " . json_encode($row));
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        error_log("Found " . count($categories) . " post categories");
        error_log("Sending response");
        sendResponse(200, true, "Lấy danh sách danh mục bài viết thành công", $categories);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi: " . $e->getMessage());
}
?> 
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_log("Starting post categories API request");

// Đảm bảo luôn trả về JSON và cho phép CORS
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    
    error_log("Connecting to shoe_store database");
    $database = new Database(); // Kết nối tới shoe_store
    $conn = $database->getConnection();

    if (!$conn) {
        error_log("Database connection failed");
        throw new Exception("Không thể kết nối đến database shoe_store");
    }
    error_log("Database connection successful");

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("Processing GET request for post categories");
        
        // Kiểm tra bảng tồn tại
        $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($tableCheck->num_rows === 0) {
            error_log("Categories table does not exist");
            throw new Exception("Bảng categories không tồn tại");
        }
        error_log("Categories table exists");
        
        $sql = "SELECT * FROM categories ORDER BY created_at DESC";
        error_log("Executing query: " . $sql);
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            error_log("Query failed: " . $conn->error);
            throw new Exception("Lỗi truy vấn: " . $conn->error);
        }
        
        $categories = [];
        error_log("Processing results");
        while ($row = $result->fetch_assoc()) {
            error_log("Found category: " . json_encode($row));
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        error_log("Found " . count($categories) . " post categories");
        error_log("Sending response");
        sendResponse(200, true, "Lấy danh sách danh mục bài viết thành công", $categories);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(500, false, "Lỗi: " . $e->getMessage());
}
?> 