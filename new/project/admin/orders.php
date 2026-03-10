<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['order_status']);
    
    $query = "UPDATE orders SET order_status = '$status' WHERE id = $order_id";
    $conn->query($query);
    
    // Create notification for user
    $order = $conn->query("SELECT user_id FROM orders WHERE id = $order_id")->fetch_assoc();
    $status_text = [
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังดำเนินการ',
        'shipped' => 'จัดส่งแล้ว',
        'delivered' => 'ได้รับสินค้าแล้ว',
        'cancelled' => 'ยกเลิก'
    ];
    
    $notify = "INSERT INTO notifications (user_id, title, message, type) 
               VALUES ({$order['user_id']}, 'อัปเดตสถานะออเดอร์', 
               'ออเดอร์ #$order_id เปลี่ยนสถานะเป็น {$status_text[$status]}', 'order')";
    $conn->query($notify);
    
    $success = "อัปเดตสถานะออเดอร์สำเร็จ";
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query
$query = "SELECT o.*, u.username, u.fullname, u.email, u.phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

if ($status_filter) {
    $query .= " AND o.order_status = '$status_filter'";
}
if ($date_from) {
    $query .= " AND DATE(o.order_date) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(o.order_date) <= '$date_to'";
}

$query .= " ORDER BY o.order_date DESC";
$orders = $conn->query($query);

// Get order details if viewing specific order
$order_details = null;
$order_items = null;
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    $order_details = $conn->query("SELECT o.*, u.username, u.fullname, u.email, u.phone, u.address 
                                   FROM orders o 
                                   JOIN users u ON o.user_id = u.id 
                                   WHERE o.id = $order_id")->fetch_assoc();
    
    $order_items = $conn->query("SELECT oi.*, p.product_name, p.image 
                                 FROM order_items oi 
                                 JOIN products p ON oi.product_id = p.id 
                                 WHERE oi.order_id = $order_id");
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการออเดอร์ - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            color: white;
            font-size: 0.8rem;
            display: inline-block;
        }
        .status-pending { background: #ffc107; }
        .status-processing { background: #17a2b8; }
        .status-shipped { background: #007bff; }
        .status-delivered { background: #28a745; }
        .status-cancelled { background: #dc3545; }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .filter-form {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2 style="padding: 0 1.5rem; margin-bottom: 2rem;">Admin Panel</h2>
            <a href="index.php">แดชบอร์ด</a>
            <a href="users.php">จัดการผู้ใช้</a>
            <a href="products.php">จัดการสินค้า</a>
            <a href="orders.php" style="background: var(--primary-blue);">จัดการออเดอร์</a>
            <a href="categories.php">จัดการหมวดหมู่</a>
            <a href="stores.php">จัดการร้านค้า</a>
            <a href="../logout.php">ออกจากระบบ</a>
        </div>
        
        <div class="admin-content">
            <h1>จัดการออเดอร์</h1>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($order_details): ?>
                <!-- Order Details View -->
                <a href="orders.php" class="btn" style="margin-bottom: 1rem;">← กลับไปรายการออเดอร์</a>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <div>
                        <div style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 1rem;">
                            <h3>รายละเอียดออเดอร์ #<?php echo $order_details['id']; ?></h3>
                            <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i', strtotime($order_details['order_date'])); ?></p>
                            <p><strong>สถานะ:</strong> 
                                <span class="status-badge status-<?php echo $order_details['order_status']; ?>">
                                    <?php 
                                    switch($order_details['order_status']) {
                                        case 'pending': echo 'รอดำเนินการ'; break;
                                        case 'processing': echo 'กำลังดำเนินการ'; break;
                                        case 'shipped': echo 'จัดส่งแล้ว'; break;
                                        case 'delivered': echo 'ได้รับสินค้า'; break;
                                        case 'cancelled': echo 'ยกเลิก'; break;
                                    }
                                    ?>
                                </span>
                            </p>
                            <p><strong>วิธีการชำระเงิน:</strong> <?php echo $order_details['payment_method']; ?></p>
                            <p><strong>ยอดรวม:</strong> ฿<?php echo number_format($order_details['total_amount'], 2); ?></p>
                        </div>
                        
                        <div style="background: white; padding: 1.5rem; border-radius: 10px;">
                            <h3>รายการสินค้า</h3>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>สินค้า</th>
                                        <th>ราคา</th>
                                        <th>จำนวน</th>
                                        <th>รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $order_items->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $item['product_name']; ?></td>
                                            <td>฿<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div>
                        <div style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 1rem;">
                            <h3>ข้อมูลลูกค้า</h3>
                            <p><strong>ชื่อผู้ใช้:</strong> <?php echo $order_details['username']; ?></p>
                            <p><strong>ชื่อ-นามสกุล:</strong> <?php echo $order_details['fullname']; ?></p>
                            <p><strong>อีเมล:</strong> <?php echo $order_details['email']; ?></p>
                            <p><strong>เบอร์โทร:</strong> <?php echo $order_details['phone']; ?></p>
                            <p><strong>ที่อยู่จัดส่ง:</strong> <?php echo $order_details['shipping_address']; ?></p>
                        </div>
                        
                        <div style="background: white; padding: 1.5rem; border-radius: 10px;">
                            <h3>อัปเดตสถานะ</h3>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                                <div class="form-group">
                                    <select name="order_status" class="form-control">
                                        <option value="pending" <?php echo $order_details['order_status'] == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                        <option value="processing" <?php echo $order_details['order_status'] == 'processing' ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                                        <option value="shipped" <?php echo $order_details['order_status'] == 'shipped' ? 'selected' : ''; ?>>จัดส่งแล้ว</option>
                                        <option value="delivered" <?php echo $order_details['order_status'] == 'delivered' ? 'selected' : ''; ?>>ได้รับสินค้า</option>
                                        <option value="cancelled" <?php echo $order_details['order_status'] == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn">อัปเดตสถานะ</button>
                            </form>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Orders List View -->
                
                <!-- Filter Form -->
                <div class="filter-form">
                    <form method="GET" style="display: contents;">
                        <div class="form-group">
                            <label>สถานะ</label>
                            <select name="status" class="form-control">
                                <option value="">ทั้งหมด</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                                <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>จัดส่งแล้ว</option>
                                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>ได้รับสินค้า</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>วันที่เริ่มต้น</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>วันที่สิ้นสุด</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">กรอง</button>
                            <a href="orders.php" class="btn btn-red">ล้าง</a>
                        </div>
                    </form>
                </div>
                
                <!-- Orders Table -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>ลูกค้า</th>
                            <th>วันที่</th>
                            <th>ยอดรวม</th>
                            <th>สถานะ</th>
                            <th>การชำระเงิน</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <?php echo $order['fullname'] ?: $order['username']; ?><br>
                                    <small><?php echo $order['email']; ?></small>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
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
                                </td>
                                <td><?php echo $order['payment_method']; ?></td>
                                <td>
                                    <a href="?view=<?php echo $order['id']; ?>" class="btn">ดูรายละเอียด</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>