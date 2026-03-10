<?php
require_once 'connectdb.php';
require_once 'includes/config.php';
include 'includes/new-header.php';

// Test database connection
if (!testConnection($conn)) {
    echo showError("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
}

// Fetch featured products
$featured_query = "SELECT p.*, c.category_name, s.store_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN stores s ON p.store_id = s.id 
                  ORDER BY p.created_at DESC 
                  LIMIT 3";
$featured_products = $conn->query($featured_query);
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1>
                Start <span>Shopping</span><br>
                ค้นพบสินค้าใหม่<br>
                ในราคาที่ถูกใจ
            </h1>
            <p>เลือกซื้อสินค้าคุณภาพเยี่ยมจากผู้ขายที่เชื่อถือได้ รับประกันการจัดส่งที่รวดเร็ว การชำระเงินที่ปลอดภัย และบริการลูกค้าที่เป็นเลิศ</p>
            <div class="hero-buttons">
                <a href="category.php" class="btn btn-primary">ค้นหาสินค้า</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="featured-section">
    <div class="section-header">
        <div>
            <h2>สินค้ามาใหม่</h2>
            <p>คัดสรรมาอย่างพิถีพิถันจากคอลเล็กชันระดับพรีเมียมของเรา</p>
        </div>
        <a href="category.php" class="ดูทั้งหมด">
            ดูทั้งหมด <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    
    <div class="product-grid">
        <?php while ($product = $featured_products->fetch_assoc()): ?>
            <div class="product-card">
                <?php if ($product['stock'] < 10): ?>
                    <div class="product-badge">Limited Stock</div>
                <?php endif; ?>
                
                <?php if ($product['image'] && file_exists("assets/images/" . $product['image'])): ?>
                    <img src="assets/images/<?php echo $product['image']; ?>" 
                         alt="<?php echo $product['product_name']; ?>" 
                         class="product-image">
                <?php else: ?>
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Product" 
                         class="product-image">
                <?php endif; ?>
                
                <div class="product-info">
                    <div class="product-category"><?php echo $product['category_name']; ?></div>
                    <h3 class="product-title"><?php echo $product['product_name']; ?></h3>
                    <div class="product-variant">Black • 2 Colors Available</div>
                    <div class="product-price">
                        <span class="current-price">฿<?php echo number_format($product['price'], 2); ?></span>
                        <?php if ($product['price'] > 1000): ?>
                            <span class="old-price">฿<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">ดูรายละเอียด</a>
                                    <?php if (isLoggedIn()): ?>
                                        <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn btn-red">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                    <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</section>



<!-- Company Info Section -->
<section class="featured-section" style="text-align: center;">
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
        <div>
            <i class="fas fa-truck" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>จัดส่งรวดเร็ว</h3>
            <p>จัดส่งฟรีเมื่อสั่งซื้อสินค้าครบ 500 บาท</p>
        </div>
        <div>
            <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>การชำระเงินที่ปลอดภัย</h3>
            <p>ธุรกรรมปลอดภัย 100%</p>
        </div>
        <div>
            <i class="fas fa-undo" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>คืนสินค้าได้ง่าย</h3>
            <p>นโยบายการคืนสินค้าภายใน 30 วัน</p>
        </div>
        <div>
            <i class="fas fa-headset" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>บริการสนับสนุนตลอด 24/7</h3>
            <p>บริการลูกค้าที่ทุ่มเท</p>
        </div>
    </div>
</section>

<?php include 'includes/new-footer.php'; ?>