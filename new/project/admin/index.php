<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../login.php');
}

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'")->fetch_assoc()['total'];

// Get recent orders
$recent_orders = $conn->query("SELECT o.*, u.username 
                                FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                ORDER BY o.order_date DESC 
                                LIMIT 5");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2 style="padding: 0 1.5rem; margin-bottom: 2rem;">Admin Panel</h2>
            <a href="index.php">แดชบอร์ด</a>
            <a href="users.php">จัดการผู้ใช้</a>
            <a href="products.php">จัดการสินค้า</a>
            <a href="orders.php">จัดการออเดอร์</a>
            <a href="categories.php">จัดการหมวดหมู่</a>
            <a href="stores.php">จัดการร้านค้า</a>
            <a href="../logout.php">ออกจากระบบ</a>
        </div>
        
        <div class="admin-content">
            <h1>แดชบอร์ด</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>ผู้ใช้ทั้งหมด</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <h3>สินค้าทั้งหมด</h3>
                    <div class="number"><?php echo $total_products; ?></div>
                </div>
                <div class="stat-card">
                    <h3>ออเดอร์ทั้งหมด</h3>
                    <div class="number"><?php echo $total_orders; ?></div>
                </div>
                <div class="stat-card">
                    <h3>ยอดขายรวม</h3>
                    <div class="number">฿<?php echo number_format($total_revenue ?? 0, 2); ?></div>
                </div>
            </div>
            
            <h2>ออเดอร์ล่าสุด</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>ผู้ใช้</th>
                        <th>วันที่</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['username']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                            <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span style="padding: 0.25rem 0.5rem; background: 
                                    <?php 
                                    switch($order['order_status']) {
                                        case 'pending': echo '#ffc107'; break;
                                        case 'processing': echo '#17a2b8'; break;
                                        case 'shipped': echo '#007bff'; break;
                                        case 'delivered': echo '#28a745'; break;
                                        case 'cancelled': echo '#dc3545'; break;
                                    }
                                    ?>; color: white; border-radius: 3px;">
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
                            <td>
                                <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn">ดู</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>