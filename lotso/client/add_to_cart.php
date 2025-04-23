<?php
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

// Kiểm tra dữ liệu POST
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];
$buy_now = isset($_POST['buy_now']) ? true : false;

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra sản phẩm tồn tại
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL");
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi câu lệnh SQL");
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // Kiểm tra số lượng tồn kho
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']);
        exit;
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Thêm hoặc cập nhật sản phẩm trong giỏ hàng
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $new_quantity = $item['quantity'] + $quantity;
            if ($new_quantity > $product['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Tổng số lượng vượt quá số lượng trong kho']);
                exit;
            }
            $item['quantity'] = $new_quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price']
        ];
    }

    // Tính tổng số lượng sản phẩm trong giỏ hàng
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Nếu là mua ngay thì chuyển đến trang thanh toán
    if ($buy_now) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count,
            'redirect' => 'checkout.php'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count
        ]);
    }

} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng: ' . $e->getMessage()]);
    exit;
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

// Kiểm tra dữ liệu POST
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];
$buy_now = isset($_POST['buy_now']) ? true : false;

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra sản phẩm tồn tại
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL");
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi câu lệnh SQL");
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // Kiểm tra số lượng tồn kho
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']);
        exit;
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Thêm hoặc cập nhật sản phẩm trong giỏ hàng
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $new_quantity = $item['quantity'] + $quantity;
            if ($new_quantity > $product['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Tổng số lượng vượt quá số lượng trong kho']);
                exit;
            }
            $item['quantity'] = $new_quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price']
        ];
    }

    // Tính tổng số lượng sản phẩm trong giỏ hàng
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Nếu là mua ngay thì chuyển đến trang thanh toán
    if ($buy_now) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count,
            'redirect' => 'checkout.php'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count
        ]);
    }

} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng: ' . $e->getMessage()]);
    exit;
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

// Kiểm tra dữ liệu POST
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];
$buy_now = isset($_POST['buy_now']) ? true : false;

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra sản phẩm tồn tại
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL");
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi câu lệnh SQL");
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // Kiểm tra số lượng tồn kho
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']);
        exit;
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Thêm hoặc cập nhật sản phẩm trong giỏ hàng
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $new_quantity = $item['quantity'] + $quantity;
            if ($new_quantity > $product['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Tổng số lượng vượt quá số lượng trong kho']);
                exit;
            }
            $item['quantity'] = $new_quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price']
        ];
    }

    // Tính tổng số lượng sản phẩm trong giỏ hàng
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Nếu là mua ngay thì chuyển đến trang thanh toán
    if ($buy_now) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count,
            'redirect' => 'checkout.php'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count
        ]);
    }

} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng: ' . $e->getMessage()]);
    exit;
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

// Kiểm tra dữ liệu POST
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];
$buy_now = isset($_POST['buy_now']) ? true : false;

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra sản phẩm tồn tại
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL");
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi câu lệnh SQL");
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // Kiểm tra số lượng tồn kho
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']);
        exit;
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Thêm hoặc cập nhật sản phẩm trong giỏ hàng
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $new_quantity = $item['quantity'] + $quantity;
            if ($new_quantity > $product['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Tổng số lượng vượt quá số lượng trong kho']);
                exit;
            }
            $item['quantity'] = $new_quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price']
        ];
    }

    // Tính tổng số lượng sản phẩm trong giỏ hàng
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Nếu là mua ngay thì chuyển đến trang thanh toán
    if ($buy_now) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count,
            'redirect' => 'checkout.php'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count
        ]);
    }

} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng: ' . $e->getMessage()]);
    exit;
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

// Kiểm tra dữ liệu POST
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];
$buy_now = isset($_POST['buy_now']) ? true : false;

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra sản phẩm tồn tại
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL");
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi câu lệnh SQL");
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // Kiểm tra số lượng tồn kho
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']);
        exit;
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Thêm hoặc cập nhật sản phẩm trong giỏ hàng
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $new_quantity = $item['quantity'] + $quantity;
            if ($new_quantity > $product['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Tổng số lượng vượt quá số lượng trong kho']);
                exit;
            }
            $item['quantity'] = $new_quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price']
        ];
    }

    // Tính tổng số lượng sản phẩm trong giỏ hàng
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Nếu là mua ngay thì chuyển đến trang thanh toán
    if ($buy_now) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count,
            'redirect' => 'checkout.php'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count
        ]);
    }

} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng: ' . $e->getMessage()]);
    exit;
} 
session_start();
require_once '../config/Database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

// Kiểm tra dữ liệu POST
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];
$buy_now = isset($_POST['buy_now']) ? true : false;

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Kiểm tra sản phẩm tồn tại
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL");
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi câu lệnh SQL");
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    // Kiểm tra số lượng tồn kho
    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ']);
        exit;
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Thêm hoặc cập nhật sản phẩm trong giỏ hàng
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $new_quantity = $item['quantity'] + $quantity;
            if ($new_quantity > $product['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Tổng số lượng vượt quá số lượng trong kho']);
                exit;
            }
            $item['quantity'] = $new_quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price']
        ];
    }

    // Tính tổng số lượng sản phẩm trong giỏ hàng
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Nếu là mua ngay thì chuyển đến trang thanh toán
    if ($buy_now) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count,
            'redirect' => 'checkout.php'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
            'cart_count' => $cart_count
        ]);
    }

} catch (Exception $e) {
    error_log('Add to cart error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng: ' . $e->getMessage()]);
    exit;
} 