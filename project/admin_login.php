<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบแอดมิน
if(!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin = fetchOne("SELECT * FROM users WHERE id = ? AND is_admin = 1", [$admin_id]);
if(!$admin) {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

// ดึงข้อมูลสถิติ
$total_users = fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            min-height: 100vh;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .sidebar .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.2rem 0;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }
        .sidebar .nav-link.active {
            background-color: #e7f1ff;
            color: #0d6efd;
        }
        .sidebar .nav-link i {
            width: 1.5rem;
        }
        .main-content {
            padding: 1.5rem;
        }
        .stat-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.25rem;
            transition: all 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 3rem;
            height: 3rem;
            background-color: #e9ecef;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #0d6efd;
        }
        .table th {
            font-weight: 600;
            color: #495057;
            border-bottom-width: 1px;
        }
        .badge-status {
            padding: 0.35em 0.65em;
            font-weight: 500;
            border-radius: 0.25rem;
        }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-processing { background-color: #cff4fc; color: #055160; }
        .badge-shipping { background-color: #cfe2ff; color: #084298; }
        .badge-delivered { background-color: #d1e7dd; color: #0a3622; }
        .badge-cancelled { background-color: #f8d7da; color: #842029; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 p-0 sidebar">
                <div class="p-3">
                    <h4 class="fw-bold mb-4">SHOP.COM</h4>
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light rounded-circle p-2 me-2">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo $admin['firstname'] . ' ' . $admin['lastname']; ?></div>
                            <small class="text-muted">Administrator</small>
                        </div>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="admin.php"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
                        <a class="nav-link" href="admin_users.php"><i class="fas fa-users"></i> ผู้ใช้</a>
                        <a class="nav-link" href="admin_products.php"><i class="fas fa-box"></i> สินค้า</a>
                        <a class="nav-link" href="admin_orders.php"><i class="fas fa-shopping-cart"></i> ออเดอร์</a>
                        <a class="nav-link" href="admin_categories.php"><i class="fas fa-tags"></i> หมวดหมู่</a>
                        <a class="nav-link" href="admin_sellers.php"><i class="fas fa-store"></i> ร้านค้า</a>
                        <a class="nav-link" href="admin_settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
                        <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">แดชบอร์ด</h2>
                    <div>
                        <span class="text-muted">สวัสดี, <?php echo $admin['firstname']; ?></span>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <div class="text-muted small">ผู้ใช้ทั้งหมด</div>
                                <div class="h4 mb-0"><?php echo number_format($total_users); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon me-3">
                                <i class="fas fa-box"></i>
                            </div>
                            <div>
                                <div class="text-muted small">สินค้าทั้งหมด</div>
                                <div class="h4 mb-0"><?php echo number_format($total_products); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon me-3">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div>
                                <div class="text-muted small">ออเดอร์ทั้งหมด</div>
                                <div class="h4 mb-0"><?php echo number_format($total_orders); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card d-flex align-items-center">
                            <div class="stat-icon me-3">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <div class="text-muted small">ยอดขายรวม</div>
                                <div class="h4 mb-0">฿<?php echo number_format($total_revenue); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="fw-bold mb-0">ออเดอร์ล่าสุด</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>เลขที่ออเดอร์</th>
                                        <th>ลูกค้า</th>
                                        <th>วันที่</th>
                                        <th>ยอดรวม</th>
                                        <th>สถานะ</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_orders as $order): ?>
                                    <tr>
                                        <td><span class="fw-semibold"><?php echo $order['order_number']; ?></span></td>
                                        <td><?php echo $order['firstname'] . ' ' . $order['lastname']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>฿<?php echo number_format($order['total']); ?></td>
                                        <td>
                                            <span class="badge-status badge-<?php echo $order['order_status']; ?>">
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
                                            <a href="admin_orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-end pb-3">
                        <a href="admin_orders.php" class="btn btn-link">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="fw-bold mb-0">ผู้ใช้ล่าสุด</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ชื่อผู้ใช้</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>อีเมล</th>
                                        <th>วันที่สมัคร</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="admin_users.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-end pb-3">
                        <a href="admin_users.php" class="btn btn-link">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>