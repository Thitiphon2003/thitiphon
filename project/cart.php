<?php
session_start();
require_once 'db_connect.php';

$page_title = 'ตะกร้าสินค้า';
include 'includes/header.php';

// ดึงข้อมูลตะกร้าสินค้าจากฐานข้อมูล (ตัวอย่าง)
// ในระบบจริงควรดึงจากตาราง cart_items ที่เชื่อมกับ user_id
$user_id = $_SESSION['user_id'] ?? 0;

// ตัวอย่างข้อมูลตะกร้าสินค้า (ในระบบจริงจะ query จากฐานข้อมูล)
$cart_items = [
    [
        'id' => 1,
        'name' => 'เสื้อยืดคอปก ผู้ชาย',
        'price' => 299,
        'original_price' => 459,
        'quantity' => 2,
        'image' => '',
        'category' => 'เสื้อผ้า',
        'seller' => 'ร้านชายสี่บะหมี่เกี๊ยว',
        'shipping_fee' => 50,
        'stock' => 50,
        'selected' => true
    ],
    [
        'id' => 2,
        'name' => 'หูฟังไร้สาย Bluetooth 5.0',
        'price' => 1290,
        'original_price' => 1890,
        'quantity' => 1,
        'image' => '',
        'category' => 'อิเล็กทรอนิกส์',
        'seller' => 'ร้านไอทีออนไลน์',
        'shipping_fee' => 30,
        'stock' => 20,
        'selected' => true
    ],
    [
        'id' => 3,
        'name' => 'กระเป๋าสะพายหนังแท้',
        'price' => 890,
        'original_price' => 1290,
        'quantity' => 1,
        'image' => '',
        'category' => 'แฟชั่น',
        'seller' => 'ร้านแฟชั่นช้อป',
        'shipping_fee' => 40,
        'stock' => 15,
        'selected' => false
    ]
];

// คำนวณราคารวม
$subtotal = 0;
$total_shipping = 0;
$selected_count = 0;

foreach ($cart_items as $item) {
    if ($item['selected']) {
        $subtotal += $item['price'] * $item['quantity'];
        $total_shipping += $item['shipping_fee'];
        $selected_count++;
    }
}

$total = $subtotal + $total_shipping;
$is_logged_in = isset($_SESSION['user_id']);
?>

<style>
/* Cart Page Styles */
.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.cart-header {
    background: linear-gradient(135deg, #2563eb10, #10b98110);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.cart-item {
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: white;
}

.cart-item:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-color: #2563eb;
}

.cart-item-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.seller-badge {
    background: #f1f5f9;
    padding: 0.25rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-selector {
    display: inline-flex;
    align-items: center;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.quantity-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: white;
    color: #2563eb;
    font-weight: bold;
    transition: all 0.3s;
}

.quantity-btn:hover {
    background: #2563eb;
    color: white;
}

.quantity-input {
    width: 50px;
    height: 36px;
    border: none;
    border-left: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
    text-align: center;
    font-weight: 500;
}

.cart-summary {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    position: sticky;
    top: 100px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px dashed #e2e8f0;
}

.summary-row.total {
    border-bottom: none;
    font-size: 1.2rem;
    font-weight: 700;
    color: #2563eb;
}

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
}

.empty-cart i {
    font-size: 4rem;
    color: #2563eb;
    opacity: 0.3;
    margin-bottom: 1rem;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.cart-item {
    animation: slideIn 0.5s ease;
    animation-fill-mode: both;
}

.cart-item:nth-child(1) { animation-delay: 0.1s; }
.cart-item:nth-child(2) { animation-delay: 0.2s; }
.cart-item:nth-child(3) { animation-delay: 0.3s; }
</style>

<div class="container cart-container">
    <div class="cart-header">
        <h1 class="h3 mb-0">
            <i class="fas fa-shopping-cart text-primary me-2"></i>
            ตะกร้าสินค้า
        </h1>
        <p class="text-muted mb-0 mt-2">มีสินค้าในตะกร้า <?php echo count($cart_items); ?> รายการ</p>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>ตะกร้าสินค้าว่างเปล่า</h3>
            <p class="text-muted mb-4">เริ่มช้อปปิ้งและเพิ่มสินค้าลงในตะกร้ากันเลย!</p>
            <a href="category.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i>เริ่มช้อปปิ้ง
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- รายการสินค้า -->
            <div class="col-lg-8">
                <?php 
                $sellers = array_unique(array_column($cart_items, 'seller'));
                foreach ($sellers as $seller): 
                ?>
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="seller-badge">
                            <i class="fas fa-store text-primary"></i>
                            <?php echo htmlspecialchars($seller); ?>
                        </span>
                        <span class="text-muted small">
                            <i class="fas fa-truck me-1"></i>
                            ค่าจัดส่งเริ่มต้น ฿<?php echo number_format($cart_items[0]['shipping_fee']); ?>
                        </span>
                    </div>
                    
                    <?php foreach ($cart_items as $item): 
                        if ($item['seller'] == $seller):
                    ?>
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" <?php echo $item['selected'] ? 'checked' : ''; ?> onchange="toggleItem(<?php echo $item['id']; ?>)">
                                </div>
                            </div>
                            <div class="col-auto">
                                <?php 
                                $image_path = "uploads/products/" . $item['id'] . ".jpg";
                                if (file_exists($image_path)): ?>
                                    <img src="<?php echo $image_path . '?t=' . time(); ?>" class="cart-item-image" alt="<?php echo $item['name']; ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/100x100?text=Product" class="cart-item-image" alt="Product">
                                <?php endif; ?>
                            </div>
                            <div class="col">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <div class="small text-muted mb-2">
                                    <span class="me-3"><i class="fas fa-tag me-1"></i><?php echo $item['category']; ?></span>
                                    <span><i class="fas fa-box me-1"></i>คงเหลือ <?php echo $item['stock']; ?> ชิ้น</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="addToWishlist(<?php echo $item['id']; ?>)">
                                        <i class="far fa-heart me-1"></i>เก็บไว้ภายหลัง
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="removeItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash me-1"></i>ลบ
                                    </button>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="mb-2">
                                        <span class="fw-bold text-primary">฿<?php echo number_format($item['price']); ?></span>
                                        <?php if ($item['original_price'] > $item['price']): ?>
                                            <small class="text-muted text-decoration-line-through ms-2">฿<?php echo number_format($item['original_price']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="quantity-selector">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="text" class="quantity-input" value="<?php echo $item['quantity']; ?>" readonly>
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- สรุปคำสั่งซื้อ -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="mb-4">สรุปคำสั่งซื้อ</h5>
                    
                    <div class="summary-row">
                        <span>ราคาสินค้า (<?php echo $selected_count; ?> รายการ)</span>
                        <span class="fw-bold">฿<?php echo number_format($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>ค่าจัดส่ง</span>
                        <span class="fw-bold">฿<?php echo number_format($total_shipping); ?></span>
                    </div>
                    
                    <?php if ($subtotal > 0): ?>
                    <div class="summary-row text-success">
                        <span>ประหยัด</span>
                        <span>-฿0</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>ยอดสุทธิ</span>
                        <span>฿<?php echo number_format($total); ?></span>
                    </div>
                    
                    <?php if (!$is_logged_in): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            กรุณา <a href="login.php?redirect=cart.php" class="alert-link">เข้าสู่ระบบ</a> เพื่อดำเนินการสั่งซื้อ
                        </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary w-100 py-3 mt-3" <?php echo !$is_logged_in ? 'disabled' : ''; ?> onclick="checkout()">
                        <i class="fas fa-credit-card me-2"></i>ดำเนินการสั่งซื้อ
                    </button>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1 text-success"></i>
                            ซื้ออย่างปลอดภัย มั่นใจได้ทุกคำสั่งซื้อ
                        </small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(productId, delta) {
    // ในระบบจริงจะ AJAX ไป update ที่ database
    console.log('Update quantity:', productId, delta);
    location.reload(); // รีโหลดเพื่อดูการเปลี่ยนแปลง
}

function toggleItem(productId) {
    console.log('Toggle item:', productId);
}

function addToWishlist(productId) {
    alert('เพิ่มสินค้าในรายการที่ชอบแล้ว');
}

function removeItem(productId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้ออกจากตะกร้า?')) {
        console.log('Remove item:', productId);
        location.reload();
    }
}

function checkout() {
    <?php if (!$is_logged_in): ?>
        window.location.href = 'login.php?redirect=cart.php';
    <?php else: ?>
        window.location.href = 'checkout.php';
    <?php endif; ?>
}
</script>

<?php include 'includes/footer.php'; ?>