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
            // Get single category with its products
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                sendResponse(400, false, 'Invalid category ID');
            }

            // Get category details
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();

            if (!$category) {
                sendResponse(404, false, 'Category not found');
            }

            // Get products in this category
            $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);

            $category['products'] = $products;
            sendResponse(200, true, 'Category retrieved successfully', $category);
        } else {
            // Get all categories
            $stmt = $conn->prepare("SELECT c.*, COUNT(p.id) as product_count 
                                  FROM categories c 
                                  LEFT JOIN products p ON c.id = p.category_id 
                                  GROUP BY c.id");
            $stmt->execute();
            $result = $stmt->get_result();
            $categories = $result->fetch_all(MYSQLI_ASSOC);
            
            sendResponse(200, true, 'Categories retrieved successfully', $categories);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['name', 'slug'];
        validateRequiredFields($data, $required);
        
        // Validate slug uniqueness
        $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->bind_param("s", $data['slug']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            sendResponse(400, false, 'Slug already exists');
        }
        
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, image, status) 
                               VALUES (?, ?, ?, ?, ?)");
        $status = isset($data['status']) ? $data['status'] : 1;
        $stmt->bind_param("ssssi", 
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['image'] ?? null,
            $status
        );
        
        if ($stmt->execute()) {
            $data['id'] = $stmt->insert_id;
            sendResponse(201, true, 'Category created successfully', $data);
        } else {
            sendResponse(500, false, 'Failed to create category');
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Category ID is required');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'Invalid category ID');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data)) {
            sendResponse(400, false, 'No data provided for update');
        }
        
        // If updating slug, check uniqueness
        if (isset($data['slug'])) {
            $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $stmt->bind_param("si", $data['slug'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                sendResponse(400, false, 'Slug already exists');
            }
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        $types = "";
        
        if (isset($data['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $data['name'];
            $types .= "s";
        }
        if (isset($data['slug'])) {
            $updateFields[] = "slug = ?";
            $params[] = $data['slug'];
            $types .= "s";
        }
        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
            $types .= "s";
        }
        if (isset($data['image'])) {
            $updateFields[] = "image = ?";
            $params[] = $data['image'];
            $types .= "s";
        }
        if (isset($data['status'])) {
            $updateFields[] = "status = ?";
            $params[] = $data['status'];
            $types .= "i";
        }
        
        if (empty($updateFields)) {
            sendResponse(400, false, 'No valid fields provided for update');
        }
        
        $query = "UPDATE categories SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, true, 'Category updated successfully');
            } else {
                sendResponse(404, false, 'Category not found or no changes made');
            }
        } else {
            sendResponse(500, false, 'Failed to update category');
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Category ID is required');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'Invalid category ID');
        }
        
        // Check if category has products
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $productCount = $result->fetch_assoc()['count'];
        
        if ($productCount > 0) {
            sendResponse(400, false, 'Cannot delete category with existing products');
        }
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, true, 'Category deleted successfully');
            } else {
                sendResponse(404, false, 'Category not found');
            }
        } else {
            sendResponse(500, false, 'Failed to delete category');
        }
        break;

    default:
        sendResponse(405, false, 'Method not allowed');
        break;
}