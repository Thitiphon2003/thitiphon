<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบแอดมิน
if(!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin = fetchOne("SELECT * FROM users WHERE id = ? AND is_admin = 1", [$_SESSION['admin_id']]);
if(!$admin) {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

// อัปเดตสถานะออเดอร์
if(isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    query("UPDATE orders SET order_status = ? WHERE id = ?", [$status, $order_id]);
    $_SESSION['success'] = 'อัปเดตสถานะเรียบร้อย';
    header('Location: admin_orders.php');
    exit();
}

// ดึงข้อมูลออเดอร์ทั้งหมด
$orders = fetchAll("SELECT o.*, u.username, u.firstname, u.lastname 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    ORDER BY o.created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการออเดอร์ - SHOP.COM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f4f6f9;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            position: fixed;
            height: 100vh;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .admin-info {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .admin-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-item {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.2);
            border-left: 4px solid #ffd700;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logout-btn {
            padding: 0.5rem 1.5rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            background: #f8f9fa;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-shipping {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        select {
            padding: 5px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
        }
        
        .btn-view {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SHOP.COM</h2>
                <p>Admin Panel</p>
            </div>
            
            <div class="admin-info">
                <div class="admin-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="admin-name"><?php echo $admin['firstname'] . ' ' . $admin['lastname']; ?></div>
            </div>
            
            <div class="nav-menu">
                <a href="admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
                <a href="admin_users.php" class="nav-item"><i class="fas fa-users"></i> จัดการผู้ใช้</a>
                <a href="admin_products.php" class="nav-item"><i class="fas fa-box"></i> จัดการสินค้า</a>
                <a href="admin_orders.php" class="nav-item active"><i class="fas fa-shopping-cart"></i> จัดการออเดอร์</a>
                <a href="admin_categories.php" class="nav-item"><i class="fas fa-tags"></i> จัดการหมวดหมู่</a>
                <a href="admin_sellers.php" class="nav-item"><i class="fas fa-store"></i> จัดการร้านค้า</a>
                <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i> ตั้งค่า</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-shopping-cart"></i> จัดการออเดอร์</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <table>
                    <thead>
                        <tr>
                            <th>เลขที่ออเดอร์</th>
                            <th>ลูกค้า</th>
                            <th>วันที่</th>
                            <th>ยอดรวม</th>
                            <th>ชำระเงิน</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo $order['firstname'] . ' ' . $order['lastname']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>฿<?php echo number_format($order['total']); ?></td>
                            <td><?php echo $order['payment_method'] ?? '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php 
                                        $status_map = [
                                            'pending' => 'รอดำเนินการ',
                                            'processing' => 'กำลังดำเนินการ',
                                            'shipping' => 'กำลังจัดส่ง',
                                            'delivered' => 'จัดส่งแล้ว',
                                            'cancelled' => 'ยกเลิก'
                                        ];
                                        echo $status_map[$order['order_status']] ?? $order['order_status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order['order_status']=='pending'?'selected':''; ?>>รอดำเนินการ</option>
                                        <option value="processing" <?php echo $order['order_status']=='processing'?'selected':''; ?>>กำลังดำเนินการ</option>
                                        <option value="shipping" <?php echo $order['order_status']=='shipping'?'selected':''; ?>>กำลังจัดส่ง</option>
                                        <option value="delivered" <?php echo $order['order_status']=='delivered'?'selected':''; ?>>จัดส่งแล้ว</option>
                                        <option value="cancelled" <?php echo $order['order_status']=='cancelled'?'selected':''; ?>>ยกเลิก</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <button class="btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function viewOrder(id) {
            window.location.href = 'admin_order_detail.php?id=' + id;
        }
    </script>
</body>
</html>