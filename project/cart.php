<?php
session_start();
require_once 'db_connect.php';

$page_title = 'ตะกร้าสินค้า';
include 'includes/header.php';

// ตัวอย่างข้อมูลตะกร้าสินค้า
$cart_items = [
    [
        'id' => 1,
        'name' => 'แก้วน้ำเก็บความเย็นสแตนเลส',
        'price' => 350,
        'original_price' => 590,
        'quantity' => 2,
        'image' => '',
        'category' => 'ของใช้ในบ้าน',
        'stock' => 500
    ],
    [
        'id' => 2,
        'name' => 'หมอนข้าง memory foam',
        'price' => 490,
        'original_price' => 890,
        'quantity' => 1,
        'image' => '',
        'category' => 'ของใช้ในบ้าน',
        'stock' => 150
    ],
    [
        'id' => 3,
        'name' => 'ชุดเครื่องสำอาง 6 ชิ้น',
        'price' => 790,
        'original_price' => 1590,
        'quantity' => 1,
        'image' => '',
        'category' => 'สุขภาพและความงาม',
        'stock' => 80
    ]
];

$subtotal = 0;
foreach($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 50;
$total = $subtotal + $shipping;
?>

<div class="container" style="margin: 2rem auto;">
    <h1 style="font-size: 2rem; color: #0f172a; margin-bottom: 2rem;">ตะกร้าสินค้า</h1>

    <div style="display: grid; grid-template-columns: 1fr 380px; gap: 2rem;">
        <!-- รายการสินค้า -->
        <div>
            <?php foreach($cart_items as $item): ?>
                <div class="cart-item" data-price="<?php echo $item['price']; ?>">
                    <div class="cart-item-image">
                        <?php if($item['image']): ?>
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        <?php else: ?>
                            <i class="fas fa-box"></i>
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-details">
                        <h3 class="cart-item-name"><?php echo $item['name']; ?></h3>
                        <div class="cart-item-price">฿<?php echo number_format($item['price']); ?> ต่อชิ้น</div>
                        <?php if($item['original_price'] > $item['price']): ?>
                            <div style="color: #94a3b8; font-size: 0.8rem; text-decoration: line-through;">
                                ฿<?php echo number_format($item['original_price']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="cart-item-actions">
                            <div class="quantity-selector">
                                <button class="quantity-btn" onclick="updateQuantity(this, -1)">-</button>
                                <input type="text" class="quantity-input" value="<?php echo $item['quantity']; ?>" readonly>
                                <button class="quantity-btn" onclick="updateQuantity(this, 1)">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                                <i class="fas fa-trash"></i> ลบ
                            </button>
                        </div>
                    </div>
                    <div style="font-weight: 600; color: #0f172a;">
                        ฿<?php echo number_format($item['price'] * $item['quantity']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- สรุปคำสั่งซื้อ -->
        <div class="cart-summary">
            <h3 style="margin-bottom: 1.5rem;">สรุปคำสั่งซื้อ</h3>
            
            <div class="summary-row">
                <span>ราคาสินค้า</span>
                <span>฿<?php echo number_format($subtotal); ?></span>
            </div>
            <div class="summary-row">
                <span>ค่าจัดส่ง</span>
                <span>฿<?php echo number_format($shipping); ?></span>
            </div>
            
            <div class="summary-row total">
                <span>ยอดสุทธิ</span>
                <span>฿<?php echo number_format($total); ?></span>
            </div>
            
            <a href="checkout.php" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 1rem;">
                ดำเนินการสั่งซื้อ
            </a>
            
            <div style="margin-top: 1rem; text-align: center; color: #64748b; font-size: 0.9rem;">
                <i class="fas fa-shield-alt"></i> ซื้ออย่างปลอดภัย มั่นใจได้ทุกคำสั่งซื้อ
            </div>
        </div>
    </div>
</div>

<script>
function updateQuantity(btn, delta) {
    const input = btn.parentElement.querySelector('.quantity-input');
    let value = parseInt(input.value) + delta;
    if(value < 1) value = 1;
    input.value = value;
    // อัปเดตราคารวม
    location.reload(); // ตัวอย่างง่ายๆ
}

function removeItem(id) {
    if(confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้ออกจากตะกร้า?')) {
        alert('ลบสินค้าเรียบร้อย');
        location.reload();
    }
}
</script>

<?php include 'includes/footer.php'; ?>