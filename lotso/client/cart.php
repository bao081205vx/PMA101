<?php
require_once 'header.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý thêm sản phẩm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Lấy thông tin sản phẩm
    $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        // Kiểm tra số lượng tồn kho
        if ($product['stock'] >= $quantity) {
            // Nếu sản phẩm đã có trong giỏ hàng thì cộng thêm số lượng
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                ];
            }
            
            // Kiểm tra lại số lượng không vượt quá tồn kho
            if ($_SESSION['cart'][$product_id]['quantity'] > $product['stock']) {
                $_SESSION['cart'][$product_id]['quantity'] = $product['stock'];
            }
            
            $success = 'Đã thêm sản phẩm vào giỏ hàng';
        } else {
            $error = 'Số lượng sản phẩm trong kho không đủ';
        }
    }
}

// Xử lý cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    try {
        if (!isset($_POST['quantity']) || !is_array($_POST['quantity'])) {
            throw new Exception('Dữ liệu không hợp lệ');
        }

    foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;

            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }

            $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Lỗi chuẩn bị câu lệnh SQL');
            }

            $stmt->bind_param("i", $product_id);
            if (!$stmt->execute()) {
                throw new Exception('Lỗi thực thi câu lệnh SQL');
            }

            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if (!$product) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }

            if ($quantity > $product['quantity']) {
                throw new Exception("Số lượng sản phẩm '{$product['name']}' vượt quá số lượng trong kho");
            }

            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }

        $response = [
            'success' => true,
            'message' => 'Đã cập nhật giỏ hàng thành công'
        ];
    } catch (Exception $e) {
        error_log('Cart update error: ' . $e->getMessage());
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Xử lý xóa sản phẩm qua AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $product_id = (int)$_POST['remove'];
    unset($_SESSION['cart'][$product_id]);
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
    ]);
    exit;
}

// Tính tổng tiền
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<div class="container py-5">
    <h1 class="mb-4">Giỏ hàng</h1>

    <div id="messageContainer"></div>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-info">
            Giỏ hàng trống. <a href="products.php">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <form id="cartForm" method="POST">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Giá</th>
                                    <th style="width: 150px;">Số lượng</th>
                                    <th class="text-end">Thành tiền</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                <tr id="cart-item-<?= $product_id ?>">
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= number_format($item['price']) ?> đ</td>
                                    <td>
                                        <input type="number" name="quantity[<?= $product_id ?>]" 
                                               value="<?= $item['quantity'] ?>" 
                                               min="1" class="form-control">
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($item['price'] * $item['quantity']) ?> đ
                                    </td>
                                    <td class="text-end">
                                        <button type="button" 
                                                class="btn btn-sm btn-danger remove-item"
                                                onclick="removeFromCart(<?= $product_id ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?= number_format($total) ?> đ</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Cập nhật giỏ hàng
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        Thanh toán <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function showMessage(message, isSuccess = true) {
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.innerHTML = `
        <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

function removeFromCart(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const item = document.getElementById(`cart-item-${productId}`);
            if (item) {
                item.remove();
            }
            showMessage(data.message);
            
            // Kiểm tra nếu giỏ hàng trống
            const tbody = document.querySelector('tbody');
            if (!tbody || tbody.children.length === 0) {
                window.location.reload();
            }
        } else {
            showMessage(data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi xóa sản phẩm', false);
    });
}

document.getElementById('cartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('update_cart.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi cập nhật giỏ hàng', false);
    });
});
</script>

<?php require_once 'footer.php'; ?>

                                                class="btn btn-sm btn-danger remove-item"
                                                onclick="removeFromCart(<?= $product_id ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?= number_format($total) ?> đ</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Cập nhật giỏ hàng
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        Thanh toán <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function showMessage(message, isSuccess = true) {
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.innerHTML = `
        <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

function removeFromCart(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const item = document.getElementById(`cart-item-${productId}`);
            if (item) {
                item.remove();
            }
            showMessage(data.message);
            
            // Kiểm tra nếu giỏ hàng trống
            const tbody = document.querySelector('tbody');
            if (!tbody || tbody.children.length === 0) {
                window.location.reload();
            }
        } else {
            showMessage(data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi xóa sản phẩm', false);
    });
}

document.getElementById('cartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('update_cart.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi cập nhật giỏ hàng', false);
    });
});
</script>

<?php require_once 'footer.php'; ?>

                                                class="btn btn-sm btn-danger remove-item"
                                                onclick="removeFromCart(<?= $product_id ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?= number_format($total) ?> đ</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Cập nhật giỏ hàng
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        Thanh toán <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function showMessage(message, isSuccess = true) {
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.innerHTML = `
        <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

function removeFromCart(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const item = document.getElementById(`cart-item-${productId}`);
            if (item) {
                item.remove();
            }
            showMessage(data.message);
            
            // Kiểm tra nếu giỏ hàng trống
            const tbody = document.querySelector('tbody');
            if (!tbody || tbody.children.length === 0) {
                window.location.reload();
            }
        } else {
            showMessage(data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi xóa sản phẩm', false);
    });
}

document.getElementById('cartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('update_cart.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi cập nhật giỏ hàng', false);
    });
});
</script>

<?php require_once 'footer.php'; ?>

                                                class="btn btn-sm btn-danger remove-item"
                                                onclick="removeFromCart(<?= $product_id ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?= number_format($total) ?> đ</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Cập nhật giỏ hàng
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        Thanh toán <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function showMessage(message, isSuccess = true) {
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.innerHTML = `
        <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

function removeFromCart(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const item = document.getElementById(`cart-item-${productId}`);
            if (item) {
                item.remove();
            }
            showMessage(data.message);
            
            // Kiểm tra nếu giỏ hàng trống
            const tbody = document.querySelector('tbody');
            if (!tbody || tbody.children.length === 0) {
                window.location.reload();
            }
        } else {
            showMessage(data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi xóa sản phẩm', false);
    });
}

document.getElementById('cartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('update_cart.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi cập nhật giỏ hàng', false);
    });
});
</script>

<?php require_once 'footer.php'; ?>

                                                class="btn btn-sm btn-danger remove-item"
                                                onclick="removeFromCart(<?= $product_id ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?= number_format($total) ?> đ</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Cập nhật giỏ hàng
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        Thanh toán <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function showMessage(message, isSuccess = true) {
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.innerHTML = `
        <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

function removeFromCart(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const item = document.getElementById(`cart-item-${productId}`);
            if (item) {
                item.remove();
            }
            showMessage(data.message);
            
            // Kiểm tra nếu giỏ hàng trống
            const tbody = document.querySelector('tbody');
            if (!tbody || tbody.children.length === 0) {
                window.location.reload();
            }
        } else {
            showMessage(data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi xóa sản phẩm', false);
    });
}

document.getElementById('cartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('update_cart.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi cập nhật giỏ hàng', false);
    });
});
</script>

<?php require_once 'footer.php'; ?>

                                                class="btn btn-sm btn-danger remove-item"
                                                onclick="removeFromCart(<?= $product_id ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                    <td class="text-end"><strong><?= number_format($total) ?> đ</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Cập nhật giỏ hàng
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        Thanh toán <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function showMessage(message, isSuccess = true) {
    const messageContainer = document.getElementById('messageContainer');
    messageContainer.innerHTML = `
        <div class="alert alert-${isSuccess ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

function removeFromCart(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const item = document.getElementById(`cart-item-${productId}`);
            if (item) {
                item.remove();
            }
            showMessage(data.message);
            
            // Kiểm tra nếu giỏ hàng trống
            const tbody = document.querySelector('tbody');
            if (!tbody || tbody.children.length === 0) {
                window.location.reload();
            }
        } else {
            showMessage(data.message, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi xóa sản phẩm', false);
    });
}

document.getElementById('cartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('update_cart.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showMessage(data.message, data.success);
        if (data.success) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Có lỗi xảy ra khi cập nhật giỏ hàng', false);
    });
});
</script>

<?php require_once 'footer.php'; ?>
