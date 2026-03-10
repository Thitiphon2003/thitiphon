<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id == 0) {
    redirect('orders.php');
}

// Get order details
$order_query = "SELECT o.*, u.fullname, u.email, u.phone, u.address 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = $order_id AND o.user_id = $user_id";
$order_result = $conn->query($order_query);

if ($order_result->num_rows == 0) {
    $_SESSION['error'] = "ไม่พบออเดอร์ที่ต้องการ";
    redirect('orders.php');
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.product_name, p.image, p.product_description 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = $order_id";
$items = $conn->query($items_query);

include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>รายละเอียดออเดอร์ #<?php echo $order_id; ?></h1>
        <p>ข้อมูลและสถานะการสั่งซื้อ</p>
    </div>
</div>

<div class="container">
    <div style="margin-bottom: 2rem;">
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายการออเดอร์
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Left Column - Order Items -->
        <div>
            <div style="background: white; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden;">
                <div style="background: var(--primary-blue); color: white; padding: 1rem 1.5rem;">
                    <h3>รายการสินค้า</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th style="padding: 0.75rem; text-align: left;">สินค้า</th>
                                <th style="padding: 0.75rem; text-align: center;">ราคา</th>
                                <th style="padding: 0.75rem; text-align: center;">จำนวน</th>
                                <th style="padding: 0.75rem; text-align: right;">รวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            while ($item = $items->fetch_assoc()): 
                                $item_total = $item['price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 1rem 0.75rem;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <?php if ($item['image'] && file_exists("assets/images/" . $item['image'])): ?>
                                                <img src="assets/images/<?php echo $item['image']; ?>" 
                                                     alt="<?php echo $item['product_name']; ?>" 
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo $item['product_name']; ?></div>
                                                <small style="color: var(--medium-gray);"><?php echo substr($item['product_description'], 0, 50); ?>...</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem 0.75rem; text-align: center;">฿<?php echo number_format($item['price'], 2); ?></td>
                                    <td style="padding: 1rem 0.75rem; text-align: center;"><?php echo $item['quantity']; ?></td>
                                    <td style="padding: 1rem 0.75rem; text-align: right; font-weight: 600;">฿<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column - Order Summary & Status -->
        <div>
            <!-- Order Status -->
            <div style="background: white; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 2rem;">
                <div style="background: var(--primary-blue); color: white; padding: 1rem 1.5rem;">
                    <h3>สถานะออเดอร์</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <?php
                    $status_steps = [
                        'pending' => 1,
                        'processing' => 2,
                        'shipped' => 3,
                        'delivered' => 4
                    ];
                    $current_step = $status_steps[$order['order_status']] ?? 0;
                    ?>
                    
                    <div style="position: relative; margin: 2rem 0;">
                        <!-- Progress Bar -->
                        <div style="height: 4px; background: var(--border); position: relative; margin: 2rem 0;">
                            <div style="height: 4px; background: var(--success); width: <?php echo ($current_step / 4) * 100; ?>%;"></div>
                        </div>
                        
                        <!-- Status Steps -->
                        <div style="display: flex; justify-content: space-between; margin-top: -1.5rem;">
                            <div style="text-align: center;">
                                <div style="width: 30px; height: 30px; background: <?php echo $current_step >= 1 ? 'var(--success)' : 'var(--border)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin: 0 auto 0.5rem;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <small>รอดำเนินการ</small>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 30px; height: 30px; background: <?php echo $current_step >= 2 ? 'var(--success)' : 'var(--border)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin: 0 auto 0.5rem;">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <small>กำลังดำเนินการ</small>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 30px; height: 30px; background: <?php echo $current_step >= 3 ? 'var(--success)' : 'var(--border)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin: 0 auto 0.5rem;">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <small>จัดส่งแล้ว</small>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 30px; height: 30px; background: <?php echo $current_step >= 4 ? 'var(--success)' : 'var(--border)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; margin: 0 auto 0.5rem;">
                                    <i class="fas fa-home"></i>
                                </div>
                                <small>ได้รับสินค้า</small>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($order['order_status'] == 'cancelled'): ?>
                        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; text-align: center;">
                            <i class="fas fa-times-circle"></i> ออเดอร์นี้ถูกยกเลิกแล้ว
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div style="background: white; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden;">
                <div style="background: var(--primary-blue); color: white; padding: 1rem 1.5rem;">
                    <h3>สรุปคำสั่งซื้อ</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <?php
                    $shipping = $subtotal >= 500 ? 0 : 50;
                    $tax = $subtotal * 0.07;
                    $grand_total = $subtotal + $shipping + $tax;
                    ?>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span>ราคาสินค้า:</span>
                        <span>฿<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span>ค่าจัดส่ง:</span>
                        <span><?php echo $shipping == 0 ? 'ฟรี' : '฿'.number_format($shipping, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span>ภาษีมูลค่าเพิ่ม 7%:</span>
                        <span>฿<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div style="border-top: 2px dashed var(--border); margin: 1rem 0; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700;">
                            <span>ยอดรวมทั้งสิ้น:</span>
                            <span style="color: var(--primary-red);">฿<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div style="background: white; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; margin-top: 2rem;">
                <div style="background: var(--primary-blue); color: white; padding: 1rem 1.5rem;">
                    <h3>ข้อมูลการจัดส่ง</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <p><strong>ชื่อผู้รับ:</strong> <?php echo $order['fullname']; ?></p>
                    <p><strong>เบอร์โทร:</strong> <?php echo $order['phone']; ?></p>
                    <p><strong>ที่อยู่จัดส่ง:</strong> <?php echo $order['shipping_address']; ?></p>
                    <p><strong>วิธีการชำระเงิน:</strong> <?php echo $order['payment_method']; ?></p>
                    <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/new-footer.php'; ?>