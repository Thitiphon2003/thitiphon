// admin.php
<?php
session_start();

// ตรวจสอบการเข้าสู่ระบบของแอดมิน
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// ข้อมูลจำลอง
$products = [
    1 => ['id' => 1, 'name' => 'เสื้อเชิ้ตผู้ชาย', 'price' => 499, 'category' => 'เสื้อผ้า', 'stock' => 50, 'image' => 'https://via.placeholder.com/100x100'],
    2 => ['id' => 2, 'name' => 'กางเกงยีนส์', 'price' => 899, 'category' => 'เสื้อผ้า', 'stock' => 30, 'image' => 'https://via.placeholder.com/100x100'],
    3 => ['id' => 3, 'name' => 'รองเท้าผ้าใบ', 'price' => 1290, 'category' => 'รองเท้า', 'stock' => 20, 'image' => 'https://via.placeholder.com/100x100'],
];

$categories = [
    1 => ['id' => 1, 'name' => 'เสื้อผ้า', 'count' => 10],
    2 => ['id' => 2, 'name' => 'รองเท้า', 'count' => 5],
    3 => ['id' => 3, 'name' => 'กระเป๋า', 'count' => 8],
];

$customers = [
    1 => ['id' => 1, 'name' => 'สมชาย ใจดี', 'email' => 'somchai@email.com', 'phone' => '081-234-5678', 'registered' => '2024-01-15'],
    2 => ['id' => 2, 'name' => 'สมหญิง รักดี', 'email' => 'somying@email.com', 'phone' => '082-345-6789', 'registered' => '2024-02-20'],
];

$orders = [
    1 => ['id' => 'ORD001', 'customer' => 'สมชาย ใจดี', 'date' => '2024-03-01', 'total' => 1897, 'status' => 'จัดส่งแล้ว', 'address' => '123 ถนนสุขุมวิท กรุงเทพฯ'],
    2 => ['id' => 'ORD002', 'customer' => 'สมหญิง รักดี', 'date' => '2024-03-02', 'total' => 1290, 'status' => 'รอดำเนินการ', 'address' => '45 ถนนพหลโยธิน กรุงเทพฯ'],
];

// จัดการ actions
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'add_product') {
        // เพิ่มสินค้าใหม่
        $new_id = count($products) + 1;
        $products[$new_id] = [
            'id' => $new_id,
            'name' => $_POST['name'],
            'price' => $_POST['price'],
            'category' => $_POST['category'],
            'stock' => $_POST['stock'],
            'image' => 'https://via.placeholder.com/100x100'
        ];
        $message = "เพิ่มสินค้าเรียบร้อยแล้ว";
    } elseif ($_POST['action'] == 'delete_product') {
        // ลบสินค้า
        $id = $_POST['id'];
        unset($products[$id]);
        $message = "ลบสินค้าเรียบร้อยแล้ว";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการหลังบ้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="//cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">
                <i class="bi bi-gear"></i> ระบบจัดการหลังบ้าน
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#products" data-bs-toggle="tab">สินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#categories" data-bs-toggle="tab">ประเภทสินค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#customers" data-bs-toggle="tab">ลูกค้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#orders" data-bs-toggle="tab">ออเดอร์</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i> แอดมิน
                    </span>
                    <a href="admin_logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tab panes -->
        <div class="tab-content">
            <!-- จัดการสินค้า -->
            <div class="tab-pane active" id="products">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-box"></i> จัดการสินค้า</h5>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="bi bi-plus-circle"></i> เพิ่มสินค้า
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" id="searchProduct" class="form-control" placeholder="ค้นหาสินค้า...">
                            </div>
                        </div>
                        <table id="productsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>รูป</th>
                                    <th>ชื่อสินค้า</th>
                                    <th>ราคา</th>
                                    <th>หมวดหมู่</th>
                                    <th>สต็อก</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><img src="<?= $product['image'] ?>" width="50" height="50" class="rounded"></td>
                                    <td><?= $product['name'] ?></td>
                                    <td>฿<?= number_format($product['price'], 2) ?></td>
                                    <td><?= $product['category'] ?></td>
                                    <td>
                                        <span class="badge <?= $product['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $product['stock'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick="editProduct(<?= $product['id'] ?>)">
                                            <i class="bi bi-pencil"></i> แก้ไข
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('ต้องการลบสินค้านี้?')">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-trash"></i> ลบ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- จัดการประเภทสินค้า -->
            <div class="tab-pane" id="categories">
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-tags"></i> จัดการประเภทสินค้า</h5>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-circle"></i> เพิ่มประเภท
                        </button>
                    </div>
                    <div class="card-body">
                        <table id="categoriesTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อประเภท</th>
                                    <th>จำนวนสินค้า</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?= $category['id'] ?></td>
                                    <td><?= $category['name'] ?></td>
                                    <td><?= $category['count'] ?> รายการ</td>
                                    <td>
                                        <button class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> แก้ไข</button>
                                        <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> ลบ</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- จัดการลูกค้า -->
            <div class="tab-pane" id="customers">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-people"></i> จัดการลูกค้า</h5>
                    </div>
                    <div class="card-body">
                        <table id="customersTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>อีเมล</th>
                                    <th>เบอร์โทร</th>
                                    <th>วันที่สมัคร</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?= $customer['id'] ?></td>
                                    <td><?= $customer['name'] ?></td>
                                    <td><?= $customer['email'] ?></td>
                                    <td><?= $customer['phone'] ?></td>
                                    <td><?= $customer['registered'] ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> แก้ไข</button>
                                        <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> ลบ</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- จัดการออเดอร์ -->
            <div class="tab-pane" id="orders">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-truck"></i> จัดการออเดอร์</h5>
                    </div>
                    <div class="card-body">
                        <table id="ordersTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>รหัสออเดอร์</th>
                                    <th>ลูกค้า</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ยอดรวม</th>
                                    <th>สถานะ</th>
                                    <th>ที่อยู่จัดส่ง</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= $order['customer'] ?></td>
                                    <td><?= $order['date'] ?></td>
                                    <td>฿<?= number_format($order['total'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $order['status'] == 'จัดส่งแล้ว' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= $order['address'] ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm"><i class="bi bi-eye"></i> ดูรายละเอียด</button>
                                        <button class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> อัปเดตสถานะ</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มสินค้า -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มสินค้าใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_product">
                        <div class="mb-3">
                            <label class="form-label">ชื่อสินค้า</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ราคา</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">หมวดหมู่</label>
                            <select name="category" class="form-control" required>
                                <option value="เสื้อผ้า">เสื้อผ้า</option>
                                <option value="รองเท้า">รองเท้า</option>
                                <option value="กระเป๋า">กระเป๋า</option>
                                <option value="เครื่องประดับ">เครื่องประดับ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">จำนวนสต็อก</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รูปสินค้า (URL)</label>
                            <input type="url" name="image" class="form-control" value="https://via.placeholder.com/100x100">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่มประเภทสินค้า -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มประเภทสินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อประเภทสินค้า</label>
                            <input type="text" name="category_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="script.js"></script>
</body>
</html>