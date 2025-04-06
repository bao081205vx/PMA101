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
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM comments";
        $countResult = $conn->query($countQuery);
        $total = $countResult->fetch_assoc()['total'];

        // Set up pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Get comments with pagination
        $query = "SELECT c.*, u.username, u.email, p.title as post_title 
                 FROM comments c 
                 JOIN users u ON c.user_id = u.id 
                 JOIN posts p ON c.post_id = p.id
                 ORDER BY c.created_at DESC 
                 LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = $result->fetch_all(MYSQLI_ASSOC);

        // Calculate pagination info
        $total_pages = ceil($total / $limit);
        
        $pagination = [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $total_pages
        ];

        sendResponse(200, true, 'Comments retrieved successfully', [
            'data' => $comments,
            'pagination' => $pagination
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['post_id']) || !isset($data['user_id']) || !isset($data['content'])) {
            sendResponse(400, false, 'Missing required fields');
        }

        // Validate post_id and user_id
        $post_id = filter_var($data['post_id'], FILTER_VALIDATE_INT);
        $user_id = filter_var($data['user_id'], FILTER_VALIDATE_INT);
        
        if (!$post_id || !$user_id) {
            sendResponse(400, false, 'Invalid post_id or user_id');
        }

        // Insert comment
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iis", $post_id, $user_id, $data['content']);
        
        if ($stmt->execute()) {
            $comment_id = $conn->insert_id;
            
            // Get the newly created comment
            $stmt = $conn->prepare("SELECT c.*, u.username, u.email, p.title as post_title 
                                  FROM comments c 
                                  JOIN users u ON c.user_id = u.id 
                                  JOIN posts p ON c.post_id = p.id 
                                  WHERE c.id = ?");
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $comment = $result->fetch_assoc();
            
            sendResponse(201, true, 'Comment created successfully', $comment);
        } else {
            sendResponse(500, false, 'Failed to create comment');
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Comment ID is required');
        }

        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'Invalid comment ID');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['status'])) {
            sendResponse(400, false, 'Status is required');
        }

        // Validate status
        $validStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($data['status'], $validStatuses)) {
            sendResponse(400, false, 'Invalid status value');
        }

        $stmt = $conn->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $data['status'], $id);
        
        if ($stmt->execute()) {
            // Get the updated comment
            $stmt = $conn->prepare("SELECT c.*, u.username, u.email, p.title as post_title 
                                  FROM comments c 
                                  JOIN users u ON c.user_id = u.id 
                                  JOIN posts p ON c.post_id = p.id 
                                  WHERE c.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $comment = $result->fetch_assoc();
            
            sendResponse(200, true, 'Comment updated successfully', $comment);
        } else {
            sendResponse(500, false, 'Failed to update comment');
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            sendResponse(400, false, 'Comment ID is required');
        }

        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            sendResponse(400, false, 'Invalid comment ID');
        }

        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            sendResponse(200, true, 'Comment deleted successfully');
        } else {
            sendResponse(500, false, 'Failed to delete comment');
        }
        break;

    default:
        sendResponse(405, false, 'Method not allowed');
        break;
}
