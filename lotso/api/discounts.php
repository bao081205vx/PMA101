<?php
// Tắt error reporting để tránh PHP trả về HTML
error_reporting(0);
ini_set('display_errors', 0);

// Đảm bảo luôn trả về JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Hàm xử lý lỗi tổng quát
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    sendResponse(500, false, 'Lỗi hệ thống: ' . $errstr);
    exit;
}

// Đăng ký handler xử lý lỗi
set_error_handler('handleError');

// Đăng ký handler xử lý exception
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
    exit;
});

require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';
require_once __DIR__ . '/../functions/validate.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception("Không thể kết nối database");
    }

    $method = $_SERVER['REQUEST_METHOD'];

    // Đọc và validate input JSON cho POST và PUT
    if ($method === 'POST' || $method === 'PUT') {
        $input = file_get_contents('php://input');
        if (!$input) {
            sendResponse(400, false, 'Dữ liệu đầu vào không hợp lệ');
        }
        
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendResponse(400, false, 'Dữ liệu JSON không hợp lệ: ' . json_last_error_msg());
        }
    }

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single discount
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    sendResponse(400, false, 'ID mã giảm giá không hợp lệ');
                }

                $stmt = $conn->prepare("SELECT * FROM discounts WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $discount = $result->fetch_assoc();

                if ($discount) {
                    sendResponse(200, true, 'Lấy thông tin mã giảm giá thành công', $discount);
                } else {
                    sendResponse(404, false, 'Không tìm thấy mã giảm giá');
                }
            } else if (isset($_GET['code'])) {
                // Get discount by code
                $code = $_GET['code'];
                $stmt = $conn->prepare("SELECT * FROM discounts WHERE code = ? AND start_date <= CURDATE() AND end_date >= CURDATE()");
                $stmt->bind_param("s", $code);
                $stmt->execute();
                $result = $stmt->get_result();
                $discount = $result->fetch_assoc();

                if ($discount) {
                    sendResponse(true, 'Valid discount code', $discount);
                } else {
                    sendResponse(false, 'Invalid or expired discount code', null, 404);
                }
            } else {
                // Get all discounts
                $stmt = $conn->prepare("SELECT *, 
                                      CASE 
                                          WHEN CURDATE() < start_date THEN 'upcoming'
                                          WHEN CURDATE() > end_date THEN 'expired'
                                          ELSE 'active'
                                      END as status
                                      FROM discounts
                                      ORDER BY created_at DESC");
                $stmt->execute();
                $result = $stmt->get_result();
                $discounts = $result->fetch_all(MYSQLI_ASSOC);
                
                if (!is_array($discounts)) {
                    $discounts = [];
                }
                
                sendResponse(200, true, 'Lấy danh sách mã giảm giá thành công', [
                    'discounts' => $discounts
                ]);
            }
            break;

        case 'POST':
            // Debug log
            error_log('Received POST data: ' . print_r($data, true));
            
            // Validate dữ liệu đầu vào
            if (!isset($data['code']) || !isset($data['type']) || !isset($data['value']) || 
                !isset($data['start_date']) || !isset($data['end_date'])) {
                sendResponse(false, 'Thiếu thông tin bắt buộc', null, 400);
                exit;
            }

            // Validate và format ngày tháng
            try {
                $startDate = new DateTime($data['start_date']);
                $endDate = new DateTime($data['end_date']);
                
                // Format lại ngày tháng theo định dạng MySQL
                $data['start_date'] = $startDate->format('Y-m-d H:i:s');
                $data['end_date'] = $endDate->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                sendResponse(false, 'Định dạng ngày tháng không hợp lệ', null, 400);
                exit;
            }

            // Kiểm tra mã giảm giá đã tồn tại chưa
            $checkSql = "SELECT id FROM discounts WHERE code = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $data['code']);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                sendResponse(false, 'Mã giảm giá đã tồn tại', null, 400);
                exit;
            }

            // Validate giá trị giảm giá
            if (!is_numeric($data['value'])) {
                sendResponse(false, 'Giá trị giảm giá phải là số', null, 400);
                exit;
            }
            
            if ($data['type'] === 'percentage' && ($data['value'] < 0 || $data['value'] > 100)) {
                sendResponse(false, 'Giá trị phần trăm phải từ 0 đến 100', null, 400);
                exit;
            }

            // Validate số lượng và giá trị tối thiểu
            if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
                sendResponse(false, 'Số lượng không hợp lệ', null, 400);
                exit;
            }

            if (!is_numeric($data['minimum_order']) || $data['minimum_order'] < 0) {
                sendResponse(false, 'Giá trị đơn hàng tối thiểu không hợp lệ', null, 400);
                exit;
            }
            
            // Debug log trước khi insert
            error_log('Prepared data for insert: ' . print_r($data, true));
            
            // Thêm mã giảm giá mới
            $sql = "INSERT INTO discounts (code, type, value, quantity, minimum_order, start_date, end_date, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log('Prepare statement failed: ' . $conn->error);
                sendResponse(false, 'Lỗi khi chuẩn bị câu lệnh SQL', null, 500);
                exit;
            }

            $stmt->bind_param("ssdiisss", 
                $data['code'],
                $data['type'],
                $data['value'],
                $data['quantity'],
                $data['minimum_order'],
                $data['start_date'],
                $data['end_date'],
                $data['description']
            );

            try {
                if ($stmt->execute()) {
                    sendResponse(true, 'Thêm mã giảm giá thành công', ['id' => $stmt->insert_id]);
                } else {
                    error_log('Execute statement failed: ' . $stmt->error);
                    sendResponse(false, 'Lỗi khi thêm mã giảm giá: ' . $stmt->error, null, 500);
                }
            } catch (Exception $e) {
                error_log('Exception during execute: ' . $e->getMessage());
                sendResponse(false, 'Lỗi khi thêm mã giảm giá: ' . $e->getMessage(), null, 500);
            }
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                sendResponse(false, 'Thiếu ID mã giảm giá', null, 400);
                exit;
            }
            
            $id = $_GET['id'];
            $data = json_decode(file_get_contents('php://input'), true);

            // Validate dữ liệu đầu vào
            if (!isset($data['code']) || !isset($data['type']) || !isset($data['value']) || 
                !isset($data['start_date']) || !isset($data['end_date'])) {
                sendResponse(false, 'Thiếu thông tin bắt buộc', null, 400);
                exit;
            }

            // Kiểm tra mã giảm giá có tồn tại không
            $checkSql = "SELECT id FROM discounts WHERE code = ? AND id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $data['code'], $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                sendResponse(false, 'Mã giảm giá đã tồn tại', null, 400);
                exit;
            }

            // Validate giá trị giảm giá
            if (!is_numeric($data['value'])) {
                sendResponse(false, 'Giá trị giảm giá phải là số', null, 400);
                exit;
            }
            
            if ($data['type'] === 'percentage' && ($data['value'] < 0 || $data['value'] > 100)) {
                sendResponse(false, 'Giá trị phần trăm phải từ 0 đến 100', null, 400);
                exit;
            }
            
            // Validate dates if provided
            if (isset($data['start_date'])) {
                validateDate($data['start_date']);
            }
            if (isset($data['end_date'])) {
                validateDate($data['end_date']);
            }
            
            if ($data['start_date'] > $data['end_date']) {
                sendResponse(400, false, 'Ngày kết thúc phải sau ngày bắt đầu');
            }
            
            // Cập nhật mã giảm giá
            $sql = "UPDATE discounts SET 
                    code = ?, 
                    type = ?, 
                    value = ?,
                    quantity = ?,
                    minimum_order = ?,
                    start_date = ?,
                    end_date = ?,
                    description = ?
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiisssi", 
                $data['code'],
                $data['type'],
                $data['value'],
                $data['quantity'],
                $data['minimum_order'],
                $data['start_date'],
                $data['end_date'],
                $data['description'],
                $id
            );

            try {
                if ($stmt->execute()) {
                    sendResponse(true, 'Cập nhật mã giảm giá thành công');
                } else {
                    sendResponse(false, 'Lỗi khi cập nhật mã giảm giá: ' . $stmt->error, null, 500);
                }
            } catch (Exception $e) {
                sendResponse(false, 'Lỗi khi cập nhật mã giảm giá: ' . $e->getMessage(), null, 500);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                sendResponse(false, 'Discount ID is required', null, 400);
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(false, 'Invalid discount ID', null, 400);
            }
            
            $stmt = $conn->prepare("DELETE FROM discounts WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(true, 'Discount deleted successfully');
                } else {
                    sendResponse(false, 'Discount not found', null, 404);
                }
            } else {
                sendResponse(false, 'Failed to delete discount', null, 500);
            }
            break;

        default:
            sendResponse(false, 'Method not allowed', null, 405);
            break;
    }
} catch (Exception $e) {
    sendResponse(false, 'Lỗi hệ thống: ' . $e->getMessage(), null, 500);
}