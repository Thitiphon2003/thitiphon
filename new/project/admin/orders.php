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

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['order_status']);
    
    if ($conn->query("UPDATE orders SET order_status = '$status' WHERE id = $order_id")) {
        $_SESSION['success'] = "อัปเดตสถานะออเดอร์สำเร็จ";
        
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
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
    }
    header("Location: orders.php");
    exit();
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

// Get admin info
$admin = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการออเดอร์ - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
    <style>
        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            text-align: center;
        }
        .summary-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        .summary-card .label {
            color: var(--secondary);
            font-size: 0.875rem;
        }
        .filter-form {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
        }
        .order-product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 0.5rem;
        }
    </style>
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
                    <a href="index.php" class="menu-item">
                        <i class="fas fa-home"></i>
                        <span>แดชบอร์ด</span>
                    </a>
                    <a href="users.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span>จัดการผู้ใช้</span>
                    </a>
                    <a href="products.php" class="menu-item">
                        <i class="fas fa-box"></i>
                        <span>จัดการสินค้า</span>
                    </a>
                    <a href="orders.php" class="menu-item active">
                        <i class="fas fa-shopping-cart"></i>
                        <span>จัดการออเดอร์</span>
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
                <h1 class="page-title">จัดการออเดอร์</h1>
                <div class="user-info">
                    <span>สวัสดี, <?php echo htmlspecialchars($admin['fullname'] ?: $admin['username']); ?></span>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($order_details): ?>
                <!-- Order Details View -->
                <a href="orders.php" class="btn btn-primary" style="margin-bottom: 1rem;">
                    <i class="fas fa-arrow-left"></i> กลับไปรายการออเดอร์
                </a>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- Left Column -->
                    <div>
                        <!-- Order Info -->
                        <div class="card">
                            <div class="card-header">
                                <h3>รายละเอียดออเดอร์ #<?php echo $order_details['id']; ?></h3>
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div>
                                        <p style="color: var(--secondary);">วันที่สั่งซื้อ</p>
                                        <p><strong><?php echo date('d/m/Y H:i', strtotime($order_details['order_date'])); ?></strong></p>
                                    </div>
                                    <div>
                                        <p style="color: var(--secondary);">สถานะ</p>
                                        <?php
                                        $status_class = '';
                                        switch($order_details['order_status']) {
                                            case 'pending': $status_class = 'badge-warning'; break;
                                            case 'processing': $status_class = 'badge-info'; break;
                                            case 'shipped': $status_class = 'badge-primary'; break;
                                            case 'delivered': $status_class = 'badge-success'; break;
                                            case 'cancelled': $status_class = 'badge-danger'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
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
                                    </div>
                                    <div>
                                        <p style="color: var(--secondary);">วิธีการชำระเงิน</p>
                                        <p><strong><?php echo $order_details['payment_method']; ?></strong></p>
                                    </div>
                                    <div>
                                        <p style="color: var(--secondary);">ยอดรวม</p>
                                        <p><strong style="color: var(--primary); font-size: 1.25rem;">฿<?php echo number_format($order_details['total_amount'], 2); ?></strong></p>
                                    </div>
                                </div>
                                
                                <!-- Order Items -->
                                <h4 style="margin-bottom: 1rem;">รายการสินค้า</h4>
                                <table style="width: 100%;">
                                    <thead>
                                        <tr style="background: var(--light);">
                                            <th style="padding: 0.75rem;">สินค้า</th>
                                            <th style="padding: 0.75rem;">ราคา</th>
                                            <th style="padding: 0.75rem;">จำนวน</th>
                                            <th style="padding: 0.75rem;">รวม</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($item = $order_items->fetch_assoc()): ?>
                                            <tr>
                                                <td style="padding: 0.75rem;">
                                                    <div style="display: flex; align-items: center;">
                                                        <?php if ($item['image'] && file_exists("../assets/images/" . $item['image'])): ?>
                                                            <img src="../assets/images/<?php echo $item['image']; ?>" 
                                                                 alt="<?php echo $item['product_name']; ?>" 
                                                                 class="order-product-image">
                                                        <?php endif; ?>
                                                        <?php echo $item['product_name']; ?>
                                                    </div>
                                                </td>
                                                <td style="padding: 0.75rem;">฿<?php echo number_format($item['price'], 2); ?></td>
                                                <td style="padding: 0.75rem;"><?php echo $item['quantity']; ?></td>
                                                <td style="padding: 0.75rem;"><strong>฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <!-- Customer Info -->
                        <div class="card">
                            <div class="card-header">
                                <h3>ข้อมูลลูกค้า</h3>
                            </div>
                            <div class="card-body">
                                <p><strong>ชื่อผู้ใช้:</strong> <?php echo $order_details['username']; ?></p>
                                <p><strong>ชื่อ-นามสกุล:</strong> <?php echo $order_details['fullname']; ?></p>
                                <p><strong>อีเมล:</strong> <?php echo $order_details['email']; ?></p>
                                <p><strong>เบอร์โทร:</strong> <?php echo $order_details['phone']; ?></p>
                                <p><strong>ที่อยู่จัดส่ง:</strong> <?php echo $order_details['shipping_address']; ?></p>
                            </div>
                        </div>
                        
                        <!-- Update Status -->
                        <div class="card">
                            <div class="card-header">
                                <h3>อัปเดตสถานะ</h3>
                            </div>
                            <div class="card-body">
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
                                    <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-save"></i> อัปเดตสถานะ
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h3>ไทม์ไลน์</h3>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="time"><?php echo date('d/m/Y H:i', strtotime($order_details['order_date'])); ?></div>
                                        <div class="title">สั่งซื้อสินค้า</div>
                                    </div>
                                    <?php if ($order_details['order_status'] != 'pending'): ?>
                                        <div class="timeline-item">
                                            <div class="time">-</div>
                                            <div class="title">กำลังดำเนินการ</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order_details['order_status'] == 'shipped' || $order_details['order_status'] == 'delivered'): ?>
                                        <div class="timeline-item">
                                            <div class="time">-</div>
                                            <div class="title">จัดส่งแล้ว</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order_details['order_status'] == 'delivered'): ?>
                                        <div class="timeline-item">
                                            <div class="time">-</div>
                                            <div class="title">ได้รับสินค้า</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Orders List View -->
                
                <!-- Summary Cards -->
                <?php
                $total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
                $pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch_assoc()['count'];
                $processing_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'")->fetch_assoc()['count'];
                $total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'")->fetch_assoc()['total'] ?? 0;
                ?>
                
                <div class="order-summary">
                    <div class="summary-card">
                        <div class="number"><?php echo $total_orders; ?></div>
                        <div class="label">ออเดอร์ทั้งหมด</div>
                    </div>
                    <div class="summary-card">
                        <div class="number"><?php echo $pending_orders; ?></div>
                        <div class="label">รอดำเนินการ</div>
                    </div>
                    <div class="summary-card">
                        <div class="number"><?php echo $processing_orders; ?></div>
                        <div class="label">กำลังดำเนินการ</div>
                    </div>
                    <div class="summary-card">
                        <div class="number">฿<?php echo number_format($total_revenue, 0); ?></div>
                        <div class="label">ยอดขายรวม</div>
                    </div>
                </div>
                
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
                            <button type="submit" class="btn btn-primary">กรอง</button>
                            <a href="orders.php" class="btn btn-secondary">ล้าง</a>
                        </div>
                    </form>
                </div>
                
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="ค้นหาออเดอร์...">
                    <button onclick="searchOrders()"><i class="fas fa-search"></i> ค้นหา</button>
                </div>
                
                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header">
                        <h3>รายการออเดอร์ทั้งหมด</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="ordersTable">
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
                                    <?php if ($orders->num_rows > 0): ?>
                                        <?php while ($order = $orders->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['fullname'] ?: $order['username']); ?>
                                                    <br>
                                                    <small style="color: var(--secondary);"><?php echo $order['email']; ?></small>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                                <td><strong style="color: var(--primary);">฿<?php echo number_format($order['total_amount'], 2); ?></strong></td>
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
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $order['payment_method']; ?></td>
                                                <td>
                                                    <a href="?view=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> ดู
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 2rem;">
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
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function searchOrders() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('ordersTable');
        if (!table) return;
        
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let row of rows) {
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let cell of cells) {
                if (cell.textContent.toLowerCase().includes(searchText)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>