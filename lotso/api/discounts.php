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
            // Get single discount
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(400, false, 'Invalid discount ID');
            }

            $stmt = $conn->prepare("SELECT * FROM discounts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $discount = $result->fetch_assoc();

            if ($discount) {
                sendResponse(200, true, 'Discount retrieved successfully', $discount);
            } else {
                sendResponse(404, false, 'Discount not found');
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
                sendResponse(200, true, 'Valid discount code', $discount);
            } else {
                sendResponse(404, false, 'Invalid or expired discount code');
            }
        } else {
            // Get all active discounts
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
            
            sendResponse(200, true, 'Discounts retrieved successfully', $discounts);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['code', 'discount_type', 'discount_value', 'start_date', 'end_date'];
        validateRequiredFields($data, $required);
        
        // Validate discount type
        if (!in_array($data['discount_type'], ['percentage', 'fixed'])) {
            sendResponse(400, false, 'Invalid discount type. Must be either "percentage" or "fixed"');
        }
        
        // Validate discount value
        if (!is_numeric($data['discount_value']) || $data['discount_value'] <= 0) {
            sendResponse(400, false, 'Invalid discount value');
        }
        
        // If percentage, validate range
        if ($data['discount_type'] === 'percentage' && ($data['discount_value'] < 0 || $data['discount_value'] > 100)) {
            sendResponse(400, false, 'Percentage discount must be between 0 and 100');
        }
        
        // Validate dates
        validateDate($data['start_date']);
        validateDate($data['end_date']);
        
        if ($data['start_date'] > $data['end_date']) {
            sendResponse(400, false, 'End date must be after start date');
        }
        
        // Check if code already exists
        $stmt = $conn->prepare("SELECT id FROM discounts WHERE code = ?");
        $stmt->bind_param("s", $data['code']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            sendResponse(400, false, 'Discount code already exists');
        }
        
        $stmt = $conn->prepare("INSERT INTO discounts (code, discount_type, discount_value, start_date, end_date) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", 
            $data['code'],
            $data['discount_type'],
            $data['discount_value'],
            $data['start_date'],
            $data['end_date']
        );
        
        if ($stmt->execute()) {
            $data['id'] = $stmt->insert_id;
            sendResponse(201, true, 'Discount created successfully', $data);
        } else {
            sendResponse(500, false, 'Failed to create discount');
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Discount ID is required');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'Invalid discount ID');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            sendResponse(400, false, 'No data provided for update');
        }
        
        // If updating code, check uniqueness
        if (isset($data['code'])) {
            $stmt = $conn->prepare("SELECT id FROM discounts WHERE code = ? AND id != ?");
            $stmt->bind_param("si", $data['code'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                sendResponse(400, false, 'Discount code already exists');
            }
        }
        
        // Validate discount type if provided
        if (isset($data['discount_type']) && !in_array($data['discount_type'], ['percentage', 'fixed'])) {
            sendResponse(400, false, 'Invalid discount type. Must be either "percentage" or "fixed"');
        }
        
        // Validate discount value if provided
        if (isset($data['discount_value'])) {
            if (!is_numeric($data['discount_value']) || $data['discount_value'] <= 0) {
                sendResponse(400, false, 'Invalid discount value');
            }
            if (isset($data['discount_type']) && $data['discount_type'] === 'percentage' && 
                ($data['discount_value'] < 0 || $data['discount_value'] > 100)) {
                sendResponse(400, false, 'Percentage discount must be between 0 and 100');
            }
        }
        
        // Validate dates if provided
        if (isset($data['start_date'])) {
            validateDate($data['start_date']);
        }
        if (isset($data['end_date'])) {
            validateDate($data['end_date']);
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        $types = "";
        
        if (isset($data['code'])) {
            $updateFields[] = "code = ?";
            $params[] = $data['code'];
            $types .= "s";
        }
        if (isset($data['discount_type'])) {
            $updateFields[] = "discount_type = ?";
            $params[] = $data['discount_type'];
            $types .= "s";
        }
        if (isset($data['discount_value'])) {
            $updateFields[] = "discount_value = ?";
            $params[] = $data['discount_value'];
            $types .= "d";
        }
        if (isset($data['start_date'])) {
            $updateFields[] = "start_date = ?";
            $params[] = $data['start_date'];
            $types .= "s";
        }
        if (isset($data['end_date'])) {
            $updateFields[] = "end_date = ?";
            $params[] = $data['end_date'];
            $types .= "s";
        }
        
        if (empty($updateFields)) {
            sendResponse(400, false, 'No valid fields provided for update');
        }
        
        $query = "UPDATE discounts SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, true, 'Discount updated successfully');
            } else {
                sendResponse(404, false, 'Discount not found or no changes made');
            }
        } else {
            sendResponse(500, false, 'Failed to update discount');
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Discount ID is required');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'Invalid discount ID');
        }
        
        $stmt = $conn->prepare("DELETE FROM discounts WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, true, 'Discount deleted successfully');
            } else {
                sendResponse(404, false, 'Discount not found');
            }
        } else {
            sendResponse(500, false, 'Failed to delete discount');
        }
        break;

    default:
        sendResponse(405, false, 'Method not allowed');
        break;
}