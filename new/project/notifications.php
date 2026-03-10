<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'notifications.php';
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Mark notification as read
if (isset($_GET['read'])) {
    $notify_id = (int)$_GET['read'];
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE id = $notify_id AND user_id = $user_id");
    redirect('notifications.php');
}

// Mark all as read
if (isset($_GET['read_all'])) {
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE user_id = $user_id");
    redirect('notifications.php');
}

// Delete notification
if (isset($_GET['delete'])) {
    $notify_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM notifications WHERE id = $notify_id AND user_id = $user_id");
    redirect('notifications.php');
}

// Get user's orders for order tracking
$orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC LIMIT 5");

// Get notifications
$notifications = $conn->query("SELECT * FROM notifications 
                              WHERE user_id = $user_id 
                              ORDER BY created_at DESC");

$unread_count = $conn->query("SELECT COUNT(*) as count FROM notifications 
                              WHERE user_id = $user_id AND is_read = FALSE")->fetch_assoc()['count'];

include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>การแจ้งเตือน</h1>
        <p>ติดตามสถานะออเดอร์และโปรโมชั่นล่าสุด</p>
    </div>
</div>

<div class="container">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Left Column - Notifications -->
        <div>
            <!-- Notification Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem;">การแจ้งเตือนทั้งหมด</h2>
                <?php if ($unread_count > 0): ?>
                    <a href="?read_all=1" class="btn btn-primary">
                        <i class="fas fa-check-double"></i> อ่านทั้งหมด (<?php echo $unread_count; ?>)
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Notifications List -->
            <?php if ($notifications->num_rows == 0): ?>
                <div style="background: white; padding: 3rem; text-align: center; border-radius: 20px; box-shadow: var(--shadow-md);">
                    <i class="far fa-bell" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                    <h3 style="margin-bottom: 0.5rem;">ไม่มีการแจ้งเตือน</h3>
                    <p style="color: var(--medium-gray);">คุณจะได้รับการแจ้งเตือนเมื่อมีกิจกรรมต่างๆ</p>
                </div>
            <?php else: ?>
                <?php while ($notify = $notifications->fetch_assoc()): ?>
                    <div class="notification notification-<?php echo $notify['type']; ?>" 
                         style="<?php echo !$notify['is_read'] ? 'border-left-width: 6px; background: linear-gradient(to right, #f0f7ff, white);' : ''; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <?php if ($notify['type'] == 'promotion'): ?>
                                        <span style="background: var(--primary-blue); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">โปรโมชั่น</span>
                                    <?php elseif ($notify['type'] == 'order'): ?>
                                        <span style="background: var(--primary-red); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">ออเดอร์</span>
                                    <?php else: ?>
                                        <span style="background: var(--dark-gray); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">ระบบ</span>
                                    <?php endif; ?>
                                    <?php if (!$notify['is_read']): ?>
                                        <span style="background: var(--primary-blue); color: white; padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.7rem;">ใหม่</span>
                                    <?php endif; ?>
                                </div>
                                <h3 style="margin-bottom: 0.5rem;"><?php echo $notify['title']; ?></h3>
                                <p style="margin-bottom: 0.5rem; color: var(--dark-gray);"><?php echo $notify['message']; ?></p>
                                <small style="color: var(--medium-gray);">
                                    <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notify['created_at'])); ?>
                                </small>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if (!$notify['is_read']): ?>
                                    <a href="?read=<?php echo $notify['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;" title="ทำเครื่องหมายว่าอ่านแล้ว">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $notify['id']; ?>" class="btn btn-red" style="padding: 0.5rem 1rem;" 
                                   onclick="return confirm('ลบการแจ้งเตือนนี้?')" title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Order Tracking & Promotions -->
        <div>
            <!-- Order Tracking -->
            <div style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-truck" style="color: var(--primary-blue);"></i>
                    ติดตามออเดอร์ล่าสุด
                </h3>
                
                <?php if ($orders->num_rows == 0): ?>
                    <div style="text-align: center; padding: 1rem;">
                        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                        <p style="color: var(--medium-gray);">ยังไม่มีประวัติการสั่งซื้อ</p>
                    </div>
                <?php else: ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div style="padding: 1rem; border: 1px solid var(--light-gray); border-radius: 10px; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <strong>ออเดอร์ #<?php echo $order['id']; ?></strong>
                                <span style="background: 
                                    <?php 
                                    switch($order['order_status']) {
                                        case 'pending': echo '#ffc107'; break;
                                        case 'processing': echo '#17a2b8'; break;
                                        case 'shipped': echo '#007bff'; break;
                                        case 'delivered': echo '#28a745'; break;
                                        case 'cancelled': echo '#dc3545'; break;
                                    }
                                    ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">
                                    <?php 
                                    switch($order['order_status']) {
                                        case 'pending': echo 'รอดำเนินการ'; break;
                                        case 'processing': echo 'กำลังดำเนินการ'; break;
                                        case 'shipped': echo 'จัดส่งแล้ว'; break;
                                        case 'delivered': echo 'ได้รับสินค้า'; break;
                                        case 'cancelled': echo 'ยกเลิก'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <p style="font-size: 0.9rem; color: var(--medium-gray); margin-bottom: 0.25rem;">
                                <i class="far fa-calendar"></i> วันที่: <?php echo date('d/m/Y', strtotime($order['order_date'])); ?>
                            </p>
                            <p style="font-size: 0.9rem; color: var(--medium-gray);">
                                <i class="fas fa-tag"></i> ยอดรวม: <strong style="color: var(--primary-red);">฿<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </p>
                        </div>
                    <?php endwhile; ?>
                    
                    <a href="orders.php" style="display: block; text-align: center; color: var(--primary-blue); text-decoration: none; font-weight: 600;">
                        ดูออเดอร์ทั้งหมด <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Promotions -->
            <div style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: var(--shadow-md);">
                <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-tags" style="color: var(--primary-red);"></i>
                    โปรโมชั่นพิเศษ
                </h3>
                
                <div class="notification notification-promotion" style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: var(--primary-blue); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            🎉
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.25rem;">โปรโมชั่นประจำเดือน</h4>
                            <p style="font-size: 0.9rem; color: var(--medium-gray);">ลดสูงสุด 50% สำหรับสินค้าในหมวดเครื่องใช้ไฟฟ้า</p>
                            <small style="color: var(--primary-red);">ถึง 31 ธันวาคม 2024</small>
                        </div>
                    </div>
                </div>
                
                <div class="notification notification-promotion" style="margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: var(--primary-red); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            🆕
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.25rem;">สมาชิกใหม่</h4>
                            <p style="font-size: 0.9rem; color: var(--medium-gray);">รับส่วนลด 100 บาท สำหรับการสั่งซื้อครั้งแรก</p>
                            <small style="color: var(--primary-red);">เฉพาะสมาชิกใหม่</small>
                        </div>
                    </div>
                </div>
                
                <div class="notification notification-promotion">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: var(--dark-gray); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            🚚
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.25rem;">จัดส่งฟรี</h4>
                            <p style="font-size: 0.9rem; color: var(--medium-gray);">เมื่อซื้อครบ 500 บาท ทั่วประเทศ</p>
                            <small style="color: var(--primary-red);">ไม่จำกัดจำนวนครั้ง</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/new-footer.php'; ?>