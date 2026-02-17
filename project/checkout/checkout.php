<?php
require_once '../config/database.php';
require_once '../auth/login.php';
require_once '../address/address-manager.php';
require_once '../shipping/shipping-calculator.php';
require_once '../coupon/coupon-system.php';

// ตรวจสอบการเข้าสู่ระบบ
if(!isLoggedIn()) {
    header('Location: ../login.php?redirect=checkout.php');
    exit();
}

$user = currentUser();
$addressManager = new AddressManager($db);
$shippingCalculator = new ShippingCalculator($db);
$couponSystem = new CouponSystem($db);

// ดึงที่อยู่ของผู้ใช้
$addresses = $addressManager->getUserAddresses($user['id']);
$defaultAddress = $addressManager->getDefaultAddress($user['id']);

// ดึงข้อมูลตะกร้าสินค้าจากฐานข้อมูล
$cartItems = getCartItems($user['id']);

// คำนวณค่าจัดส่ง
$shippingOptions = [];
if($defaultAddress) {
    $shippingOptions = $shippingCalculator->calculateShipping(
        $cartItems, 
        $defaultAddress['province'],
        $defaultAddress['district']
    );
}

// ดึงคูปองที่ใช้ได้
$availableCoupons = $couponSystem->getAvailableCoupons($user['id']);

// จัดการการสั่งซื้อ
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action == 'place_order') {
        placeOrder($user, $_POST, $cartItems);
    }
}

function getCartItems($userId) {
    global $db;
    
    $sql = "SELECT ci.*, p.name, p.price, p.weight, p.stock, p.shipping_fee,
                   s.name as seller_name
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            JOIN sellers s ON p.seller_id = s.id
            WHERE ci.user_id = :user_id AND ci.selected = 1";
    
    return fetchAll($sql, [':user_id' => $userId]);
}

function placeOrder($user, $data, $items) {
    global $db, $couponSystem;
    
    try {
        $db->beginTransaction();
        
        // คำนวณยอดรวม
        $subtotal = 0;
        foreach($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $shippingFee = $data['shipping_fee'] ?? 0;
        $discount = $data['discount'] ?? 0;
        $total = $subtotal + $shippingFee - $discount;
        
        // สร้างคำสั่งซื้อ
        $orderSql = "INSERT INTO orders 
                     (order_number, user_id, address_id, shipping_method, shipping_fee, 
                      discount, subtotal, total, payment_method, payment_status, 
                      order_status, notes, created_at) 
                     VALUES 
                     (:order_number, :user_id, :address_id, :shipping_method, :shipping_fee,
                      :discount, :subtotal, :total, :payment_method, 'pending',
                      'pending', :notes, NOW())";
        
        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $stmt = $db->prepare($orderSql);
        $stmt->execute([
            ':order_number' => $orderNumber,
            ':user_id' => $user['id'],
            ':address_id' => $data['address_id'],
            ':shipping_method' => $data['shipping_method'],
            ':shipping_fee' => $shippingFee,
            ':discount' => $discount,
            ':subtotal' => $subtotal,
            ':total' => $total,
            ':payment_method' => $data['payment_method'],
            ':notes' => $data['notes'] ?? ''
        ]);
        
        $orderId = $db->lastInsertId();
        
        // บันทึกรายการสินค้า
        foreach($items as $item) {
            $itemSql = "INSERT INTO order_items 
                        (order_id, product_id, product_name, price, quantity, subtotal) 
                        VALUES 
                        (:order_id, :product_id, :product_name, :price, :quantity, :subtotal)";
            
            $stmt = $db->prepare($itemSql);
            $stmt->execute([
                ':order_id' => $orderId,
                ':product_id' => $item['product_id'],
                ':product_name' => $item['name'],
                ':price' => $item['price'],
                ':quantity' => $item['quantity'],
                ':subtotal' => $item['price'] * $item['quantity']
            ]);
            
            // ลดสต็อก
            $updateStock = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
            query($updateStock, [
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
        
        // บันทึกการใช้คูปอง
        if(!empty($data['coupon_code'])) {
            $couponSystem->applyCoupon($data['coupon_code'], $user['id'], $orderId);
        }
        
        // ล้างตะกร้าสินค้า
        $clearCart = "DELETE FROM cart_items WHERE user_id = :user_id AND selected = 1";
        query($clearCart, [':user_id' => $user['id']]);
        
        $db->commit();
        
        // ส่งอีเมลยืนยัน
        sendOrderConfirmationEmail($user['email'], $orderNumber, $items, $total);
        
        // ไปหน้าสำเร็จ
        header("Location: order-success.php?order_id=$orderId");
        exit();
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

function sendOrderConfirmationEmail($email, $orderNumber, $items, $total) {
    $subject = "ยืนยันคำสั่งซื้อ #$orderNumber - SHOP.COM";
    
    $message = "<html><body>";
    $message .= "<h2>ขอบคุณสำหรับคำสั่งซื้อ</h2>";
    $message .= "<p>เลขที่คำสั่งซื้อ: <strong>$orderNumber</strong></p>";
    $message .= "<h3>รายการสินค้า:</h3>";
    $message .= "<table border='1' cellpadding='5'>";
    $message .= "<tr><th>สินค้า</th><th>จำนวน</th><th>ราคา</th></tr>";
    
    foreach($items as $item) {
        $message .= "<tr>";
        $message .= "<td>{$item['name']}</td>";
        $message .= "<td>{$item['quantity']}</td>";
        $message .= "<td>" . number_format($item['price'] * $item['quantity']) . "</td>";
        $message .= "</tr>";
    }
    
    $message .= "<tr><td colspan='2' align='right'><strong>รวม</strong></td>";
    $message .= "<td><strong>" . number_format($total) . "</strong></td></tr>";
    $message .= "</table>";
    $message .= "</body></html>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@shop.com\r\n";
    
    mail($email, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - SHOP.COM</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        .checkout-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .section-title i {
            color: #667eea;
        }
        
        .address-option {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .address-option:hover {
            border-color: #667eea;
        }
        
        .address-option.selected {
            border-color: #667eea;
            background: #f0f3ff;
        }
        
        .address-option input[type="radio"] {
            margin-right: 1rem;
        }
        
        .address-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .address-detail {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .shipping-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        
        .shipping-option.selected {
            border-color: #667eea;
            background: #f0f3ff;
        }
        
        .shipping-info h4 {
            font-size: 1rem;
            margin-bottom: 0.3rem;
        }
        
        .shipping-info p {
            font-size: 0.8rem;
            color: #666;
        }
        
        .shipping-price {
            font-weight: 600;
            color: #667eea;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        
        .payment-method.selected {
            border-color: #667eea;
            background: #f0f3ff;
        }
        
        .payment-method img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 1rem;
        }
        
        .payment-method .method-name {
            font-weight: 500;
        }
        
        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.95rem;
        }
        
        .summary-item.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #f0f3ff;
        }
        
        .summary-item.total .price {
            color: #667eea;
        }
        
        .place-order-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }
        
        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.3);
        }
        
        .product-preview {
            display: flex;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f3ff;
        }
        
        .product-preview:last-child {
            border-bottom: none;
        }
        
        .product-preview img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .product-preview-info {
            flex: 1;
        }
        
        .product-preview-name {
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }
        
        .product-preview-meta {
            font-size: 0.8rem;
            color: #999;
        }
        
        .product-preview-price {
            font-weight: 600;
            color: #667eea;
        }
        
        @media (max-width: 992px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Left Column - Checkout Forms -->
        <div class="checkout-left">
            <div class="checkout-section">
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    <h2>ที่อยู่จัดส่ง</h2>
                </div>
                
                <?php foreach($addresses as $address): ?>
                    <div class="address-option <?php echo $address['is_default'] ? 'selected' : ''; ?>" onclick="selectAddress(<?php echo $address['id']; ?>)">
                        <input type="radio" name="address" value="<?php echo $address['id']; ?>" <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                        <span class="address-name">
                            <?php echo $address['address_name']; ?>
                            <?php if($address['is_default']): ?>
                                <span style="background: #667eea; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.7rem; margin-left: 0.5rem;">ค่าเริ่มต้น</span>
                            <?php endif; ?>
                        </span>
                        <div class="address-detail">
                            <?php echo $address['recipient']; ?> - <?php echo $address['phone']; ?><br>
                            <?php echo $address['address']; ?><br>
                            <?php echo $address['district']; ?> <?php echo $address['city']; ?><br>
                            <?php echo $address['province']; ?> <?php echo $address['postcode']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button class="btn-outline" onclick="showAddAddressModal()">
                    <i class="fas fa-plus"></i> เพิ่มที่อยู่ใหม่
                </button>
            </div>
            
            <div class="checkout-section">
                <div class="section-title">
                    <i class="fas fa-truck"></i>
                    <h2>วิธีการจัดส่ง</h2>
                </div>
                
                <?php foreach($shippingOptions as $index => $option): ?>
                    <div class="shipping-option <?php echo $index == 0 ? 'selected' : ''; ?>" onclick="selectShipping(<?php echo $index; ?>)">
                        <div class="shipping-info">
                            <h4><?php echo $option['name']; ?></h4>
                            <p><?php echo $option['description']; ?> • ถึงภายใน <?php echo $option['estimated_days']; ?> วัน</p>
                        </div>
                        <div class="shipping-price">
                            <?php if($option['discount'] > 0): ?>
                                <span style="text-decoration: line-through; color: #999; margin-right: 0.5rem;">฿<?php echo number_format($option['cost']); ?></span>
                            <?php endif; ?>
                            ฿<?php echo number_format($option['final_cost']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="checkout-section">
                <div class="section-title">
                    <i class="fas fa-credit-card"></i>
                    <h2>วิธีการชำระเงิน</h2>
                </div>
                
                <div class="payment-method selected" onclick="selectPayment('bank')">
                    <img src="https://via.placeholder.com/40x40?text=Bank" alt="Bank Transfer">
                    <div class="method-name">โอนผ่านธนาคาร</div>
                </div>
                
                <div class="payment-method" onclick="selectPayment('credit')">
                    <img src="https://via.placeholder.com/40x40?text=Credit" alt="Credit Card">
                    <div class="method-name">บัตรเครดิต/เดบิต</div>
                </div>
                
                <div class="payment-method" onclick="selectPayment('cod')">
                    <img src="https://via.placeholder.com/40x40?text=COD" alt="Cash on Delivery">
                    <div class="method-name">เก็บเงินปลายทาง</div>
                </div>
                
                <div class="payment-method" onclick="selectPayment('promptpay')">
                    <img src="https://via.placeholder.com/40x40?text=PromptPay" alt="PromptPay">
                    <div class="method-name">พร้อมเพย์</div>
                </div>
            </div>
            
            <div class="checkout-section">
                <div class="section-title">
                    <i class="fas fa-tag"></i>
                    <h2>โค้ดส่วนลด</h2>
                </div>
                
                <div class="coupon-input" style="display: flex; gap: 0.5rem;">
                    <input type="text" id="couponCode" placeholder="ใส่โค้ดส่วนลด" style="flex: 1; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 8px;">
                    <button onclick="applyCheckoutCoupon()" style="padding: 0.8rem 1.5rem; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">ใช้</button>
                </div>
                
                <?php if(!empty($availableCoupons)): ?>
                    <div style="margin-top: 1rem;">
                        <p style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem;">คูปองแนะนำ:</p>
                        <?php foreach($availableCoupons as $coupon): ?>
                            <div style="background: #f0f3ff; padding: 0.5rem; border-radius: 5px; margin-bottom: 0.5rem; font-size: 0.8rem; display: flex; justify-content: space-between;">
                                <span><strong><?php echo $coupon['code']; ?></strong> - <?php echo $coupon['description']; ?></span>
                                <button onclick="useCoupon('<?php echo $coupon['code']; ?>')" style="color: #667eea; border: none; background: none; cursor: pointer;">ใช้</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column - Order Summary -->
        <div class="checkout-right">
            <div class="order-summary">
                <h3 style="margin-bottom: 1rem;">สรุปคำสั่งซื้อ</h3>
                
                <?php foreach($cartItems as $item): ?>
                    <div class="product-preview">
                        <img src="<?php echo $item['image'] ?? 'https://via.placeholder.com/60x60'; ?>" alt="<?php echo $item['name']; ?>">
                        <div class="product-preview-info">
                            <div class="product-preview-name"><?php echo $item['name']; ?></div>
                            <div class="product-preview-meta">x<?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="product-preview-price">฿<?php echo number_format($item['price'] * $item['quantity']); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div style="margin: 1rem 0; padding: 0.5rem 0; border-top: 1px solid #f0f3ff; border-bottom: 1px solid #f0f3ff;">
                    <div class="summary-item">
                        <span>ราคาสินค้า</span>
                        <span>฿<?php echo number_format(array_sum(array_map(function($item) { return $item['price'] * $item['quantity']; }, $cartItems))); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>ค่าจัดส่ง</span>
                        <span id="displayShipping">฿0</span>
                    </div>
                    <div class="summary-item">
                        <span>ส่วนลด</span>
                        <span id="displayDiscount" style="color: #28a745;">-฿0</span>
                    </div>
                </div>
                
                <div class="summary-item total">
                    <span>ยอดสุทธิ</span>
                    <span class="price" id="displayTotal">฿<?php echo number_format(array_sum(array_map(function($item) { return $item['price'] * $item['quantity']; }, $cartItems))); ?></span>
                </div>
                
                <button class="place-order-btn" onclick="placeOrder()">
                    <i class="fas fa-check-circle"></i> สั่งซื้อสินค้า
                </button>
                
                <div style="margin-top: 1rem; font-size: 0.8rem; color: #999; text-align: center;">
                    <i class="fas fa-lock"></i> ข้อมูลของคุณปลอดภัยด้วยการเข้ารหัส SSL
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let selectedAddress = <?php echo $defaultAddress['id'] ?? 0; ?>;
        let selectedShipping = <?php echo $shippingOptions[0]['final_cost'] ?? 0; ?>;
        let selectedPayment = 'bank';
        let appliedCoupon = null;
        let discount = 0;
        
        // Select address
        function selectAddress(addressId) {
            selectedAddress = addressId;
            document.querySelectorAll('.address-option').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            event.currentTarget.querySelector('input[type="radio"]').checked = true;
            
            // รีโหลดค่าจัดส่งตามที่อยู่ใหม่
            location.reload();
        }
        
        // Select shipping
        function selectShipping(index) {
            const options = document.querySelectorAll('.shipping-option');
            options.forEach((el, i) => {
                if(i == index) {
                    el.classList.add('selected');
                    selectedShipping = parseFloat(el.querySelector('.shipping-price').innerText.replace(/[^0-9.-]+/g, ''));
                } else {
                    el.classList.remove('selected');
                }
            });
            updateTotal();
        }
        
        // Select payment
        function selectPayment(method) {
            selectedPayment = method;
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }
        
        // Apply coupon
        function applyCheckoutCoupon() {
            const coupon = document.getElementById('couponCode').value;
            if(!coupon) return;
            
            // เรียก API ตรวจสอบคูปอง
            fetch('checkout-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({action: 'validate_coupon', coupon: coupon})
            })
            .then(response => response.json())
            .then(data => {
                if(data.valid) {
                    appliedCoupon = coupon;
                    discount = data.discount;
                    document.getElementById('couponCode').value = '';
                    showNotification('ใช้คูปองสำเร็จ ส่วนลด ' + discount + ' บาท', 'success');
                    updateTotal();
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }
        
        function useCoupon(code) {
            document.getElementById('couponCode').value = code;
            applyCheckoutCoupon();
        }
        
        // Update total
        function updateTotal() {
            const subtotal = <?php echo array_sum(array_map(function($item) { return $item['price'] * $item['quantity']; }, $cartItems)); ?>;
            const shipping = selectedShipping;
            const total = subtotal + shipping - discount;
            
            document.getElementById('displayShipping').innerHTML = '฿' + shipping.toLocaleString();
            document.getElementById('displayDiscount').innerHTML = '-฿' + discount.toLocaleString();
            document.getElementById('displayTotal').innerHTML = '฿' + total.toLocaleString();
        }
        
        // Place order
        function placeOrder() {
            if(!selectedAddress) {
                showNotification('กรุณาเลือกที่อยู่จัดส่ง', 'error');
                return;
            }
            
            // สร้างฟอร์มส่งข้อมูล
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" name="address_id" value="${selectedAddress}">
                <input type="hidden" name="shipping_method" value="${selectedShipping}">
                <input type="hidden" name="payment_method" value="${selectedPayment}">
                <input type="hidden" name="coupon_code" value="${appliedCoupon || ''}">
                <input type="hidden" name="discount" value="${discount}">
                <input type="hidden" name="shipping_fee" value="${selectedShipping}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>