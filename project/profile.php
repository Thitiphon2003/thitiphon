<?php
session_start();
require_once 'db_connect.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if(!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$order_stats = fetchOne("SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_spent 
                         FROM orders WHERE user_id = ? AND order_status IN ('delivered', 'shipping')", 
                         [$user_id]);

$total_orders = $order_stats['total_orders'] ?? 0;
$total_spent = $order_stats['total_spent'] ?? 0;

$success_message = '';
$error_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if(empty($firstname) || empty($lastname)) {
            $error_message = 'กรุณากรอกชื่อและนามสกุล';
        } else {
            query("UPDATE users SET firstname = ?, lastname = ?, phone = ? WHERE id = ?", 
                  [$firstname, $lastname, $phone, $user_id]);
            
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['fullname'] = $firstname . ' ' . $lastname;
            
            $user['firstname'] = $firstname;
            $user['lastname'] = $lastname;
            $user['phone'] = $phone;
            
            $success_message = 'อัปเดตโปรไฟล์เรียบร้อยแล้ว';
        }
    }
}

$page_title = 'โปรไฟล์ของฉัน';
include 'includes/header.php';
?>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?php echo showImage($user['avatar'], 'profiles', 'default-avatar.png'); ?>" alt="avatar">
            </div>
            <div class="profile-name"><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></div>
            <div class="profile-email"><?php echo $user['email']; ?></div>
            <div class="profile-level"><?php echo $user['level']; ?></div>
        </div>
        
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-label">คำสั่งซื้อ</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">฿<?php echo number_format($total_spent); ?></div>
                <div class="stat-label">ยอดสั่งซื้อ</div>
            </div>
        </div>
        
        <div class="profile-nav">
            <a href="profile.php" class="profile-nav-item active">
                <i class="fas fa-user"></i> ข้อมูลส่วนตัว
            </a>
            <a href="orders.php" class="profile-nav-item">
                <i class="fas fa-shopping-bag"></i> คำสั่งซื้อของฉัน
            </a>
            <a href="logout.php" class="profile-nav-item">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="profile-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
            <h2 style="font-size: 1.5rem; color: #0f172a;">ข้อมูลส่วนตัว</h2>
            <button class="btn btn-primary" onclick="enableEdit()" id="editBtn">แก้ไขข้อมูล</button>
        </div>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- View Mode -->
        <div id="viewMode">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">ชื่อผู้ใช้</div>
                    <div style="font-weight: 500;"><?php echo $user['username']; ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">ระดับสมาชิก</div>
                    <div style="font-weight: 500;"><?php echo $user['level']; ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">ชื่อ</div>
                    <div style="font-weight: 500;"><?php echo $user['firstname']; ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">นามสกุล</div>
                    <div style="font-weight: 500;"><?php echo $user['lastname']; ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">อีเมล</div>
                    <div style="font-weight: 500;"><?php echo $user['email']; ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">เบอร์โทรศัพท์</div>
                    <div style="font-weight: 500;"><?php echo $user['phone'] ?? '-'; ?></div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">คะแนนสะสม</div>
                    <div style="font-weight: 500;"><?php echo number_format($user['points']); ?> พอยท์</div>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.3rem;">วันที่สมัคร</div>
                    <div style="font-weight: 500;"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Edit Mode -->
        <div id="editMode" style="display: none;">
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label>ชื่อ</label>
                        <input type="text" name="firstname" value="<?php echo $user['firstname']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>นามสกุล</label>
                        <input type="text" name="lastname" value="<?php echo $user['lastname']; ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" value="<?php echo $user['phone'] ?? ''; ?>" maxlength="10">
                </div>
                <div class="form-group">
                    <label>อีเมล (ไม่สามารถแก้ไขได้)</label>
                    <input type="email" value="<?php echo $user['email']; ?>" disabled>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="submit" name="update_profile" class="btn btn-primary">บันทึก</button>
                    <button type="button" class="btn btn-secondary" onclick="disableEdit()">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function enableEdit() {
    document.getElementById('viewMode').style.display = 'none';
    document.getElementById('editMode').style.display = 'block';
    document.getElementById('editBtn').style.display = 'none';
}

function disableEdit() {
    document.getElementById('viewMode').style.display = 'block';
    document.getElementById('editMode').style.display = 'none';
    document.getElementById('editBtn').style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>