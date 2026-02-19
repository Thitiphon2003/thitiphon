<?php
session_start();
require_once 'db_connect.php';
$page_title = 'หน้าแรก';
include 'includes/header.php';

// ดึงข้อมูลหมวดหมู่
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name LIMIT 8");
$products = fetchAll("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
?>

<div class="container">
    <!-- Hero Section -->
    <div class="row min-vh-75 align-items-center py-5">
        <div class="col-lg-6 fade-in">
            <h1 class="display-4 fw-bold mb-4">ช้อปปิ้งออนไลน์<br>ที่ง่ายและปลอดภัย</h1>
            <p class="lead text-muted mb-4">สินค้าคุณภาพจากร้านค้าชั้นนำ ราคาพิเศษสำหรับคุณ จัดส่งรวดเร็วทั่วประเทศ</p>
            <div class="d-flex gap-3">
                <a href="category.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-store me-2"></i>เริ่มช้อปปิ้ง
                </a>
                <a href="#how-it-works" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-play-circle me-2"></i>ดูวิธีการสั่งซื้อ
                </a>
            </div>
        </div>
        <div class="col-lg-6 fade-in">
            <img src="https://via.placeholder.com/600x400" alt="Shopping" class="img-fluid rounded-4 shadow">
        </div>
    </div>
    
    <!-- Categories Section -->
    <section class="py-5">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold">หมวดหมู่สินค้า</h2>
            <p class="text-muted">เลือกซื้อสินค้าจากหลากหลายหมวดหมู่ที่คุณชื่นชอบ</p>
        </div>
        
        <div class="row g-4">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): 
                    $count = fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$category['id']])['count'] ?? 0;
                ?>
                <div class="col-md-3 col-6">
                    <a href="category.php?id=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="card text-center p-4 h-100">
                            <div class="stat-icon mx-auto mb-3">
                                <i class="<?php echo $category['icon'] ?? 'fas fa-tag'; ?>"></i>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo $count; ?> สินค้า</p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="col-md-3 col-6">
                    <div class="card text-center p-4 h-100">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="fas fa-tag"></i>
                        </div>
                        <h5 class="card-title">หมวดหมู่ตัวอย่าง</h5>
                        <p class="text-muted mb-0">0 สินค้า</p>
                    </div>
                </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Products Section -->
    <section class="py-5 bg-light rounded-4">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold">สินค้ามาใหม่</h2>
                <p class="text-muted">พบกับสินค้าใหม่ล่าสุดที่มาแรงตอนนี้</p>
            </div>
            
            <div class="row g-4">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="card h-100">
                            <div class="position-relative">
                                <div class="product-thumb mx-auto mt-3" style="width: 100%; height: 200px;">
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
                                <div class="mb-2">
                                    <span class="fw-bold text-primary">฿<?php echo number_format($product['price']); ?></span>
                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                        <small class="text-muted text-decoration-line-through ms-2">฿<?php echo number_format($product['original_price']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-primary w-100" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>หยิบใส่ตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="card h-100">
                            <img src="https://via.placeholder.com/200x200" class="card-img-top" alt="Product">
                            <div class="card-body">
                                <h6 class="card-title">สินค้าตัวอย่าง <?php echo $i; ?></h6>
                                <div class="mb-2">
                                    <span class="fw-bold text-primary">฿299</span>
                                </div>
                                <button class="btn btn-primary w-100" onclick="addToCart(0)">
                                    <i class="fas fa-cart-plus me-2"></i>หยิบใส่ตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="category.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-box me-2"></i>ดูสินค้าทั้งหมด
                </a>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>