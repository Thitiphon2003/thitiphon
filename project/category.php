<?php
session_start();
require_once 'db_connect.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$page_title = 'สินค้าทั้งหมด';
$where = "WHERE status = 'active'";
$params = [];

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $page_title = "ค้นหา: $search";
} elseif ($category_id > 0) {
    $category = fetchOne("SELECT * FROM categories WHERE id = ? AND status = 'active'", [$category_id]);
    if ($category) {
        $where .= " AND category_id = ?";
        $params[] = $category_id;
        $page_title = $category['name'];
    }
}

$products = fetchAll("SELECT * FROM products $where ORDER BY created_at DESC", $params);
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">หน้าแรก</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($page_title); ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-th-large me-2"></i>หมวดหมู่ทั้งหมด</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="category.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $category_id == 0 && empty($search) ? 'active' : ''; ?>">
                        สินค้าทั้งหมด
                        <span class="badge bg-secondary rounded-pill"><?php echo fetchOne("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'] ?? 0; ?></span>
                    </a>
                    <?php foreach ($categories as $cat): 
                        $count = fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 'active'", [$cat['id']])['count'] ?? 0;
                    ?>
                        <a href="category.php?id=<?php echo $cat['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                            <i class="<?php echo $cat['icon'] ?? 'fas fa-tag'; ?> me-2"></i><?php echo htmlspecialchars($cat['name']); ?>
                            <span class="badge bg-secondary rounded-pill"><?php echo $count; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
                <span class="text-muted">พบ <?php echo count($products); ?> รายการ</span>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">เรียงตาม</label>
                            <select class="form-select" onchange="sortProducts(this.value)">
                                <option value="newest">มาใหม่ล่าสุด</option>
                                <option value="price-low">ราคาต่ำไปสูง</option>
                                <option value="price-high">ราคาสูงไปต่ำ</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">แสดง</label>
                            <select class="form-select" onchange="showPerPage(this.value)">
                                <option value="12">12 ชิ้น</option>
                                <option value="24">24 ชิ้น</option>
                                <option value="36">36 ชิ้น</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ค้นหา</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" placeholder="ค้นหาสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary" type="button" onclick="searchProducts()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h3>ไม่พบสินค้า</h3>
                    <p class="text-muted">กรุณาลองค้นหาหรือเลือกหมวดหมู่อื่น</p>
                    <a href="category.php" class="btn btn-primary">ดูสินค้าทั้งหมด</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): 
                        $cat_name = '';
                        if ($product['category_id']) {
                            $cat = fetchOne("SELECT name FROM categories WHERE id = ?", [$product['category_id']]);
                            $cat_name = $cat ? $cat['name'] : '';
                        }
                    ?>
                        <div class="col-md-4 col-6">
                            <div class="card h-100">
                                <div class="position-relative">
                                    <div style="height: 200px; overflow: hidden;">
                                        <?php 
                                        $image_path = "uploads/products/" . $product['id'] . ".jpg";
                                        if (file_exists($image_path)): ?>
                                            <img src="<?php echo $image_path . '?t=' . time(); ?>" class="card-img-top" alt="<?php echo $product['name']; ?>" style="height: 200px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/200x200?text=No+Image" class="card-img-top" alt="No Image">
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                        <span class="position-absolute top-0 start-0 badge bg-danger m-2">
                                            -<?php echo round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <?php if ($cat_name): ?>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($cat_name); ?></p>
                                    <?php endif; ?>
                                    <div class="mb-2">
                                        <span class="fw-bold text-primary">฿<?php echo number_format($product['price']); ?></span>
                                        <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                            <small class="text-muted text-decoration-line-through ms-2">฿<?php echo number_format($product['original_price']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <p class="small text-success mb-2">
                                        <i class="fas fa-box me-1"></i>คงเหลือ <?php echo $product['stock']; ?> ชิ้น
                                    </p>
                                    <button class="btn btn-primary w-100" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus me-2"></i>หยิบใส่ตะกร้า
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1"><i class="fas fa-chevron-left"></i></a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">4</a></li>
                        <li class="page-item"><a class="page-link" href="#">5</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>