<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_SESSION['order_success'])) {
    redirect('index.php');
}

$order_id = $_SESSION['order_success'];
unset($_SESSION['order_success']);

// Get order details
$order_query = "SELECT o.*, u.fullname, u.email, u.phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = $order_id AND o.user_id = {$_SESSION['user_id']}";
$order_result = $conn->query($order_query);

if ($order_result->num_rows == 0) {
    redirect('index.php');
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.product_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = $order_id";
$items = $conn->query($items_query);

include 'includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: var(--shadow); text-align: center;">
        <div style="font-size: 5rem; color: #28a745; margin-bottom: 1rem;">✓</div>
        <h1 style="color: #28a745; margin-bottom: 1rem;">สั่งซื้อสำเร็จ!</h1>
        <p style="font-size: 1.2rem; margin-bottom: 2rem;">ขอบคุณสำหรับการสั่งซื้อสินค้ากับเรา</p>
        
        <div style="background: var(--light-gray); padding: 1.5rem; border-radius: 10px; text-align: left; margin-bottom: 2rem;">
            <h3>รายละเอียดออเดอร์ #<?php echo $order['id']; ?></h3>
            <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
            <p><strong>ชื่อผู้รับ:</strong> <?php echo $order['fullname']; ?></p>
            <p><strong>ที่อยู่จัดส่ง:</strong> <?php echo $order['shipping_address']; ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo $order['phone']; ?></p>
            <p><strong>วิธีการชำระเงิน:</strong> <?php echo $order['payment_method']; ?></p>
            
            <h4 style="margin-top: 1rem;">รายการสินค้า</h4>
            <table style="width: 100%; margin-top: 1rem;">
                <thead>
                    <tr>
                        <th style="text-align: left;">สินค้า</th>
                        <th style="text-align: right;">ราคา</th>
                        <th style="text-align: right;">จำนวน</th>
                        <th style="text-align: right;">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td style="text-align: right;">฿<?php echo number_format($item['price'], 2); ?></td>
                            <td style="text-align: right;"><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>ยอดรวมทั้งสิ้น:</strong></td>
                        <td style="text-align: right;"><strong>฿<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="index.php" class="btn">กลับสู่หน้าแรก</a>
            <a href="notifications.php" class="btn btn-red">ติดตามออเดอร์</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>