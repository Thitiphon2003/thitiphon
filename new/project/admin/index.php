<?php
// เปิด error display (ใส่ไว้ก่อนทุกอย่าง)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เปลี่ยน path ให้ถูกต้อง - ใช้ __DIR__ เพื่อหาตำแหน่งที่แน่นอน
require_once __DIR__ . '/../includes/config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn) || $conn->connect_error) {
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . ($conn->connect_error ?? 'unknown error'));
}

// ตรวจสอบ session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่า login หรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// ตรวจสอบว่าเป็น admin หรือไม่
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// Get statistics with error handling
$total_users = 0;
$total_products = 0;
$total_orders = 0;
$total_revenue = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $total_users = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result) {
    $total_products = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result) {
    $total_orders = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'");
if ($result) {
    $total_revenue = $result->fetch_assoc()['total'] ?? 0;
}

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
    <title>Admin Dashboard - ShopHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 260px;
            background: #1a2639;
            color: white;
            padding: 2rem 0;
        }
        .admin-sidebar h2 {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            color: #4f9da6;
        }
        .admin-sidebar a {
            display: block;
            padding: 0.8rem 1.5rem;
            color: #ddd;
            text-decoration: none;
            transition: 0.3s;
            margin: 0.2rem 0;
        }
        .admin-sidebar a:hover {
            background: #2d3748;
            color: white;
            border-left: 3px solid #4f9da6;
        }
        .admin-content {
            flex: 1;
            padding: 2rem;
            background: #f0f2f5;
        }
        .admin-content h1 {
            margin-bottom: 2rem;
            color: #1a2639;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: white;
            padding: 1.8rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4f9da6;
        }
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .stat-card .number {
            font-size: 2.2rem;
            font-weight: bold;
            color: #1a2639;
        }
        .stat-card .number small {
            font-size: 0.9rem;
            color: #666;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-container h2 {
            margin-bottom: 1.5rem;
            color: #1a2639;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: #1a2639;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        table tr:hover {
            background: #f5f5f5;
        }
        .status-badge {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-processing { background: #17a2b8; color: white; }
        .status-shipped { background: #007bff; color: white; }
        .status-delivered { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .btn {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: #4f9da6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #3d7a81;
        }
        .btn-red {
            background: #dc3545;
        }
        .btn-red:hover {
            background: #b02a37;
        }
        .logout-btn {
            margin-top: 2rem;
            padding: 0 1.5rem;
        }
        .logout-btn a {
            background: #dc3545;
            text-align: center;
            border-radius: 5px;
        }
        .logout-btn a:hover {
            background: #b02a37;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>ShopHub Admin</h2>
            <a href="index.php" style="background: #2d3748; color: white; border-left: 3px solid #4f9da6;">📊 แดชบอร์ด</a>
            <a href="users.php">👥 จัดการผู้ใช้</a>
            <a href="products.php">📦 จัดการสินค้า</a>
            <a href="orders.php">📋 จัดการออเดอร์</a>
            <a href="categories.php">📑 จัดการหมวดหมู่</a>
            <a href="stores.php">🏪 จัดการร้านค้า</a>
            <div class="logout-btn">
                <a href="../logout.php">🚪 ออกจากระบบ</a>
            </div>
        </div>
        
        <div class="admin-content">
            <h1>แดชบอร์ด</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>👥 ผู้ใช้ทั้งหมด</h3>
                    <div class="number"><?php echo number_format($total_users); ?></div>
                </div>
                <div class="stat-card">
                    <h3>📦 สินค้าทั้งหมด</h3>
                    <div class="number"><?php echo number_format($total_products); ?></div>
                </div>
                <div class="stat-card">
                    <h3>📋 ออเดอร์ทั้งหมด</h3>
                    <div class="number"><?php echo number_format($total_orders); ?></div>
                </div>
                <div class="stat-card">
                    <h3>💰 ยอดขายรวม</h3>
                    <div class="number">฿<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>
            
            <div class="table-container">
                <h2>ออเดอร์ล่าสุด</h2>
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
                                                $status_class = 'status-pending';
                                                $status_text = 'รอดำเนินการ';
                                                break;
                                            case 'processing':
                                                $status_class = 'status-processing';
                                                $status_text = 'กำลังดำเนินการ';
                                                break;
                                            case 'shipped':
                                                $status_class = 'status-shipped';
                                                $status_text = 'จัดส่งแล้ว';
                                                break;
                                            case 'delivered':
                                                $status_class = 'status-delivered';
                                                $status_text = 'ได้รับสินค้า';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'status-cancelled';
                                                $status_text = 'ยกเลิก';
                                                break;
                                            default:
                                                $status_class = '';
                                                $status_text = $order['order_status'];
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn">ดูรายละเอียด</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">
                                    <p style="color: #666;">ไม่พบข้อมูลออเดอร์</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>