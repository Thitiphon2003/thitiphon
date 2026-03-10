<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'account.php';
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Update user information
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname = sanitize($_POST['fullname']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        // Check if email already exists for other users
        $check_email = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if ($check_email->num_rows > 0) {
            $error = "อีเมลนี้มีผู้ใช้งานแล้ว";
        } else {
            $update_query = "UPDATE users SET 
                            fullname = '$fullname',
                            email = '$email',
                            phone = '$phone',
                            address = '$address'
                            WHERE id = $user_id";
            
            if ($conn->query($update_query)) {
                $success = "อัปเดตข้อมูลสำเร็จ";
                // Refresh user data
                $user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
                $user = $user_result->fetch_assoc();
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // ตรวจสอบรหัสผ่านปัจจุบัน (แบบ plain text)
        if ($current_password === $user['password']) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    // อัปเดตรหัสผ่านใหม่ (เก็บเป็น plain text)
                    $update_pass = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
                    if ($conn->query($update_pass)) {
                        $pass_success = "เปลี่ยนรหัสผ่านสำเร็จ";
                        // Refresh user data to get new password
                        $user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
                        $user = $user_result->fetch_assoc();
                    } else {
                        $pass_error = "เกิดข้อผิดพลาด: " . $conn->error;
                    }
                } else {
                    $pass_error = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
                }
            } else {
                $pass_error = "รหัสผ่านใหม่ไม่ตรงกัน";
            }
        } else {
            $pass_error = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        }
    }
}

// Get user orders
$orders_query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC LIMIT 5";
$orders = $conn->query($orders_query);

include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>บัญชีของฉัน</h1>
        <p>จัดการข้อมูลส่วนตัวและติดตามออเดอร์ของคุณ</p>
    </div>
</div>

<div class="container">
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 2rem;">
        <!-- Sidebar -->
        <div>
            <div style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md); text-align: center;">
                <div style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-blue), var(--primary-red)); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 3rem; color: white; font-weight: 700;">
                        <?php echo strtoupper(substr($user['username'] ?: 'U', 0, 1)); ?>
                    </span>
                </div>
                <h3 style="margin-bottom: 0.25rem;"><?php echo $user['fullname'] ?: $user['username']; ?></h3>
                <p style="color: var(--medium-gray); margin-bottom: 1rem;"><?php echo $user['email']; ?></p>
                
                <div style="border-top: 1px solid var(--light-gray); padding-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: var(--medium-gray);">สมาชิกตั้งแต่:</span>
                        <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: var(--shadow-md); margin-top: 1rem;">
                <h4 style="margin-bottom: 1rem;">เมนู</h4>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 0.75rem;">
                        <a href="#profile" style="text-decoration: none; color: var(--dark-gray); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user" style="color: var(--primary-blue);"></i> ข้อมูลส่วนตัว
                        </a>
                    </li>
                    <li style="margin-bottom: 0.75rem;">
                        <a href="#orders" style="text-decoration: none; color: var(--dark-gray); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-shopping-bag" style="color: var(--primary-blue);"></i> ประวัติการสั่งซื้อ
                        </a>
                    </li>
                    <li style="margin-bottom: 0.75rem;">
                        <a href="#password" style="text-decoration: none; color: var(--dark-gray); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-lock" style="color: var(--primary-blue);"></i> เปลี่ยนรหัสผ่าน
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" style="text-decoration: none; color: var(--primary-red); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div>
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Section -->
            <div id="profile" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1.5rem;">ข้อมูลส่วนตัว</h2>
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small style="color: var(--medium-gray);">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</small>
                        </div>
                        
                        <div class="form-group">
                            <label>ชื่อ-นามสกุล</label>
                            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>อีเมล</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>ที่อยู่</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>
            
            <!-- Orders Section -->
            <div id="orders" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1.5rem;">ประวัติการสั่งซื้อล่าสุด</h2>
                
                <?php if ($orders->num_rows == 0): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                        <p style="color: var(--medium-gray);">ยังไม่มีประวัติการสั่งซื้อ</p>
                        <a href="category.php" class="btn btn-primary" style="margin-top: 1rem;">เริ่มช้อปปิ้ง</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
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
                                        <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                        <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span style="background: 
                                                <?php 
                                                switch($order['order_status']) {
                                                    case 'pending': echo '#ffc107'; break;
                                                    case 'processing': echo '#17a2b8'; break;
                                                    case 'shipped': echo '#007bff'; break;
                                                    case 'delivered': echo '#28a745'; break;
                                                    case 'cancelled': echo '#dc3545'; break;
                                                }
                                                ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem;">
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
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.75rem;">ดูรายละเอียด</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($orders->num_rows >= 5): ?>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="orders.php" style="color: var(--primary-blue); text-decoration: none;">ดูออเดอร์ทั้งหมด <i class="fas fa-arrow-right"></i></a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Change Password Section -->
            <div id="password" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: var(--shadow-md);">
                <h2 style="margin-bottom: 1.5rem;">เปลี่ยนรหัสผ่าน</h2>
                
                <?php if (isset($pass_success)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $pass_success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($pass_error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $pass_error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" class="form-control" required>
                        <small style="color: var(--medium-gray);">ความยาวอย่างน้อย 6 ตัวอักษร</small>
                    </div>
                    
                    <div class="form-group">
                        <label>ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                    </button>
                </form>
                
                <!-- Debug info (สามารถลบได้หลังจากทดสอบ) -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <div style="margin-top: 2rem; padding: 1rem; background: #f0f0f0; border-radius: 10px;">
                        <p><strong>Debug Info (สำหรับ Admin):</strong></p>
                        <p>รหัสผ่านปัจจุบันในฐานข้อมูล: <?php echo $user['password']; ?></p>
                        <p><small>ถ้าขึ้นว่ารหัสผิด แสดงว่ารหัสผ่านในฐานข้อมูลกับที่พิมพ์ไม่ตรงกัน</small></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/new-footer.php'; ?>