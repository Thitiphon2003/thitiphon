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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['user_id'];
        if ($id != $_SESSION['user_id']) {
            $conn->query("DELETE FROM users WHERE id = $id");
        }
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Admin</title>
    <link rel="stylesheet" href="../assets/css/new-style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>ShopHub Admin</h2>
            <a href="index.php">📊 แดชบอร์ด</a>
            <a href="users.php" style="background: #2d3748; color: white; border-left: 3px solid #4f9da6;">👥 จัดการผู้ใช้</a>
            <a href="products.php">📦 จัดการสินค้า</a>
            <a href="orders.php">📋 จัดการออเดอร์</a>
            <a href="categories.php">📑 จัดการหมวดหมู่</a>
            <a href="stores.php">🏪 จัดการร้านค้า</a>
            <div class="logout-btn">
                <a href="../logout.php">🚪 ออกจากระบบ</a>
            </div>
        </div>
        
        <div class="admin-content">
            <h1>จัดการผู้ใช้</h1>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>อีเมล</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>เบอร์โทร</th>
                        <th>บทบาท</th>
                        <th>วันที่สมัคร</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['fullname'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td>
                                <span style="background: <?php echo $user['role'] == 'admin' ? '#dc3545' : '#4f9da6'; ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 3px;">
                                    <?php echo $user['role'] == 'admin' ? 'ผู้ดูแล' : 'สมาชิก'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('ลบผู้ใช้นี้?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-red">ลบ</button>
                                    </form>
                                <?php endif; ?>
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