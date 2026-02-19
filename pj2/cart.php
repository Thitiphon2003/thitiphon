// cart.php
<?php
session_start();

// ข้อมูลจำลอง
$products = [
    1 => ['id' => 1, 'name' => 'เสื้อเชิ้ตผู้ชาย', 'price' => 499, 'stock' => 50],
    2 => ['id' => 2, 'name' => 'กางเกงยีนส์', 'price' => 899, 'stock' => 30],
    3 => ['id' => 3, 'name' => 'รองเท้าผ้าใบ', 'price' => 1290, 'stock' => 20],
];

// จัดการอัปเดตตะกร้า
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    header('Location: cart.php?updated=1');
    exit();
}

// ลบสินค้าออกจากตะกร้า
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
    header('Location: cart.php?removed=1');
    exit();
}

// คำนวณยอดรวม
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 0 ? 50 : 0;
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-shop"></i> ร้านค้าออนไลน์</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">หน้าหลัก</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="cart.php" class="btn btn-light position-relative me-2">
                        <i class="bi bi-cart"></i> ตะกร้า
                        <?php if (count($cart_items) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= count($cart_items) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-cart"></i> ตะกร้าสินค้า</h2>

        <!-- Messages -->
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">อัปเดตตะกร้าสินค้าเรียบร้อย</div>
        <?php endif; ?>
        <?php if (isset($_GET['removed'])): ?>
            <div class="alert alert-warning">ลบสินค้าออกจากตะกร้าเรียบร้อย</div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-3">ตะกร้าสินค้าว่างเปล่า</h3>
                <p class="text-muted">เลือกซื้อสินค้าได้ที่หน้าหลัก</p>
                <a href="index.php" class="btn btn-primary">เลือกซื้อสินค้า</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>สินค้า</th>
                                            <th>ราคา</th>
                                            <th>จำนวน</th>
                                            <th>รวม</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $id => $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="https://via.placeholder.com/50x50" class="me-2 rounded">
                                                    <span><?= $item['name'] ?></span>
                                                </div>
                                            </td>
                                            <td>฿<?= number_format($item['price'], 2) ?></td>
                                            <td style="width: 150px;">
                                                <input type="number" name="quantity[<?= $id ?>]" 
                                                       class="form-control" value="<?= $item['quantity'] ?>" 
                                                       min="0" max="<?= $products[$id]['stock'] ?>">
                                            </td>
                                            <td>฿<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                            <td>
                                                <a href="?remove=<?= $id ?>" class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('ต้องการลบสินค้านี้ออกจากตะกร้า?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button type="submit" name="update_cart" class="btn btn-warning">
                                    <i class="bi bi-arrow-repeat"></i> อัปเดตตะกร้า
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">สรุปคำสั่งซื้อ</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td>ยอดรวมสินค้า:</td>
                                    <td class="text-end">฿<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <td>ค่าจัดส่ง:</td>
                                    <td class="text-end">฿<?= number_format($shipping, 2) ?></td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>ยอดรวมทั้งสิ้น:</td>
                                    <td class="text-end text-primary">฿<?= number_format($total, 2) ?></td>
                                </tr>
                            </table>
                            <hr>
                            <?php if (isset($_SESSION['user'])): ?>
                                <button class="btn btn-success btn-lg w-100" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                                    <i class="bi bi-credit-card"></i> ดำเนินการสั่งซื้อ
                                </button>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    กรุณา <a href="login.php">เข้าสู่ระบบ</a> เพื่อดำเนินการสั่งซื้อ
                                </div>
                                <a href="login.php" class="btn btn-primary w-100">เข้าสู่ระบบ</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ยืนยันคำสั่งซื้อ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>รายการสินค้า</h6>
                    <table class="table table-sm">
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?= $item['name'] ?> x <?= $item['quantity'] ?></td>
                            <td class="text-end">฿<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold">
                            <td>รวมทั้งสิ้น</td>
                            <td class="text-end text-primary">฿<?= number_format($total, 2) ?></td>
                        </tr>
                    </table>

                    <h6 class="mt-3">วิธีการชำระเงิน</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment" id="creditCard" checked>
                        <label class="form-check-label" for="creditCard">
                            บัตรเครดิต / เดบิต
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment" id="bankTransfer">
                        <label class="form-check-label" for="bankTransfer">
                            โอนเงินผ่านธนาคาร
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment" id="cod">
                        <label class="form-check-label" for="cod">
                            เก็บเงินปลายทาง
                        </label>
                    </div>

                    <h6 class="mt-3">ที่อยู่จัดส่ง</h6>
                    <textarea class="form-control" rows="3">123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success" onclick="processOrder()">
                        <i class="bi bi-check-circle"></i> ยืนยันคำสั่งซื้อ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processOrder() {
            alert('คำสั่งซื้อสำเร็จ! ขอบคุณที่ใช้บริการ');
            window.location.href = 'index.php?order_success=1';
        }
    </script>
</body>
</html>