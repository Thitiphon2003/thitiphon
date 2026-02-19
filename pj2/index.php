// index.php
<?php
session_start();

// ข้อมูลจำลอง (ในระบบจริงควรใช้ฐานข้อมูล)
$products = [
    1 => ['id' => 1, 'name' => 'เสื้อเชิ้ตผู้ชาย', 'price' => 499, 'category' => 'เสื้อผ้า', 'image' => 'https://via.placeholder.com/300x300', 'description' => 'เสื้อเชิ้ตผ้าคอตตอน 100% เนื้อนุ่ม สวมใส่สบาย'],
    2 => ['id' => 2, 'name' => 'กางเกงยีนส์', 'price' => 899, 'category' => 'เสื้อผ้า', 'image' => 'https://via.placeholder.com/300x300', 'description' => 'กางเกงยีนส์ทรงสวมใส่สบาย เนื้อผ้าคุณภาพดี'],
    3 => ['id' => 3, 'name' => 'รองเท้าผ้าใบ', 'price' => 1290, 'category' => 'รองเท้า', 'image' => 'https://via.placeholder.com/300x300', 'description' => 'รองเท้าผ้าใบแฟชั่น สวมใส่สบาย เหมาะสำหรับทุกวัน'],
    4 => ['id' => 4, 'name' => 'กระเป๋าเป้', 'price' => 790, 'category' => 'กระเป๋า', 'image' => 'https://via.placeholder.com/300x300', 'description' => 'กระเป๋าเป้กันน้ำ มีช่องใส่ของหลายช่อง'],
    5 => ['id' => 5, 'name' => 'นาฬิกาข้อมือ', 'price' => 1990, 'category' => 'เครื่องประดับ', 'image' => 'https://via.placeholder.com/300x300', 'description' => 'นาฬิกาข้อมือดีไซน์ทันสมัย กันน้ำได้'],
    6 => ['id' => 6, 'name' => 'หมวกแคป', 'price' => 290, 'category' => 'เครื่องประดับ', 'image' => 'https://via.placeholder.com/300x300', 'description' => 'หมวกแคปปรับขนาดได้ สวมใส่สบาย'],
];

$categories = ['ทั้งหมด', 'เสื้อผ้า', 'รองเท้า', 'กระเป๋า', 'เครื่องประดับ'];

// ดึงหมวดหมู่จาก URL
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'ทั้งหมด';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// กรองสินค้าตามหมวดหมู่และคำค้นหา
$filtered_products = $products;
if ($selected_category !== 'ทั้งหมด') {
    $filtered_products = array_filter($products, function($product) use ($selected_category) {
        return $product['category'] === $selected_category;
    });
}

if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
        return stripos($product['name'], $search_query) !== false || 
               stripos($product['description'], $search_query) !== false;
    });
}

// จัดการตะกร้าสินค้า
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $_SESSION['cart'][$product_id] = [
            'id' => $product_id,
            'name' => $products[$product_id]['name'],
            'price' => $products[$product_id]['price'],
            'quantity' => 1,
            'image' => $products[$product_id]['image']
        ];
    }
    
    header('Location: index.php?added=' . $product_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้าออนไลน์</title>
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
                        <a class="nav-link active" href="index.php">หน้าหลัก</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="cart.php" class="btn btn-light position-relative me-2">
                        <i class="bi bi-cart"></i> ตะกร้า
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= count($_SESSION['cart']) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php if (isset($_SESSION['user'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?= $_SESSION['user']['name'] ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">โปรไฟล์</a></li>
                                <li><a class="dropdown-item" href="orders.php">ประวัติการสั่งซื้อ</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">ออกจากระบบ</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-light me-2"><i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ</a>
                        <a href="register.php" class="btn btn-warning"><i class="bi bi-person-plus"></i> สมัครสมาชิก</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Search and Categories -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="ค้นหาสินค้า..." value="<?= htmlspecialchars($search_query) ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> ค้นหา</button>
                </form>
            </div>
            <div class="col-md-6">
                <div class="btn-group" role="group">
                    <?php foreach ($categories as $category): ?>
                        <a href="?category=<?= urlencode($category) ?>" class="btn btn-outline-primary <?= $selected_category == $category ? 'active' : '' ?>">
                            <?= $category ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if (count($filtered_products) > 0): ?>
                <?php foreach ($filtered_products as $product): ?>
                    <div class="col">
                        <div class="card h-100 product-card">
                            <img src="<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= $product['name'] ?></h5>
                                <p class="card-text text-muted"><?= $product['description'] ?></p>
                                <p class="card-text">
                                    <span class="badge bg-secondary"><?= $product['category'] ?></span>
                                </p>
                                <h4 class="text-primary mb-3">฿<?= number_format($product['price'], 2) ?></h4>
                                <div class="d-flex justify-content-between">
                                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn btn-outline-info">
                                        <i class="bi bi-eye"></i> รายละเอียด
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-success">
                                            <i class="bi bi-cart-plus"></i> หยิบใส่ตะกร้า
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        ไม่พบสินค้าที่ค้นหา
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container text-center">
            <p>&copy; 2024 ร้านค้าออนไลน์. สงวนลิขสิทธิ์.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>