<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบแอดมิน
if(!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin = fetchOne("SELECT * FROM users WHERE id = ? AND is_admin = 1", [$_SESSION['admin_id']]);
if(!$admin) {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

// บันทึกการตั้งค่า
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['save_settings'])) {
        // บันทึกการตั้งค่าต่างๆ
        $_SESSION['success'] = 'บันทึกการตั้งค่าเรียบร้อยแล้ว';
        header('Location: admin_settings.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - SHOP.COM</title>
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
        
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            position: fixed;
            height: 100vh;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.2);
            border-left: 4px solid #ffd700;
        }
        
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
        }
        
        .logout-btn {
            padding: 0.5rem 1.5rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }
        
        .settings-section {
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .settings-section h3 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            color: #555;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
        }
        
        .btn-primary {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
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
            </div>
            
            <div class="nav-menu">
                <a href="admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
                <a href="admin_users.php" class="nav-item"><i class="fas fa-users"></i> จัดการผู้ใช้</a>
                <a href="admin_products.php" class="nav-item"><i class="fas fa-box"></i> จัดการสินค้า</a>
                <a href="admin_orders.php" class="nav-item"><i class="fas fa-shopping-cart"></i> จัดการออเดอร์</a>
                <a href="admin_categories.php" class="nav-item"><i class="fas fa-tags"></i> จัดการหมวดหมู่</a>
                <a href="admin_sellers.php" class="nav-item"><i class="fas fa-store"></i> จัดการร้านค้า</a>
                <a href="admin_settings.php" class="nav-item active"><i class="fas fa-cog"></i> ตั้งค่า</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-cog"></i> ตั้งค่าระบบ</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <form method="POST">
                    <div class="settings-grid">
                        <div class="settings-section">
                            <h3>ข้อมูลร้านค้า</h3>
                            <div class="form-group">
                                <label>ชื่อร้านค้า</label>
                                <input type="text" value="SHOP.COM">
                            </div>
                            <div class="form-group">
                                <label>คำอธิบาย</label>
                                <input type="text" value="ร้านค้าออนไลน์คุณภาพ">
                            </div>
                            <div class="form-group">
                                <label>อีเมลติดต่อ</label>
                                <input type="email" value="contact@shop.com">
                            </div>
                            <div class="form-group">
                                <label>เบอร์โทรศัพท์</label>
                                <input type="text" value="02-123-4567">
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3>การจัดส่ง</h3>
                            <div class="form-group">
                                <label>ค่าจัดส่งเริ่มต้น</label>
                                <input type="number" value="50">
                            </div>
                            <div class="form-group">
                                <label>จัดส่งฟรีเมื่อซื้อตั้งแต่</label>
                                <input type="number" value="500">
                            </div>
                            <div class="form-group">
                                <label>บริษัทขนส่งหลัก</label>
                                <select>
                                    <option>Kerry Express</option>
                                    <option>Flash Express</option>
                                    <option>J&T Express</option>
                                    <option>ไปรษณีย์ไทย</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3>การชำระเงิน</h3>
                            <div class="form-group">
                                <label>ธนาคาร</label>
                                <input type="text" value="ธนาคารกรุงเทพ">
                            </div>
                            <div class="form-group">
                                <label>เลขที่บัญชี</label>
                                <input type="text" value="123-4-56789-0">
                            </div>
                            <div class="form-group">
                                <label>ชื่อบัญชี</label>
                                <input type="text" value="บริษัท ช้อป จำกัด">
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h3>Social Media</h3>
                            <div class="form-group">
                                <label>Facebook</label>
                                <input type="text" value="https://facebook.com/shop">
                            </div>
                            <div class="form-group">
                                <label>LINE</label>
                                <input type="text" value="@shop">
                            </div>
                            <div class="form-group">
                                <label>Instagram</label>
                                <input type="text" value="@shop_official">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="save_settings" class="btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่า
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>