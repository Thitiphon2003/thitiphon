<?php
session_start();
require_once 'db_connect.php';

// บังคับให้เข้าสู่ระบบก่อนเข้าใช้งาน
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'checkout.php';
    header('Location: login.php?redirect=checkout.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'ชำระเงิน';
include 'includes/header.php';

// ============================================
// ดึงข้อมูลตะกร้าสินค้าที่เลือกไว้
// ============================================
$cart_items = fetchAll("SELECT ci.*, p.name, p.price, p.shipping_fee, p.stock,
                               c.name as category_name, s.name as seller_name
                        FROM cart_items ci
                        JOIN products p ON ci.product_id = p.id
                        LEFT JOIN categories c ON p.category_id = c.id
                        LEFT JOIN sellers s ON p.seller_id = s.id
                        WHERE ci.user_id = ? AND ci.selected = 1
                        ORDER BY ci.created_at DESC", [$user_id]);

if (empty($cart_items)) {
    $_SESSION['error'] = 'กรุณาเลือกสินค้าที่ต้องการสั่งซื้อ';
    header('Location: cart.php');
    exit();
}

// ============================================
// ดึงข้อมูลผู้ใช้
// ============================================
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

// ============================================
// ดึงข้อมูลที่อยู่
// ============================================
$addresses = fetchAll("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC", [$user_id]);

// ============================================
// คำนวณราคา
// ============================================
$subtotal = 0;
$total_shipping = 0;
$items_count = count($cart_items);

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_shipping += $item['shipping_fee'] ?? 0;
}

$total = $subtotal + $total_shipping;

// ============================================
// จัดการการส่งคำสั่งซื้อ
// ============================================
$order_success = false;
$order_number = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    try {
        // รับข้อมูลจากฟอร์ม
        $address_id = (int)($_POST['address_id'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'bank_transfer';
        $notes = trim($_POST['notes'] ?? '');
        
        // ตรวจสอบที่อยู่
        if ($address_id <= 0) {
            throw new Exception('กรุณาเลือกที่อยู่จัดส่ง');
        }
        
        $address = fetchOne("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?", [$address_id, $user_id]);
        if (!$address) {
            throw new Exception('ไม่พบที่อยู่ที่เลือก');
        }
        
        // เริ่ม transaction
        $pdo->beginTransaction();
        
        // ตรวจสอบสต็อกสินค้าอีกครั้ง
        foreach ($cart_items as $item) {
            $product = fetchOne("SELECT stock FROM products WHERE id = ? FOR UPDATE", [$item['product_id']]);
            if ($product['stock'] < $item['quantity']) {
                throw new Exception("สินค้า {$item['name']} มีจำนวนไม่เพียงพอ (คงเหลือ {$product['stock']} ชิ้น)");
            }
        }
        
        // สร้างหมายเลขคำสั่งซื้อ (ORD-YYYYMMDD-XXXX)
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $order_number = "ORD-{$date}-{$random}";
        
        // ตรวจสอบหมายเลขซ้ำ
        $check = fetchOne("SELECT id FROM orders WHERE order_number = ?", [$order_number]);
        if ($check) {
            // ถ้าซ้ำให้สุ่มใหม่
            $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $order_number = "ORD-{$date}-{$random}";
        }
        
        // บันทึกคำสั่งซื้อ
        $order_sql = "INSERT INTO orders (
            order_number, user_id, address_id, shipping_method, shipping_fee,
            subtotal, total, payment_method, payment_status, order_status, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())";
        
        query($order_sql, [
            $order_number,
            $user_id,
            $address_id,
            'standard',
            $total_shipping,
            $subtotal,
            $total,
            $payment_method,
            $notes
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // บันทึกรายการสินค้าในคำสั่งซื้อ
        foreach ($cart_items as $item) {
            $item_sql = "INSERT INTO order_items (
                order_id, product_id, product_name, price, quantity, subtotal
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            query($item_sql, [
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                $item['price'] * $item['quantity']
            ]);
            
            // อัปเดตสต็อกสินค้า
            query("UPDATE products SET stock = stock - ? WHERE id = ?", 
                  [$item['quantity'], $item['product_id']]);
        }
        
        // ลบสินค้าที่เลือกออกจากตะกร้า
        query("DELETE FROM cart_items WHERE user_id = ? AND selected = 1", [$user_id]);
        
        $pdo->commit();
        
        // เก็บข้อมูลใน session เพื่อแสดงในหน้าถัดไป
        $_SESSION['order_success'] = true;
        $_SESSION['order_number'] = $order_number;
        $_SESSION['order_total'] = $total;
        
        // Redirect ไปหน้า orders พร้อมพารามิเตอร์
        header('Location: orders.php?success=1&order=' . urlencode($order_number));
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}
?>

<style>
.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.checkout-header {
    background: linear-gradient(135deg, #2563eb10, #10b98110);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.checkout-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 3rem;
    position: relative;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 1;
}

.step::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e2e8f0;
    z-index: -1;
}

.step:first-child::before {
    left: 50%;
}

.step:last-child::before {
    right: 50%;
}

.step.active .step-number {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.step.completed .step-number {
    background: #10b981;
    color: white;
    border-color: #10b981;
}

.step-number {
    width: 48px;
    height: 48px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin: 0 auto 0.5rem;
    transition: all 0.3s;
}

.step-label {
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 500;
}

.step.active .step-label {
    color: #2563eb;
}

.address-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s;
    height: 100%;
}

.address-card:hover {
    border-color: #2563eb;
    box-shadow: 0 5px 20px rgba(37, 99, 235, 0.1);
}

.address-card.selected {
    border-color: #2563eb;
    background: #eff6ff;
}

.address-card .radio {
    position: absolute;
    opacity: 0;
}

.payment-method {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.payment-method:hover {
    border-color: #2563eb;
    background: #f8fafc;
}

.payment-method.selected {
    border-color: #2563eb;
    background: #eff6ff;
}

.payment-method input[type="radio"] {
    width: 20px;
    height: 20px;
    accent-color: #2563eb;
}

.order-summary {
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
    padding-top: 1rem;
    margin-top: 0.5rem;
}

.order-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
}

.order-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.order-item-details {
    flex: 1;
}

.order-item-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.order-item-meta {
    font-size: 0.85rem;
    color: #64748b;
}

.order-item-price {
    font-weight: 600;
    color: #2563eb;
    text-align: right;
}

.loading {
    position: relative;
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30px;
    height: 30px;
    margin-left: -15px;
    margin-top: -15px;
    border: 3px solid #e2e8f0;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    z-index: 10;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    min-width: 300px;
    margin-bottom: 10px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    animation: slideInRight 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.toast-success { background: #10b981; }
.toast-danger { background: #ef4444; }
.toast-warning { background: #f59e0b; }
.toast-info { background: #3b82f6; }

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .checkout-steps {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step::before {
        display: none;
    }
    
    .order-summary {
        position: static;
        margin-top: 2rem;
    }
}
</style>

<div class="container checkout-container">
    <!-- ขั้นตอนการชำระเงิน -->
    <div class="checkout-steps">
        <div class="step completed">
            <div class="step-number">✓</div>
            <div class="step-label">ตรวจสอบสินค้า</div>
        </div>
        <div class="step active">
            <div class="step-number">2</div>
            <div class="step-label">ข้อมูลจัดส่ง</div>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <div class="step-label">ชำระเงิน</div>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <div class="step-label">เสร็จสิ้น</div>
        </div>
    </div>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" id="checkoutForm" onsubmit="return validateForm()">
        <div class="row">
            <!-- ฟอร์มชำระเงิน -->
            <div class="col-lg-8">
                <!-- รายการสินค้า -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-box text-primary me-2"></i>
                            รายการสินค้า (<?php echo $items_count; ?> รายการ)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="order-item-image">
                                <?php 
                                $image_path = "uploads/products/" . $item['product_id'] . ".jpg";
                                if (file_exists($image_path)): ?>
                                    <img src="<?php echo $image_path . '?t=' . time(); ?>" alt="<?php echo $item['name']; ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/60x60?text=Product" alt="Product">
                                <?php endif; ?>
                            </div>
                            <div class="order-item-details">
                                <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="order-item-meta">
                                    <span class="me-3"><?php echo $item['category_name'] ?? 'ทั่วไป'; ?></span>
                                    <span>x<?php echo $item['quantity']; ?></span>
                                </div>
                            </div>
                            <div class="order-item-price">
                                ฿<?php echo number_format($item['price'] * $item['quantity']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- ที่อยู่จัดส่ง -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            ที่อยู่จัดส่ง <span class="text-danger">*</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-3">ยังไม่มีที่อยู่จัดส่ง กรุณาเพิ่มที่อยู่ก่อนดำเนินการสั่งซื้อ</p>
                                <button type="button" class="btn btn-primary" onclick="showAddressModal()">
                                    <i class="fas fa-plus me-2"></i>เพิ่มที่อยู่ใหม่
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($addresses as $address): ?>
                                <div class="col-md-6">
                                    <label class="address-card w-100 position-relative">
                                        <input type="radio" name="address_id" value="<?php echo $address['id']; ?>" 
                                               class="radio" <?php echo $address['is_default'] ? 'checked' : ''; ?> required>
                                        <div>
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="fw-bold"><?php echo htmlspecialchars($address['address_name']); ?></span>
                                                <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-primary">ค่าเริ่มต้น</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small">
                                                <div><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($address['recipient']); ?></div>
                                                <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($address['phone']); ?></div>
                                                <div class="mt-1">
                                                    <?php echo htmlspecialchars($address['address']); ?><br>
                                                    <?php echo htmlspecialchars($address['district'] . ' ' . $address['city'] . ' ' . $address['province']); ?><br>
                                                    <?php echo htmlspecialchars($address['postcode']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="showAddressModal()">
                                    <i class="fas fa-plus me-1"></i>เพิ่มที่อยู่ใหม่
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- วิธีการชำระเงิน -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card text-primary me-2"></i>
                            วิธีการชำระเงิน <span class="text-danger">*</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="payment-method w-100">
                                    <input type="radio" name="payment_method" value="bank_transfer" checked>
                                    <i class="fas fa-university fa-2x text-primary"></i>
                                    <div>
                                        <div class="fw-bold">โอนผ่านธนาคาร</div>
                                        <small class="text-muted">ธ.กรุงเทพ / กสิกรไทย / ไทยพาณิชย์</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-method w-100">
                                    <input type="radio" name="payment_method" value="credit_card">
                                    <i class="fas fa-credit-card fa-2x text-primary"></i>
                                    <div>
                                        <div class="fw-bold">บัตรเครดิต/เดบิต</div>
                                        <small class="text-muted">Visa / Mastercard / JCB</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-method w-100">
                                    <input type="radio" name="payment_method" value="promptpay">
                                    <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                                    <div>
                                        <div class="fw-bold">พร้อมเพย์</div>
                                        <small class="text-muted">สแกน QR Code เพื่อชำระเงิน</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-method w-100">
                                    <input type="radio" name="payment_method" value="cod">
                                    <i class="fas fa-truck fa-2x text-primary"></i>
                                    <div>
                                        <div class="fw-bold">เก็บเงินปลายทาง</div>
                                        <small class="text-muted">ชำระเงินเมื่อได้รับสินค้า</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- หมายเหตุ -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-pen text-primary me-2"></i>
                            หมายเหตุ (ถ้ามี)
                        </h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="3" placeholder="ระบุข้อความเพิ่มเติม..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- สรุปคำสั่งซื้อ -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <h5 class="mb-4">สรุปคำสั่งซื้อ</h5>
                    
                    <div class="summary-row">
                        <span>ราคาสินค้า (<?php echo $items_count; ?> รายการ)</span>
                        <span class="fw-bold">฿<?php echo number_format($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>ค่าจัดส่ง</span>
                        <span class="fw-bold">฿<?php echo number_format($total_shipping); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>ยอดสุทธิ</span>
                        <span>฿<?php echo number_format($total); ?></span>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="d-flex align-items-center gap-2 text-success mb-2">
                            <i class="fas fa-shield-alt"></i>
                            <small class="fw-bold">ซื้ออย่างปลอดภัย</small>
                        </div>
                        <small class="text-muted d-block mb-1">
                            <i class="fas fa-check-circle me-1"></i>ข้อมูลถูกเข้ารหัส
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-check-circle me-1"></i>รับประกันคืนสินค้า 7 วัน
                        </small>
                    </div>
                    
                    <!-- ปุ่มดำเนินการ -->
                    <div class="d-flex flex-column gap-2 mt-4">
                        <a href="cart.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left me-2"></i>กลับไปตะกร้าสินค้า
                        </a>
                        <button type="submit" name="place_order" class="btn btn-primary btn-lg w-100" id="placeOrderBtn">
                            <i class="fas fa-check-circle me-2"></i>ยืนยันคำสั่งซื้อ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal เพิ่มที่อยู่ -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มที่อยู่ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addressForm" onsubmit="saveAddress(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อที่อยู่ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="address_name" placeholder="เช่น บ้าน, ที่ทำงาน" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อ-นามสกุล ผู้รับ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="recipient" value="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ที่อยู่ <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="address" rows="2" placeholder="บ้านเลขที่, หมู่บ้าน, ถนน" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">แขวง/ตำบล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="district" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">เขต/อำเภอ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="city" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="province" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="postcode" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                            <label class="form-check-label" for="is_default">
                                ตั้งเป็นที่อยู่ค่าเริ่มต้น
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกที่อยู่</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}

function showAddressModal() {
    const modal = new bootstrap.Modal(document.getElementById('addressModal'));
    modal.show();
}

function saveAddress(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'add_address');
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
    
    fetch('address/save_address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('เพิ่มที่อยู่เรียบร้อย', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'เกิดข้อผิดพลาด', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'บันทึกที่อยู่';
        }
    })
    .catch(error => {
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'บันทึกที่อยู่';
    });
}

function validateForm() {
    // ตรวจสอบว่ามีที่อยู่หรือไม่
    <?php if (empty($addresses)): ?>
        showToast('กรุณาเพิ่มที่อยู่จัดส่งก่อนดำเนินการ', 'warning');
        return false;
    <?php endif; ?>
    
    // ตรวจสอบว่าเลือกที่อยู่หรือไม่
    const addressSelected = document.querySelector('input[name="address_id"]:checked');
    if (!addressSelected) {
        showToast('กรุณาเลือกที่อยู่จัดส่ง', 'warning');
        return false;
    }
    
    // ตรวจสอบว่าเลือกวิธีการชำระเงินหรือไม่
    const paymentSelected = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentSelected) {
        showToast('กรุณาเลือกวิธีการชำระเงิน', 'warning');
        return false;
    }
    
    return true;
}

// ป้องกันการ submit ซ้ำ
document.getElementById('checkoutForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังดำเนินการ...';
});
</script>

<?php include 'includes/footer.php'; ?>