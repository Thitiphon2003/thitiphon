<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบ
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if(!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// อัปเดต session ให้ตรงกับข้อมูลล่าสุด
$_SESSION['firstname'] = $user['firstname'];
$_SESSION['lastname'] = $user['lastname'];
$_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
$_SESSION['email'] = $user['email'];
$_SESSION['phone'] = $user['phone'];
$_SESSION['level'] = $user['level'];
$_SESSION['points'] = $user['points'];

// ดึงสถิติคำสั่งซื้อ
$order_stats = fetchOne("SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_spent 
                         FROM orders WHERE user_id = ? AND order_status IN ('delivered', 'shipping')", 
                         [$user_id]);

$total_orders = $order_stats['total_orders'] ?? 0;
$total_spent = $order_stats['total_spent'] ?? 0;

// จัดการการอัปเดตโปรไฟล์
$success_message = '';
$error_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if(empty($firstname) || empty($lastname)) {
        $error_message = 'กรุณากรอกชื่อและนามสกุล';
    } else {
        try {
            $update_sql = "UPDATE users SET firstname = ?, lastname = ?, phone = ? WHERE id = ?";
            query($update_sql, [$firstname, $lastname, $phone, $user_id]);
            
            // อัปเดต session
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['fullname'] = $firstname . ' ' . $lastname;
            $_SESSION['phone'] = $phone;
            
            // อัปเดตตัวแปร
            $user['firstname'] = $firstname;
            $user['lastname'] = $lastname;
            $user['phone'] = $phone;
            
            $success_message = 'อัปเดตโปรไฟล์เรียบร้อยแล้ว';
        } catch(Exception $e) {
            $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน - SHOP.COM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand a {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .user-icon {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .user-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .profile-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .profile-email {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .profile-level {
            display: inline-block;
            padding: 0.3rem 1rem;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        .profile-nav {
            padding: 1rem 0;
        }
        
        .profile-nav-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #555;
            text-decoration: none;
            gap: 1rem;
        }
        
        .profile-nav-item i {
            width: 20px;
            color: #667eea;
        }
        
        .profile-nav-item:hover {
            background: #f8f9fa;
        }
        
        .profile-nav-item.active {
            background: #f0f3ff;
            color: #667eea;
            border-left: 4px solid #667eea;
        }
        
        .profile-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .content-header h2 {
            font-size: 1.5rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .content-header h2 i {
            color: #667eea;
        }
        
        .btn-edit {
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-save {
            padding: 0.5rem 1.5rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .btn-cancel {
            padding: 0.5rem 1.5rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid #e1e5e9;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }
        
        .edit-form {
            display: none;
        }
        
        .edit-form.active {
            display: block;
        }
        
        .view-mode {
            display: block;
        }
        
        .view-mode.hidden {
            display: none;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php">SHOP.COM</a>
            </div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.php">หน้าแรก</a></li>
                    <li><a href="index.php#categories">หมวดหมู่</a></li>
                    <li><a href="index.php#products">สินค้าทั้งหมด</a></li>
                    <li><a href="#contact">ติดต่อเรา</a></li>
                </ul>
                <div class="nav-icons">
                    <div class="user-dropdown">
                        <a href="#" class="user-icon">
                            <i class="fas fa-user-circle"></i>
                            <?php echo $_SESSION['fullname']; ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php"><i class="fas fa-user-circle"></i> โปรไฟล์ของฉัน</a>
                            <a href="orders.php"><i class="fas fa-shopping-bag"></i> คำสั่งซื้อของฉัน</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
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
                    <i class="fas fa-user-circle"></i>
                    <span>ข้อมูลส่วนตัว</span>
                </a>
                <a href="orders.php" class="profile-nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>คำสั่งซื้อของฉัน</span>
                </a>
                <a href="logout.php" class="profile-nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>ออกจากระบบ</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="profile-content">
            <div class="content-header">
                <h2><i class="fas fa-user-circle"></i> ข้อมูลส่วนตัว</h2>
                <button class="btn-edit" onclick="enableEdit()" id="editBtn">
                    <i class="fas fa-edit"></i> แก้ไขข้อมูล
                </button>
            </div>
            
            <?php if($success_message): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- View Mode -->
            <div id="viewMode" class="view-mode">
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-user"></i> ชื่อผู้ใช้</div>
                        <div class="info-value"><?php echo $user['username']; ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-tag"></i> ระดับสมาชิก</div>
                        <div class="info-value"><?php echo $user['level']; ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-user"></i> ชื่อ</div>
                        <div class="info-value"><?php echo $user['firstname']; ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-user"></i> นามสกุล</div>
                        <div class="info-value"><?php echo $user['lastname']; ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-envelope"></i> อีเมล</div>
                        <div class="info-value"><?php echo $user['email']; ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-phone"></i> เบอร์โทรศัพท์</div>
                        <div class="info-value"><?php echo $user['phone'] ?? '-'; ?></div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-star"></i> คะแนนสะสม</div>
                        <div class="info-value"><?php echo number_format($user['points']); ?> พอยท์</div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label"><i class="fas fa-calendar"></i> วันที่สมัคร</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Mode -->
            <div id="editMode" class="edit-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>ชื่อ</label>
                        <input type="text" name="firstname" value="<?php echo $user['firstname']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>นามสกุล</label>
                        <input type="text" name="lastname" value="<?php echo $user['lastname']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" value="<?php echo $user['phone'] ?? ''; ?>" maxlength="10">
                    </div>
                    
                    <div class="form-group">
                        <label>อีเมล (ไม่สามารถแก้ไขได้)</label>
                        <input type="email" value="<?php echo $user['email']; ?>" disabled>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-save">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                        <button type="button" class="btn-cancel" onclick="disableEdit()">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function enableEdit() {
            document.getElementById('viewMode').classList.add('hidden');
            document.getElementById('editMode').classList.add('active');
            document.getElementById('editBtn').style.display = 'none';
        }
        
        function disableEdit() {
            document.getElementById('viewMode').classList.remove('hidden');
            document.getElementById('editMode').classList.remove('active');
            document.getElementById('editBtn').style.display = 'block';
        }
    </script>
</body>
</html>