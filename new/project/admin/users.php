<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['user_id'];
        
        // Don't allow deleting yourself
        if ($id != $_SESSION['user_id']) {
            $query = "DELETE FROM users WHERE id = $id";
            $conn->query($query);
            $success = "ลบผู้ใช้สำเร็จ";
        } else {
            $error = "ไม่สามารถลบบัญชีตัวเองได้";
        }
    }
}

// Get all users with order count and total spent
$users = $conn->query("SELECT u.*, 
                      COUNT(DISTINCT o.id) as order_count,
                      COALESCE(SUM(o.total_amount), 0) as total_spent
                      FROM users u
                      LEFT JOIN orders o ON u.id = o.user_id
                      GROUP BY u.id
                      ORDER BY u.created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            color: white;
            font-size: 0.8rem;
        }
        .role-admin {
            background: var(--primary-red);
        }
        .role-user {
            background: var(--primary-blue);
        }
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
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2 style="padding: 0 1.5rem; margin-bottom: 2rem;">Admin Panel</h2>
            <a href="index.php">แดชบอร์ด</a>
            <a href="users.php" style="background: var(--primary-blue);">จัดการผู้ใช้</a>
            <a href="products.php">จัดการสินค้า</a>
            <a href="orders.php">จัดการออเดอร์</a>
            <a href="categories.php">จัดการหมวดหมู่</a>
            <a href="stores.php">จัดการร้านค้า</a>
            <a href="../logout.php">ออกจากระบบ</a>
        </div>
        
        <div class="admin-content">
            <h1>จัดการผู้ใช้</h1>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Users Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>อีเมล</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>เบอร์โทร</th>
                        <th>บทบาท</th>
                        <th>จำนวนออเดอร์</th>
                        <th>ยอดซื้อรวม</th>
                        <th>วันที่สมัคร</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['fullname']; ?></td>
                            <td><?php echo $user['phone']; ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo $user['role'] == 'admin' ? 'ผู้ดูแลระบบ' : 'สมาชิก'; ?>
                                </span>
                            </td>
                            <td><?php echo $user['order_count']; ?></td>
                            <td>฿<?php echo number_format($user['total_spent'], 2); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                        class="btn">ดูรายละเอียด</button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('ลบผู้ใช้นี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')">
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
    
    <!-- View User Modal -->
    <div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; width: 90%; max-width: 600px; margin: 50px auto; padding: 2rem; border-radius: 10px;">
            <h3>รายละเอียดผู้ใช้</h3>
            <div id="userDetails" style="margin-top: 1rem;">
                <!-- User details will be populated here -->
            </div>
            <button type="button" onclick="closeViewModal()" class="btn btn-red" style="margin-top: 1rem;">ปิด</button>
        </div>
    </div>
    
    <script>
    function viewUser(user) {
        const details = `
            <p><strong>ID:</strong> ${user.id}</p>
            <p><strong>ชื่อผู้ใช้:</strong> ${user.username}</p>
            <p><strong>อีเมล:</strong> ${user.email}</p>
            <p><strong>ชื่อ-นามสกุล:</strong> ${user.fullname || '-'}</p>
            <p><strong>เบอร์โทร:</strong> ${user.phone || '-'}</p>
            <p><strong>ที่อยู่:</strong> ${user.address || '-'}</p>
            <p><strong>บทบาท:</strong> ${user.role == 'admin' ? 'ผู้ดูแลระบบ' : 'สมาชิก'}</p>
            <p><strong>วันที่สมัคร:</strong> ${new Date(user.created_at).toLocaleString('th-TH')}</p>
        `;
        document.getElementById('userDetails').innerHTML = details;
        document.getElementById('viewModal').style.display = 'block';
    }
    
    function closeViewModal() {
        document.getElementById('viewModal').style.display = 'none';
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>