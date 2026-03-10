<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// ตรวจสอบว่ามี order_success ใน session หรือไม่
if (!isset($_SESSION['order_success'])) {
    redirect('index.php');
}

$order_id = $_SESSION['order_success'];
unset($_SESSION['order_success']);

// Get order details
$order_query = "SELECT o.*, u.fullname, u.email, u.phone, u.address 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = $order_id AND o.user_id = {$_SESSION['user_id']}";
$order_result = $conn->query($order_query);

if (!$order_result || $order_result->num_rows == 0) {
    redirect('index.php');
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.product_name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = $order_id";
$items = $conn->query($items_query);

// ใช้ new-header.php แทน header.php
include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>สั่งซื้อสำเร็จ!</h1>
        <p>ขอบคุณสำหรับการสั่งซื้อสินค้ากับเรา</p>
    </div>
</div>

<div class="container">
    <div style="max-width: 800px; margin: 0 auto;">
        <!-- Success Message -->
        <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md); text-align: center; margin-bottom: 2rem;">
            <div style="width: 80px; height: 80px; background: #28a745; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-check" style="font-size: 2.5rem; color: white;"></i>
            </div>
            <h2 style="color: #28a745; margin-bottom: 1rem;">สั่งซื้อสำเร็จ!</h2>
            <p style="color: var(--medium-gray); font-size: 1.1rem;">ขอบคุณสำหรับการสั่งซื้อสินค้ากับ ShopHub</p>
        </div>
        
        <!-- Order Details -->
        <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md); margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">📋 รายละเอียดออเดอร์ #<?php echo $order['id']; ?></h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">วันที่สั่งซื้อ:</p>
                    <p style="font-weight: 600;"><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                </div>
                <div>
                    <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">สถานะ:</p>
                    <span style="background: #ffc107; color: #000; padding: 0.25rem 1rem; border-radius: 20px; font-weight: 500;">
                        รอดำเนินการ
                    </span>
                </div>
                <div>
                    <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">ชื่อผู้รับ:</p>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($order['fullname'] ?: 'ไม่ได้ระบุ'); ?></p>
                </div>
                <div>
                    <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">เบอร์โทร:</p>
                    <p style="font-weight: 600;"><?php echo htmlspecialchars($order['phone'] ?: 'ไม่ได้ระบุ'); ?></p>
                </div>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">ที่อยู่จัดส่ง:</p>
                <p style="font-weight: 600; background: var(--light-gray); padding: 1rem; border-radius: 10px;">
                    <?php echo htmlspecialchars($order['shipping_address'] ?: $order['address'] ?: 'ไม่ได้ระบุ'); ?>
                </p>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">วิธีการชำระเงิน:</p>
                <?php
                $payment_icons = [
                    'bank_transfer' => '🏦',
                    'credit_card' => '💳',
                    'cod' => '💰',
                    'promptpay' => '📱',
                    'true_wallet' => '📱'
                ];
                $payment_names = [
                    'bank_transfer' => 'โอนเงินผ่านธนาคาร',
                    'credit_card' => 'บัตรเครดิต/เดบิต',
                    'cod' => 'เก็บเงินปลายทาง',
                    'promptpay' => 'พร้อมเพย์',
                    'true_wallet' => 'TrueWallet'
                ];
                $payment_method = $order['payment_method'] ?? 'bank_transfer';
                $payment_icon = $payment_icons[$payment_method] ?? '🏦';
                $payment_name = $payment_names[$payment_method] ?? $payment_method;
                ?>
                <p style="font-weight: 600;"><?php echo $payment_icon . ' ' . $payment_name; ?></p>
            </div>
            
            <!-- Order Items -->
            <h4 style="margin-bottom: 1rem;">รายการสินค้า</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--light-gray);">
                        <th style="padding: 1rem; text-align: left;">สินค้า</th>
                        <th style="padding: 1rem; text-align: center;">ราคา</th>
                        <th style="padding: 1rem; text-align: center;">จำนวน</th>
                        <th style="padding: 1rem; text-align: right;">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    while ($item = $items->fetch_assoc()): 
                        $item_total = $item['price'] * $item['quantity'];
                        $subtotal += $item_total;
                    ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if ($item['image'] && file_exists("assets/images/" . $item['image'])): ?>
                                        <img src="assets/images/<?php echo $item['image']; ?>" 
                                             alt="<?php echo $item['product_name']; ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                </div>
                            </td>
                            <td style="padding: 1rem; text-align: center;">฿<?php echo number_format($item['price'], 2); ?></td>
                            <td style="padding: 1rem; text-align: center;"><?php echo $item['quantity']; ?></td>
                            <td style="padding: 1rem; text-align: right;">฿<?php echo number_format($item_total, 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <?php 
                    $shipping = $subtotal >= 500 ? 0 : 50;
                    $tax = $subtotal * 0.07;
                    $grand_total = $subtotal + $shipping + $tax;
                    ?>
                    <tr>
                        <td colspan="3" style="padding: 1rem; text-align: right;"><strong>ราคาสินค้า:</strong></td>
                        <td style="padding: 1rem; text-align: right;">฿<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding: 0.5rem 1rem; text-align: right;"><strong>ค่าจัดส่ง:</strong></td>
                        <td style="padding: 0.5rem 1rem; text-align: right;">
                            <?php if ($shipping == 0): ?>
                                <span style="color: #28a745;">ฟรี</span>
                            <?php else: ?>
                                ฿<?php echo number_format($shipping, 2); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding: 0.5rem 1rem; text-align: right;"><strong>ภาษีมูลค่าเพิ่ม 7%:</strong></td>
                        <td style="padding: 0.5rem 1rem; text-align: right;">฿<?php echo number_format($tax, 2); ?></td>
                    </tr>
                    <tr style="border-top: 2px solid #ddd;">
                        <td colspan="3" style="padding: 1rem; text-align: right; font-size: 1.2rem;"><strong>ยอดรวมทั้งสิ้น:</strong></td>
                        <td style="padding: 1rem; text-align: right; font-size: 1.2rem; color: var(--primary-red); font-weight: 700;">
                            ฿<?php echo number_format($grand_total, 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="index.php" class="btn btn-primary" style="padding: 1rem 3rem;">
                <i class="fas fa-home"></i> กลับหน้าแรก
            </a>
            <a href="account.php" class="btn btn-secondary" style="padding: 1rem 3rem;">
                <i class="fas fa-user"></i> ดูประวัติการสั่งซื้อ
            </a>
            <a href="category.php" class="btn" style="padding: 1rem 3rem; background: var(--primary-blue); color: white;">
                <i class="fas fa-shopping-bag"></i> ช้อปปิ้งต่อ
            </a>
        </div>
        
        <!-- Print Receipt -->
        <div style="text-align: center; margin-top: 2rem;">
            <button onclick="window.print()" style="background: none; border: none; color: var(--primary-blue); cursor: pointer;">
                <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
            </button>
        </div>
    </div>
</div>

<?php 
// ใช้ new-footer.php แทน footer.php
include 'includes/new-footer.php'; 
?>