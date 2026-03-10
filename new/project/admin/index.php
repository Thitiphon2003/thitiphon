<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

if (!isset($conn) || $conn->connect_error) {
    die("Connection failed");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied");
}

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'] ?? 0;
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'")->fetch_assoc()['total'] ?? 0;

// Get recent orders
$recent_orders = $conn->query("SELECT o.*, u.username 
                                FROM orders o 
                                JOIN users u ON o.user_id = u.id 
                                ORDER BY o.order_date DESC 
                                LIMIT 5");

// Get current admin info
$admin = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h2>ShopHub</h2>
                <p>Admin Panel</p>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-title">เมนูหลัก</div>
                    <a href="index.php" class="menu-item active">
                        <i class="fas fa-home"></i>
                        <span>แดชบอร์ด</span>
                    </a>
                    <a href="users.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span>จัดการผู้ใช้</span>
                        <span class="badge"><?php echo $total_users; ?></span>
                    </a>
                    <a href="products.php" class="menu-item">
                        <i class="fas fa-box"></i>
                        <span>จัดการสินค้า</span>
                        <span class="badge"><?php echo $total_products; ?></span>
                    </a>
                    <a href="orders.php" class="menu-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>จัดการออเดอร์</span>
                        <span class="badge"><?php echo $total_orders; ?></span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">จัดการระบบ</div>
                    <a href="categories.php" class="menu-item">
                        <i class="fas fa-tags"></i>
                        <span>จัดการหมวดหมู่</span>
                    </a>
                    <a href="stores.php" class="menu-item">
                        <i class="fas fa-store"></i>
                        <span>จัดการร้านค้า</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">ระบบ</div>
                    <a href="../index.php" class="menu-item">
                        <i class="fas fa-globe"></i>
                        <span>กลับสู่หน้าร้าน</span>
                    </a>
                    <a href="../logout.php" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">แดชบอร์ด</h1>
                <div class="user-info">
                    <span>สวัสดี, <?php echo htmlspecialchars($admin['fullname'] ?: $admin['username']); ?></span>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-label">ผู้ใช้ทั้งหมด</div>
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                    <div class="stat-desc">เพิ่มขึ้น 12% จากเดือนที่แล้ว</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-label">สินค้าทั้งหมด</div>
                    <div class="stat-value"><?php echo number_format($total_products); ?></div>
                    <div class="stat-desc">เพิ่มขึ้น 8% จากเดือนที่แล้ว</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-label">ออเดอร์ทั้งหมด</div>
                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-desc">เพิ่มขึ้น 15% จากเดือนที่แล้ว</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-label">ยอดขายรวม</div>
                    <div class="stat-value">฿<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-desc">เพิ่มขึ้น 10% จากเดือนที่แล้ว</div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h3>ออเดอร์ล่าสุด</h3>
                    <a href="orders.php" class="btn btn-primary btn-sm">ดูทั้งหมด</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
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
                                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                            <td><strong>฿<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                switch($order['order_status']) {
                                                    case 'pending':
                                                        $status_class = 'badge-warning';
                                                        $status_text = 'รอดำเนินการ';
                                                        break;
                                                    case 'processing':
                                                        $status_class = 'badge-info';
                                                        $status_text = 'กำลังดำเนินการ';
                                                        break;
                                                    case 'shipped':
                                                        $status_class = 'badge-primary';
                                                        $status_text = 'จัดส่งแล้ว';
                                                        break;
                                                    case 'delivered':
                                                        $status_class = 'badge-success';
                                                        $status_text = 'ได้รับสินค้า';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'badge-danger';
                                                        $status_text = 'ยกเลิก';
                                                        break;
                                                    default:
                                                        $status_class = 'badge-secondary';
                                                        $status_text = $order['order_status'];
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 2rem;">
                                            <i class="fas fa-box-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                                            <p>ไม่มีข้อมูลออเดอร์</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <a href="products.php?action=add" class="stat-card" style="text-decoration: none; display: block;">
                    <div style="text-align: center;">
                        <i class="fas fa-plus-circle" style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
                        <h4>เพิ่มสินค้าใหม่</h4>
                        <p style="color: var(--secondary);">เพิ่มสินค้าลงในระบบ</p>
                    </div>
                </a>
                
                <a href="orders.php" class="stat-card" style="text-decoration: none; display: block;">
                    <div style="text-align: center;">
                        <i class="fas fa-truck" style="font-size: 2rem; color: var(--success); margin-bottom: 0.5rem;"></i>
                        <h4>จัดการออเดอร์</h4>
                        <p style="color: var(--secondary);">ตรวจสอบและอัปเดตสถานะ</p>
                    </div>
                </a>
                
                <a href="users.php" class="stat-card" style="text-decoration: none; display: block;">
                    <div style="text-align: center;">
                        <i class="fas fa-user-plus" style="font-size: 2rem; color: var(--warning); margin-bottom: 0.5rem;"></i>
                        <h4>ผู้ใช้ใหม่</h4>
                        <p style="color: var(--secondary);">ตรวจสอบผู้ใช้ล่าสุด</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Menu Toggle (optional) -->
    <script>
    // Toggle mobile menu
    document.addEventListener('DOMContentLoaded', function() {
        // Add mobile menu button if needed
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>