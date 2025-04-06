<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lấy tất cả cài đặt
        $stmt = $conn->prepare("SELECT * FROM settings");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['key']] = $row['value'];
        }
        
        sendResponse(200, true, 'Lấy cài đặt thành công', $settings);
        break;

    case 'POST':
        // Kiểm tra endpoint cụ thể
        $endpoint = isset($_GET['type']) ? $_GET['type'] : '';
        
        switch ($endpoint) {
            case 'store':
                // Cập nhật thông tin cửa hàng
                $storeName = $_POST['store_name'] ?? '';
                $storeAddress = $_POST['store_address'] ?? '';
                $storePhone = $_POST['store_phone'] ?? '';
                $storeEmail = $_POST['store_email'] ?? '';

                // Xử lý upload logo nếu có
                if (isset($_FILES['store_logo'])) {
                    $logo = $_FILES['store_logo'];
                    $logoPath = '../uploads/logo/' . time() . '_' . $logo['name'];
                    if (move_uploaded_file($logo['tmp_name'], $logoPath)) {
                        updateSetting($conn, 'store_logo', $logoPath);
                    }
                }

                updateSetting($conn, 'store_name', $storeName);
                updateSetting($conn, 'store_address', $storeAddress);
                updateSetting($conn, 'store_phone', $storePhone);
                updateSetting($conn, 'store_email', $storeEmail);

                sendResponse(200, true, 'Cập nhật thông tin cửa hàng thành công');
                break;

            case 'seo':
                // Cập nhật cài đặt SEO
                $data = json_decode(file_get_contents('php://input'), true);
                
                updateSetting($conn, 'meta_title', $data['meta_title'] ?? '');
                updateSetting($conn, 'meta_description', $data['meta_description'] ?? '');
                updateSetting($conn, 'meta_keywords', $data['meta_keywords'] ?? '');

                sendResponse(200, true, 'Cập nhật cài đặt SEO thành công');
                break;

            case 'social':
                // Cập nhật cài đặt mạng xã hội
                $data = json_decode(file_get_contents('php://input'), true);
                
                updateSetting($conn, 'facebook', $data['facebook'] ?? '');
                updateSetting($conn, 'instagram', $data['instagram'] ?? '');
                updateSetting($conn, 'youtube', $data['youtube'] ?? '');

                sendResponse(200, true, 'Cập nhật cài đặt mạng xã hội thành công');
                break;

            default:
                sendResponse(400, false, 'Endpoint không hợp lệ');
                break;
        }
        break;

    default:
        sendResponse(405, false, 'Phương thức không được hỗ trợ');
        break;
}

function updateSetting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->bind_param('sss', $key, $value, $value);
    $stmt->execute();
}
