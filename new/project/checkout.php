<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'checkout.php';
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get cart items
$cart_query = "SELECT c.*, p.product_name, p.price, p.stock, p.image 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = $user_id";
$cart_items = $conn->query($cart_query);

// Calculate total
$total = 0;
$cart_data = [];
if ($cart_items && $cart_items->num_rows > 0) {
    while ($item = $cart_items->fetch_assoc()) {
        $total += $item['price'] * $item['quantity'];
        $cart_data[] = $item;
    }
}

if (empty($cart_data)) {
    $_SESSION['error'] = "ไม่มีสินค้าในตะกร้า";
    redirect('cart.php');
}

// Process order
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $payment_method = sanitize($_POST['payment_method']);
    
    // Validate
    $errors = [];
    if (empty($address)) $errors[] = "กรุณาระบุที่อยู่จัดส่ง";
    if (empty($phone)) $errors[] = "กรุณาระบุเบอร์โทรศัพท์";
    
    // Check stock
    foreach ($cart_data as $item) {
        if ($item['quantity'] > $item['stock']) {
            $errors[] = "สินค้า {$item['product_name']} มีจำนวนในสต็อกไม่เพียงพอ (คงเหลือ {$item['stock']} ชิ้น)";
        }
    }
    
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, phone, payment_method, order_status) 
                           VALUES ($user_id, $total, '$address', '$phone', '$payment_method', 'pending')";
            $conn->query($order_query);
            $order_id = $conn->insert_id;
            
            // Create order items and update stock
            foreach ($cart_data as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                    VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['price']})";
                $conn->query($order_item_query);
                
                // Update stock
                $new_stock = $item['stock'] - $item['quantity'];
                $update_stock = "UPDATE products SET stock = $new_stock WHERE id = {$item['product_id']}";
                $conn->query($update_stock);
            }
            
            // Clear cart
            $clear_cart = "DELETE FROM cart WHERE user_id = $user_id";
            $conn->query($clear_cart);
            
            // Create notification
            $notify_query = "INSERT INTO notifications (user_id, title, message, type) 
                            VALUES ($user_id, 'สั่งซื้อสำเร็จ', 'คำสั่งซื้อ #$order_id อยู่ในระหว่างดำเนินการ', 'order')";
            $conn->query($notify_query);
            
            $conn->commit();
            
            $_SESSION['order_success'] = $order_id;
            redirect('order-success.php');
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// ใช้ new-header.php แทน header.php
include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>สั่งซื้อสินค้า</h1>
        <p>กรุณาตรวจสอบข้อมูลและยืนยันการสั่งซื้อ</p>
    </div>
</div>

<div class="container">
    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; border-left: 4px solid #dc3545;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Shipping Information Form -->
        <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md);">
            <h2 style="margin-bottom: 1.5rem;">📦 ข้อมูลการจัดส่ง</h2>
            
            <form method="POST" id="checkoutForm">
                <div class="form-group">
                    <label>ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="phone">เบอร์โทรศัพท์ <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           placeholder="เช่น 0812345678" required>
                </div>
                
                <div class="form-group">
                    <label for="address">ที่อยู่จัดส่ง <span style="color: red;">*</span></label>
                    <textarea class="form-control" id="address" name="address" rows="4" 
                              placeholder="บ้านเลขที่ ถนน ตำบล/แขวง อำเภอ/เขต จังหวัด รหัสไปรษณีย์" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">วิธีการชำระเงิน <span style="color: red;">*</span></label>
                    <select class="form-control" id="payment_method" name="payment_method" required>
                        <option value="">เลือกวิธีการชำระเงิน</option>
                        <option value="bank_transfer">🏦 โอนเงินผ่านธนาคาร</option>
                        <option value="credit_card">💳 บัตรเครดิต/เดบิต</option>
                        <option value="cod">💰 เก็บเงินปลายทาง</option>
                        <option value="promptpay">📱 พร้อมเพย์</option>
                        <option value="true_wallet">📱 TrueWallet</option>
                    </select>
                </div>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div>
            <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md); position: sticky; top: 100px;">
                <h2 style="margin-bottom: 1.5rem;">🛒 สรุปคำสั่งซื้อ</h2>
                
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1.5rem; padding-right: 0.5rem;">
                    <?php foreach ($cart_data as $item): ?>
                        <div style="display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid var(--light-gray);">
                            <?php if ($item['image'] && file_exists("assets/images/" . $item['image'])): ?>
                                <img src="assets/images/<?php echo $item['image']; ?>" 
                                     alt="<?php echo $item['product_name']; ?>" 
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;">
                            <?php else: ?>
                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #999;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div style="flex: 1;">
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.25rem;"><?php echo $item['product_name']; ?></h4>
                                <p style="font-size: 0.8rem; color: var(--medium-gray);">จำนวน: <?php echo $item['quantity']; ?> ชิ้น</p>
                                <p style="font-weight: 600; color: var(--primary-red);">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php 
                $subtotal = $total;
                $shipping = $total >= 500 ? 0 : 50;
                $tax = $total * 0.07;
                $grand_total = $subtotal + $shipping + $tax;
                ?>
                
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>ราคาสินค้า:</span>
                        <span>฿<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>ค่าจัดส่ง:</span>
                        <span>
                            <?php if ($shipping == 0): ?>
                                <span style="color: #28a745;">ฟรี</span>
                            <?php else: ?>
                                ฿<?php echo number_format($shipping, 2); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>ภาษีมูลค่าเพิ่ม 7%:</span>
                        <span>฿<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div style="border-top: 2px dashed var(--light-gray); margin: 1rem 0; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700;">
                            <span>ยอดรวมทั้งสิ้น:</span>
                            <span style="color: var(--primary-red);">฿<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($shipping > 0): ?>
                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                        <p style="color: var(--primary-blue); font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> ซื้อเพิ่มอีก ฿<?php echo number_format(500 - $subtotal, 2); ?> เพื่อรับสิทธิ์จัดส่งฟรี!
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Payment Methods Icons -->
                <div style="display: flex; justify-content: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: var(--light-gray); border-radius: 10px;">
                    <i class="fab fa-cc-visa" style="font-size: 2rem; color: #1a1f71;"></i>
                    <i class="fab fa-cc-mastercard" style="font-size: 2rem; color: #eb001b;"></i>
                    <img src="https://www.promptpay.co.th/images/promptpay-logo.png" style="height: 30px;">
                    <img src="https://static.truewallet.com/logo.png" style="height: 30px;">
                </div>
                
                <button type="submit" form="checkoutForm" name="place_order" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                    <i class="fas fa-check-circle"></i> ยืนยันการสั่งซื้อ
                </button>
                
                <a href="cart.php" class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem; text-align: center;">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้าตะกร้า
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
// ใช้ new-footer.php แทน footer.php
include 'includes/new-footer.php'; 
?>