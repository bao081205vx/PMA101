<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/response.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    sendResponse(405, false, 'Phương thức không được hỗ trợ');
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$period = isset($_GET['period']) ? $_GET['period'] : '';

switch ($type) {
    case 'products':
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        sendResponse(200, true, 'Lấy số lượng sản phẩm thành công', $result['count']);
        break;

    case 'orders':
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled
        FROM orders");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        sendResponse(200, true, 'Lấy thống kê đơn hàng thành công', $result);
        break;

    case 'users':
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        sendResponse(200, true, 'Lấy số lượng khách hàng thành công', $result['count']);
        break;

    case 'posts':
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE status = 'published'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        sendResponse(200, true, 'Lấy số lượng bài viết thành công', $result['count']);
        break;

    case 'recent_orders':
        $sql = "SELECT o.*, u.username, u.email 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               ORDER BY o.created_at DESC 
               LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        sendResponse(200, true, 'Lấy 5 đơn hàng gần nhất thành công', $result);
        break;

    case 'revenue_chart':
        $labels = [];
        $values = [];

        switch ($period) {
            case 'week':
                // Doanh thu 7 ngày gần nhất
                $sql = "SELECT 
                    DATE(created_at) as date,
                    COALESCE(SUM(total_price), 0) as revenue
                FROM orders 
                WHERE status = 'completed'
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";

                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Tạo mảng cho 7 ngày
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $revenue = 0;

                    // Tìm doanh thu cho ngày
                    foreach ($data as $row) {
                        if ($row['date'] === $date) {
                            $revenue = $row['revenue'];
                            break;
                        }
                    }

                    $labels[] = date('d/m', strtotime($date));
                    $values[] = (float)$revenue;
                }
                break;

            case 'month':
                // Doanh thu 30 ngày gần nhất
                $sql = "SELECT 
                    DATE(created_at) as date,
                    COALESCE(SUM(total_price), 0) as revenue
                FROM orders 
                WHERE status = 'completed'
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";

                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Tạo mảng cho 30 ngày
                for ($i = 29; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $revenue = 0;

                    // Tìm doanh thu cho ngày
                    foreach ($data as $row) {
                        if ($row['date'] === $date) {
                            $revenue = $row['revenue'];
                            break;
                        }
                    }

                    $labels[] = date('d/m', strtotime($date));
                    $values[] = (float)$revenue;
                }
                break;

            case 'year':
                // Doanh thu 12 tháng gần nhất
                $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COALESCE(SUM(total_price), 0) as revenue
                FROM orders 
                WHERE status = 'completed'
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";

                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Tạo mảng cho 12 tháng
                for ($i = 11; $i >= 0; $i--) {
                    $date = date('Y-m', strtotime("-$i months"));
                    $revenue = 0;

                    // Tìm doanh thu cho tháng
                    foreach ($data as $row) {
                        if ($row['month'] === $date) {
                            $revenue = $row['revenue'];
                            break;
                        }
                    }

                    $labels[] = date('m/Y', strtotime($date));
                    $values[] = (float)$revenue;
                }
                break;
        }

        sendResponse(200, true, 'Lấy dữ liệu doanh thu thành công', ['labels' => $labels, 'values' => $values]);
        break;

    default:
        sendResponse(400, false, 'Loại thống kê không hợp lệ');
        break;
}
