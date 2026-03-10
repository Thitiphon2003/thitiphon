<?php
require_once 'includes/config.php';
include 'includes/new-header.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id == 0) {
    redirect('category.php');
}

// Get product details
$product_query = "SELECT p.*, c.category_name, s.store_name, s.store_description 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN stores s ON p.store_id = s.id 
                  WHERE p.id = $product_id";
$product_result = $conn->query($product_query);

if ($product_result->num_rows == 0) {
    redirect('category.php');
}

$product = $product_result->fetch_assoc();

// Get related products from same category
$related_query = "SELECT * FROM products 
                  WHERE category_id = {$product['category_id']} 
                  AND id != $product_id 
                  LIMIT 4";
$related_products = $conn->query($related_query);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        // Check if product already in cart
        $check = $conn->query("SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id");
        
        if ($check && $check->num_rows > 0) {
            $cart_item = $check->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            $conn->query("UPDATE cart SET quantity = $new_quantity WHERE id = {$cart_item['id']}");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
        }
        
        $_SESSION['cart_message'] = "เพิ่มสินค้าลงตะกร้าเรียบร้อย";
        redirect("product.php?id=$product_id");
    } else {
        $_SESSION['redirect_url'] = "product.php?id=$product_id";
        redirect('login.php');
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>รายละเอียดสินค้า</h1>
        <p>ข้อมูลและรายละเอียดของสินค้า</p>
    </div>
</div>

<div class="container">
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
            <?php 
            echo $_SESSION['cart_message']; 
            unset($_SESSION['cart_message']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Product Detail -->
    <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md); margin-bottom: 3rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
            <!-- Product Image -->
            <div>
                <?php if ($product['image'] && file_exists("assets/images/" . $product['image'])): ?>
                    <img src="assets/images/<?php echo $product['image']; ?>" 
                         alt="<?php echo $product['product_name']; ?>" 
                         style="width: 100%; height: auto; border-radius: 20px; box-shadow: var(--shadow-lg);">
                <?php else: ?>
                    <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #f0f0f0, #e0e0e0); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: #999;">
                        <i class="fas fa-image" style="font-size: 3rem;"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div>
                <!-- Category and Store -->
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <span style="background: var(--light-gray); padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem;">
                        <i class="fas fa-tag" style="color: var(--primary-blue);"></i> <?php echo $product['category_name']; ?>
                    </span>
                    <span style="background: var(--light-gray); padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem;">
                        <i class="fas fa-store" style="color: var(--primary-red);"></i> <?php echo $product['store_name']; ?>
                    </span>
                </div>
                
                <!-- Product Name -->
                <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: var(--dark-gray);">
                    <?php echo $product['product_name']; ?>
                </h1>
                
                <!-- Price -->
                <div style="margin-bottom: 2rem;">
                    <?php 
                    $old_price = $product['price'] * 1.2; // สมมติราคาเก่าสำหรับแสดงโปรโมชั่น
                    ?>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-size: 2.5rem; font-weight: 800; color: var(--primary-red);">
                            ฿<?php echo number_format($product['price'], 2); ?>
                        </span>
                        <?php if ($product['price'] < $old_price): ?>
                            <span style="font-size: 1.5rem; color: var(--medium-gray); text-decoration: line-through;">
                                ฿<?php echo number_format($old_price, 2); ?>
                            </span>
                            <span style="background: var(--primary-red); color: white; padding: 0.25rem 1rem; border-radius: 50px; font-size: 0.9rem; font-weight: 600;">
                                -20%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Description -->
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">รายละเอียดสินค้า</h3>
                    <p style="color: var(--dark-gray); line-height: 1.8;"><?php echo nl2br($product['product_description']); ?></p>
                </div>
                
                <!-- Stock Status -->
                <div style="margin-bottom: 2rem;">
                    <?php if ($product['stock'] > 0): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 12px; height: 12px; background: #28a745; border-radius: 50%;"></span>
                            <span style="color: #28a745; font-weight: 600;">สินค้าพร้อมส่ง</span>
                            <span style="color: var(--medium-gray);">(เหลือ <?php echo $product['stock']; ?> ชิ้น)</span>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 12px; height: 12px; background: var(--primary-red); border-radius: 50%;"></span>
                            <span style="color: var(--primary-red); font-weight: 600;">สินค้าหมดชั่วคราว</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Add to Cart Form -->
                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" style="margin-bottom: 2rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 120px;">
                                <label style="display: block; margin-bottom: 0.5rem;">จำนวน</label>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                       style="width: 100%; padding: 0.75rem; border: 2px solid var(--light-gray); border-radius: 10px; font-size: 1rem;">
                            </div>
                            
                            <?php if (isLoggedIn()): ?>
                                <button type="submit" name="add_to_cart" class="btn btn-primary" style="flex: 1; padding: 1rem; font-size: 1.1rem;">
                                    <i class="fas fa-shopping-cart"></i> เพิ่มลงตะกร้า
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary" style="flex: 1; padding: 1rem; font-size: 1.1rem; text-align: center;">
                                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบเพื่อสั่งซื้อ
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary" style="width: 100%; padding: 1rem; font-size: 1.1rem;" disabled>
                        <i class="fas fa-times-circle"></i> สินค้าหมดชั่วคราว
                    </button>
                <?php endif; ?>
                
                <!-- Product Meta -->
                <div style="border-top: 1px solid var(--light-gray); padding-top: 1.5rem;">
                    <div style="display: flex; gap: 2rem;">
                        <div>
                            <i class="fas fa-truck" style="color: var(--primary-blue);"></i>
                            <span style="margin-left: 0.5rem;">จัดส่งฟรีเมื่อซื้อครบ 500 บาท</span>
                        </div>
                        <div>
                            <i class="fas fa-undo" style="color: var(--primary-blue);"></i>
                            <span style="margin-left: 0.5rem;">คืนสินค้าได้ภายใน 7 วัน</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Store Info -->
        <?php if ($product['store_description']): ?>
            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px solid var(--light-gray);">
                <h3 style="margin-bottom: 1rem;">เกี่ยวกับร้านค้า: <?php echo $product['store_name']; ?></h3>
                <p style="color: var(--dark-gray);"><?php echo $product['store_description']; ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Related Products -->
    <?php if ($related_products && $related_products->num_rows > 0): ?>
        <section class="featured-section">
            <div class="section-header">
                <div>
                    <h2>สินค้าที่เกี่ยวข้อง</h2>
                    <p>คุณอาจสนใจสินค้าเหล่านี้</p>
                </div>
                <a href="category.php?id=<?php echo $product['category_id']; ?>" class="view-all">
                    ดูทั้งหมด <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="product-grid">
                <?php while ($related = $related_products->fetch_assoc()): ?>
                    <div class="product-card">
                        <?php if ($related['stock'] < 5): ?>
                            <div class="product-badge">เหลือน้อย</div>
                        <?php endif; ?>
                        
                        <?php if ($related['image'] && file_exists("assets/images/" . $related['image'])): ?>
                            <img src="assets/images/<?php echo $related['image']; ?>" 
                                 alt="<?php echo $related['product_name']; ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #f0f0f0, #e0e0e0); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="font-size: 2rem; color: #999;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-title"><?php echo $related['product_name']; ?></h3>
                            <div class="product-price">
                                <?php 
                                $related_old = $related['price'] * 1.2;
                                ?>
                                <span class="current-price">฿<?php echo number_format($related['price'], 2); ?></span>
                                <?php if ($related['price'] < $related_old): ?>
                                    <span class="old-price">฿<?php echo number_format($related_old, 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                </a>
                                <?php if (isLoggedIn() && $related['stock'] > 0): ?>
                                    <a href="cart.php?add=<?php echo $related['id']; ?>" class="btn btn-red">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php include 'includes/new-footer.php'; ?>