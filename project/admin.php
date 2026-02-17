<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบแอดมิน
if(!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// ตรวจสอบว่ายังเป็นแอดมินอยู่หรือไม่
$admin = fetchOne("SELECT * FROM users WHERE id = ? AND is_admin = 1 AND status = 'active'", [$_SESSION['admin_id']]);

if(!$admin) {
    session_destroy();
    header('Location: admin_login.php?error=unauthorized');
    exit();
}

// ดึงข้อมูลสถิติ
$total_users = fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$total_products = fetchOne("SELECT COUNT(*) as count FROM products")['count'] ?? 0;
$total_orders = fetchOne("SELECT COUNT(*) as count FROM orders")['count'] ?? 0;
$total_revenue = fetchOne("SELECT SUM(total) as sum FROM orders WHERE order_status IN ('delivered', 'shipping')")['sum'] ?? 0;

// ออเดอร์ล่าสุด
$recent_orders = fetchAll("SELECT o.*, u.username, u.firstname, u.lastname 
                           FROM orders o 
                           JOIN users u ON o.user_id = u.id 
                           ORDER BY o.created_at DESC 
                           LIMIT 5") ?? [];

// ผู้ใช้ล่าสุด
$recent_users = fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5") ?? [];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SHOP.COM</title>
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
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 0.3rem;
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
        
        .admin-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .admin-role {
            font-size: 0.8rem;
            opacity: 0.7;
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
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.2);
            color: white;
            border-left: 4px solid #ffd700;
        }
        
        .nav-item i {
            width: 20px;
        }
        
        /* Main Content */
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title h1 {
            font-size: 1.5rem;
            color: #333;
        }
        
        .logout-btn {
            padding: 0.5rem 1.5rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-info h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .stat-info .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
        }
        
        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-card h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        /* Tables */
        .table-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .table-header h3 {
            color: #333;
        }
        
        .view-all {
            color: #667eea;
            text-decoration: none;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-shipping {
            background: #cce5ff;
            color: #004085;
        }
        
        .action-btn {
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            margin: 0 0.2rem;
        }
        
        .btn-view {
            background: #28a745;
            color: white;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .charts-row {
                grid-template-columns: 1fr;
            }
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
                <div class="admin-role">Administrator</div>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>แดชบอร์ด</span>
                </div>
                <div class="nav-item" onclick="window.location.href='admin_users.php'">
                    <i class="fas fa-users"></i>
                    <span>จัดการผู้ใช้</span>
                </div>
                <div class="nav-item" onclick="window.location.href='admin_products.php'">
                    <i class="fas fa-box"></i>
                    <span>จัดการสินค้า</span>
                </div>
                <div class="nav-item" onclick="window.location.href='admin_orders.php'">
                    <i class="fas fa-shopping-cart"></i>
                    <span>จัดการออเดอร์</span>
                </div>
                <div class="nav-item" onclick="window.location.href='admin_categories.php'">
                    <i class="fas fa-tags"></i>
                    <span>จัดการหมวดหมู่</span>
                </div>
                <div class="nav-item" onclick="window.location.href='admin_sellers.php'">
                    <i class="fas fa-store"></i>
                    <span>จัดการร้านค้า</span>
                </div>
                <div class="nav-item" onclick="window.location.href='admin_settings.php'">
                    <i class="fas fa-cog"></i>
                    <span>ตั้งค่า</span>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>แดชบอร์ด</h1>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
            
            <!-- Dashboard Content -->
            <div id="dashboard">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>ผู้ใช้ทั้งหมด</h3>
                            <div class="value"><?php echo number_format($total_users); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3>สินค้าทั้งหมด</h3>
                            <div class="value"><?php echo number_format($total_products); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>ออเดอร์ทั้งหมด</h3>
                            <div class="value"><?php echo number_format($total_orders); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>ยอดขายรวม</h3>
                            <div class="value">฿<?php echo number_format($total_revenue); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>ออเดอร์ล่าสุด</h3>
                        <a href="admin_orders.php" class="view-all">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>เลขที่ออเดอร์</th>
                                <th>ลูกค้า</th>
                                <th>วันที่</th>
                                <th>ยอดรวม</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_orders)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">ไม่มีข้อมูลออเดอร์</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_number']; ?></td>
                                    <td><?php echo $order['firstname'] . ' ' . $order['lastname']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>฿<?php echo number_format($order['total']); ?></td>
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
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Recent Users -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>ผู้ใช้ล่าสุด</h3>
                        <a href="admin_users.php" class="view-all">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ชื่อผู้ใช้</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>อีเมล</th>
                                <th>ระดับ</th>
                                <th>วันที่สมัคร</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_users)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">ไม่มีข้อมูลผู้ใช้</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['level']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSection(section) {
            // ฟังก์ชันสำหรับสลับหน้า
            alert('กำลังพัฒนา: ' + section);
        }
    </script>
</body>
</html>