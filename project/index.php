<?php
session_start();
require_once 'db_connect.php';
$page_title = 'หน้าแรก';
include 'includes/header.php';

// ดึงข้อมูลหมวดหมู่
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name LIMIT 8");
$products = fetchAll("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
?>

<style>
/* Animated Background */
.animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
}

.animated-bg span {
    position: absolute;
    display: block;
    width: 20px;
    height: 20px;
    background: rgba(37, 99, 235, 0.03);
    animation: animate 20s linear infinite;
    bottom: -150px;
}

.animated-bg span:nth-child(1) {
    left: 25%;
    width: 80px;
    height: 80px;
    animation-delay: 0s;
    background: rgba(37, 99, 235, 0.02);
}

.animated-bg span:nth-child(2) {
    left: 10%;
    width: 40px;
    height: 40px;
    animation-delay: 2s;
    animation-duration: 12s;
    background: rgba(16, 185, 129, 0.02);
}

.animated-bg span:nth-child(3) {
    left: 70%;
    width: 60px;
    height: 60px;
    animation-delay: 4s;
    background: rgba(245, 158, 11, 0.02);
}

.animated-bg span:nth-child(4) {
    left: 40%;
    width: 100px;
    height: 100px;
    animation-delay: 0s;
    animation-duration: 18s;
    background: rgba(37, 99, 235, 0.02);
}

.animated-bg span:nth-child(5) {
    left: 65%;
    width: 30px;
    height: 30px;
    animation-delay: 0s;
    background: rgba(16, 185, 129, 0.02);
}

.animated-bg span:nth-child(6) {
    left: 75%;
    width: 120px;
    height: 120px;
    animation-delay: 3s;
    background: rgba(245, 158, 11, 0.02);
}

.animated-bg span:nth-child(7) {
    left: 35%;
    width: 150px;
    height: 150px;
    animation-delay: 7s;
    background: rgba(37, 99, 235, 0.02);
}

.animated-bg span:nth-child(8) {
    left: 50%;
    width: 25px;
    height: 25px;
    animation-delay: 15s;
    animation-duration: 45s;
    background: rgba(16, 185, 129, 0.02);
}

.animated-bg span:nth-child(9) {
    left: 20%;
    width: 45px;
    height: 45px;
    animation-delay: 2s;
    animation-duration: 35s;
    background: rgba(245, 158, 11, 0.02);
}

.animated-bg span:nth-child(10) {
    left: 85%;
    width: 90px;
    height: 90px;
    animation-delay: 0s;
    animation-duration: 11s;
    background: rgba(37, 99, 235, 0.02);
}

@keyframes animate {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
        border-radius: 0;
    }
    100% {
        transform: translateY(-1000px) rotate(720deg);
        opacity: 0;
        border-radius: 50%;
    }
}

/* Floating Animation */
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
    100% { transform: translateY(0px); }
}

.floating {
    animation: float 6s ease-in-out infinite;
}

/* Pulse Animation */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.pulse {
    animation: pulse 2s infinite;
}

/* Slide In Animation */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-content {
    animation: slideInLeft 1s ease;
}

.hero-image {
    animation: slideInRight 1s ease;
}

.category-card {
    animation: slideInUp 0.8s ease;
    animation-fill-mode: both;
}

.category-card:nth-child(1) { animation-delay: 0.1s; }
.category-card:nth-child(2) { animation-delay: 0.2s; }
.category-card:nth-child(3) { animation-delay: 0.3s; }
.category-card:nth-child(4) { animation-delay: 0.4s; }
.category-card:nth-child(5) { animation-delay: 0.5s; }
.category-card:nth-child(6) { animation-delay: 0.6s; }
.category-card:nth-child(7) { animation-delay: 0.7s; }
.category-card:nth-child(8) { animation-delay: 0.8s; }

.product-card {
    animation: slideInUp 0.8s ease;
    animation-fill-mode: both;
}

.product-card:nth-child(1) { animation-delay: 0.1s; }
.product-card:nth-child(2) { animation-delay: 0.15s; }
.product-card:nth-child(3) { animation-delay: 0.2s; }
.product-card:nth-child(4) { animation-delay: 0.25s; }
.product-card:nth-child(5) { animation-delay: 0.3s; }
.product-card:nth-child(6) { animation-delay: 0.35s; }
.product-card:nth-child(7) { animation-delay: 0.4s; }
.product-card:nth-child(8) { animation-delay: 0.45s; }

/* Hover Effects */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 30px rgba(0,0,0,0.1) !important;
}

.btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn:hover::after {
    width: 300px;
    height: 300px;
}

/* Parallax Effect */
.parallax {
    background-attachment: fixed;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
}

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #2563eb, #10b981);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Shine Effect */
.shine {
    position: relative;
    overflow: hidden;
}

.shine::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.shine:hover::before {
    left: 100%;
}
</style>

<!-- Animated Background -->
<div class="animated-bg">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
</div>

<div class="container">
    <!-- Hero Section with Parallax -->
    <div class="row min-vh-75 align-items-center py-5 position-relative">
        <div class="col-lg-6 hero-content">
            <h1 class="display-4 fw-bold mb-4">
                <span class="gradient-text">ช้อปปิ้งออนไลน์</span><br>
                ที่ง่ายและปลอดภัย
            </h1>
            <p class="lead text-muted mb-4">สินค้าคุณภาพจากร้านค้าชั้นนำ ราคาพิเศษสำหรับคุณ จัดส่งรวดเร็วทั่วประเทศ</p>
            <div class="d-flex gap-3">
                <a href="category.php" class="btn btn-primary btn-lg pulse">
                    <i class="fas fa-store me-2"></i>เริ่มช้อปปิ้ง
                </a>
                <a href="#how-it-works" class="btn btn-outline-secondary btn-lg shine">
                    <i class="fas fa-play-circle me-2"></i>ดูวิธีการสั่งซื้อ
                </a>
            </div>
            <div class="mt-4">
                <span class="badge bg-light text-dark me-2"><i class="fas fa-truck text-primary me-1"></i>จัดส่งฟรี</span>
                <span class="badge bg-light text-dark me-2"><i class="fas fa-shield-alt text-success me-1"></i>ปลอดภัย 100%</span>
                <span class="badge bg-light text-dark"><i class="fas fa-undo text-warning me-1"></i>คืนสินค้าได้ 7 วัน</span>
            </div>
        </div>
        <div class="col-lg-6 hero-image text-center">
            <div class="floating">
                <img src="https://via.placeholder.com/500x400/2563eb/ffffff?text=Shopping" alt="Shopping" class="img-fluid rounded-4 shadow-lg">
            </div>
        </div>
    </div>
    
    <!-- Categories Section -->
    <section class="py-5">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold gradient-text">หมวดหมู่สินค้า</h2>
            <p class="text-muted">เลือกซื้อสินค้าจากหลากหลายหมวดหมู่ที่คุณชื่นชอบ</p>
        </div>
        
        <div class="row g-4">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): 
                    $count = fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$category['id']])['count'] ?? 0;
                ?>
                <div class="col-md-3 col-6 category-card">
                    <a href="category.php?id=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="card text-center p-4 h-100 border-0 shadow-sm shine">
                            <div class="stat-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                <i class="<?php echo $category['icon'] ?? 'fas fa-tag'; ?>"></i>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo $count; ?> สินค้า</p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php 
                $sample_cats = ['เสื้อผ้า', 'อิเล็กทรอนิกส์', 'ของใช้ในบ้าน', 'สุขภาพ'];
                foreach ($sample_cats as $index => $cat): 
                ?>
                <div class="col-md-3 col-6 category-card">
                    <div class="card text-center p-4 h-100 border-0 shadow-sm">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="fas fa-tag"></i>
                        </div>
                        <h5 class="card-title"><?php echo $cat; ?></h5>
                        <p class="text-muted mb-0">0 สินค้า</p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Products Section -->
    <section class="py-5 bg-light rounded-4">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold gradient-text">สินค้ามาใหม่</h2>
                <p class="text-muted">พบกับสินค้าใหม่ล่าสุดที่มาแรงตอนนี้</p>
            </div>
            
            <div class="row g-4">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-6 product-card">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="position-relative overflow-hidden">
                                <div style="height: 200px; overflow: hidden;">
                                    <?php 
                                    $image_path = "uploads/products/" . $product['id'] . ".jpg";
                                    if (file_exists($image_path)): ?>
                                        <img src="<?php echo $image_path . '?t=' . time(); ?>" class="card-img-top" alt="<?php echo $product['name']; ?>" style="height: 200px; object-fit: cover; transition: transform 0.5s;">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/200x200?text=No+Image" class="card-img-top" alt="No Image" style="height: 200px; object-fit: cover;">
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
                                <button class="btn btn-primary w-100 shine" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>หยิบใส่ตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="col-lg-3 col-md-4 col-6 product-card">
                        <div class="card h-100 border-0 shadow-sm">
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
                <a href="category.php" class="btn btn-outline-primary btn-lg shine">
                    <i class="fas fa-box me-2"></i>ดูสินค้าทั้งหมด
                </a>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>