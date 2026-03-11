<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connectdb.php';
require_once 'includes/config.php';

include 'includes/new-header.php';

// ดึงสินค้าทั้งหมด 20 ชิ้น
$products_query = "SELECT p.*, c.category_name, s.store_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN stores s ON p.store_id = s.id 
                  ORDER BY p.created_at DESC 
                  LIMIT 20";
$products = $conn->query($products_query);

// แยกสินค้าเป็น 2 กลุ่ม
$all_products = [];
while ($product = $products->fetch_assoc()) {
    $all_products[] = $product;
}

// สินค้าสำหรับ marquee (5 ชิ้นแรก)
$marquee_products = array_slice($all_products, 0, 5);
// สินค้าสำหรับ grid (ที่เหลือ 15 ชิ้น)
$grid_products = array_slice($all_products, 5, 15);
?>

<style>
/* Marquee Animation */
@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.marquee-container {
    width: 100%;
    overflow: hidden;
    position: relative;
    background: linear-gradient(90deg, 
        rgba(37,99,235,0.05) 0%, 
        rgba(239,68,68,0.05) 100%);
    padding: 20px 0;
    border-radius: 20px;
    margin-bottom: 3rem;
}

.marquee-content {
    display: flex;
    animation: marquee 30s linear infinite;
    width: fit-content;
}

.marquee-content:hover {
    animation-play-state: paused;
}

.marquee-item {
    flex: 0 0 auto;
    width: 280px;
    margin-right: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: var(--shadow-md);
    transition: transform 0.3s;
    position: relative;
    overflow: hidden;
}

.marquee-item:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: var(--shadow-xl);
}

.marquee-item .badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: var(--gradient-red);
    color: white;
    padding: 5px 15px;
    border-radius: 25px;
    font-size: 0.8rem;
    font-weight: 600;
    z-index: 2;
}

.marquee-item img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.5s;
}

.marquee-item:hover img {
    transform: scale(1.1);
}

.marquee-info {
    padding: 15px;
}

.marquee-info h4 {
    font-size: 1rem;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.marquee-price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.marquee-current {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--primary-red);
}

.marquee-old {
    font-size: 0.9rem;
    color: var(--medium-gray);
    text-decoration: line-through;
}

/* Gradient overlays for marquee */
.marquee-container::before,
.marquee-container::after {
    content: '';
    position: absolute;
    top: 0;
    width: 100px;
    height: 100%;
    z-index: 2;
    pointer-events: none;
}

.marquee-container::before {
    left: 0;
    background: linear-gradient(90deg, 
        rgba(255,255,255,1) 0%, 
        rgba(255,255,255,0) 100%);
}

.marquee-container::after {
    right: 0;
    background: linear-gradient(-90deg, 
        rgba(255,255,255,1) 0%, 
        rgba(255,255,255,0) 100%);
}

/* Product Grid */
.section-title {
    text-align: center;
    margin-bottom: 2rem;
}

.section-title h2 {
    font-size: 2rem;
    font-weight: 700;
    background: var(--gradient-blue);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.section-title p {
    color: var(--medium-gray);
}

.product-grid-vertical {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 2rem;
    margin-bottom: 3rem;
}

.product-card-vertical {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all 0.3s;
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.product-card-vertical:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.product-card-vertical .badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: var(--gradient-red);
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    z-index: 2;
}

.product-card-vertical .image-container {
    height: 200px;
    overflow: hidden;
    position: relative;
}

.product-card-vertical img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.product-card-vertical:hover img {
    transform: scale(1.1);
}

.product-card-vertical .info {
    padding: 1.2rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.product-card-vertical .category {
    color: var(--primary-blue);
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.3rem;
}

.product-card-vertical h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    line-height: 1.3;
    height: 2.6rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-card-vertical .variant {
    color: var(--medium-gray);
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card-vertical .price-section {
    margin-top: auto;
    padding-top: 0.5rem;
}

.product-card-vertical .price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.8rem;
}

.product-card-vertical .current {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary-red);
}

.product-card-vertical .old {
    font-size: 0.8rem;
    color: var(--medium-gray);
    text-decoration: line-through;
}

.product-card-vertical .actions {
    display: flex;
    gap: 0.5rem;
}

.product-card-vertical .actions .btn {
    flex: 1;
    padding: 0.5rem;
    font-size: 0.8rem;
}

.product-card-vertical .actions .btn i {
    margin-right: 0.3rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .product-grid-vertical {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 992px) {
    .product-grid-vertical {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .product-grid-vertical {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .marquee-item {
        width: 220px;
    }
    
    .marquee-container::before,
    .marquee-container::after {
        width: 50px;
    }
}

@media (max-width: 480px) {
    .product-grid-vertical {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1>
                Start <span>Shopping</span><br>
                Discover Your Next<br>
                Favorite Purchase
            </h1>
            <p>Shop premium products from trusted sellers. Fast shipping, secure checkout, and exceptional customer service guaranteed.</p>
            <div class="hero-buttons">
                <a href="category.php" class="btn btn-primary">Browse Products</a>
                <a href="about.php" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1607082350899-7e8aa7c1e4b4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Shopping">
            <div class="sale-badge">
                SALE<br>50% OFF
            </div>
        </div>
    </div>
</section>

<!-- Marquee Products Row (5 items scrolling) -->
<section class="featured-section">
    <div class="section-title">
        <h2>⚡ เทรนด์มาแรง</h2>
        <p>สินค้ามาแรง ประจำสัปดาห์</p>
    </div>
    
    <div class="marquee-container">
        <div class="marquee-content">
            <!-- Show products twice for seamless loop -->
            <?php for ($i = 0; $i < 2; $i++): ?>
                <?php foreach ($marquee_products as $product): ?>
                    <div class="marquee-item">
                        <?php if ($product['stock'] < 5): ?>
                            <div class="badge">เหลือน้อย</div>
                        <?php endif; ?>
                        
                        <?php 
                        $image_url = "";
                        if (!empty($product['image'])) {
                            if (file_exists("assets/images/" . $product['image'])) {
                                $image_url = "assets/images/" . $product['image'];
                            } elseif (file_exists("assets/images/stores/" . $product['image'])) {
                                $image_url = "assets/images/stores/" . $product['image'];
                            }
                        }
                        
                        if ($image_url): ?>
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300" alt="Product">
                        <?php endif; ?>
                        
                        <div class="marquee-info">
                            <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                            <div class="marquee-price">
                                <span class="marquee-current">฿<?php echo number_format($product['price'], 2); ?></span>
                                <?php if ($product['price'] < $product['price'] * 1.2): ?>
                                    <span class="marquee-old">฿<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm" style="width: 100%; margin-top: 10px;">ดูสินค้า</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- Vertical Grid Products (3 rows x 5 columns = 15 items) -->
<section class="featured-section">
    <div class="section-title">
        <h2>🛍️ สินค้าแนะนำ</h2>
        <p>คัดสรรมาอย่างพิถีพิถันจากคอลเล็กชั่นระดับพรีเมี่ยมของเรา</p>
    </div>
    
    <?php if (empty($grid_products)): ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: 20px;">
            <p>ไม่มีสินค้าในระบบ</p>
        </div>
    <?php else: ?>
        <div class="product-grid-vertical">
            <?php foreach ($grid_products as $product): ?>
                <div class="product-card-vertical">
                    <?php if ($product['stock'] < 5): ?>
                        <div class="badge">Limited Stock</div>
                    <?php endif; ?>
                    
                    <div class="image-container">
                        <?php 
                        $image_url = "";
                        if (!empty($product['image'])) {
                            if (file_exists("assets/images/" . $product['image'])) {
                                $image_url = "assets/images/" . $product['image'];
                            } elseif (file_exists("assets/images/stores/" . $product['image'])) {
                                $image_url = "assets/images/stores/" . $product['image'];
                            }
                        }
                        
                        if ($image_url): ?>
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300" alt="Product">
                        <?php endif; ?>
                    </div>
                    
                    <div class="info">
                        <div class="category"><?php echo htmlspecialchars($product['category_name'] ?? 'สินค้าทั่วไป'); ?></div>
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <div class="variant">Black • 2 Colors Available</div>
                        
                        <div class="price-section">
                            <div class="price">
                                <span class="current">฿<?php echo number_format($product['price'], 2); ?></span>
                                <?php if ($product['price'] < $product['price'] * 1.2): ?>
                                    <span class="old">฿<?php echo number_format($product['price'] * 1.2, 2); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> ดู
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn btn-red">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Call to Action Banner -->
<section class="featured-section">
    <div class="sale-banner">
        <div class="sale-content">
            <h3>Special Offer</h3>
            <h2>CYBER MONDAY <span>SALE</span></h2>
            <p>Up to 70% off on selected items</p>
            <a href="category.php" class="btn btn-primary" style="margin-top: 2rem;">Shop Now</a>
        </div>
    </div>
</section>

<!-- Features -->
<section class="featured-section" style="text-align: center;">
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
        <div>
            <i class="fas fa-truck" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>Fast Shipping</h3>
            <p>Free shipping on orders over 500฿</p>
        </div>
        <div>
            <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>Secure Payment</h3>
            <p>100% secure transactions</p>
        </div>
        <div>
            <i class="fas fa-undo" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>Easy Returns</h3>
            <p>30-day return policy</p>
        </div>
        <div>
            <i class="fas fa-headset" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 1rem;"></i>
            <h3>24/7 Support</h3>
            <p>Dedicated customer service</p>
        </div>
    </div>
</section>

<?php include 'includes/new-footer.php'; ?>