<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบแอดมิน
if(!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// ดึงข้อมูลแอดมิน
$admin = fetchOne("SELECT * FROM users WHERE id = ? AND is_admin = 1", [$_SESSION['admin_id']]);

if(!$admin) {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

// จัดการการลบผู้ใช้
if(isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    if($user_id != $_SESSION['admin_id']) { // ป้องกันการลบตัวเอง
        delete('users', 'id = ?', [$user_id]);
        $_SESSION['success'] = 'ลบผู้ใช้เรียบร้อยแล้ว';
    }
    header('Location: admin_users.php');
    exit();
}

// จัดการการเพิ่ม/แก้ไขผู้ใช้
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_user'])) {
        // เพิ่มผู้ใช้ใหม่
        $username = trim($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = trim($_POST['email']);
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $phone = trim($_POST['phone']);
        $level = $_POST['level'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        $check = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        if($check) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว';
        } else {
            $sql = "INSERT INTO users (username, password, email, firstname, lastname, phone, level, is_admin, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
            query($sql, [$username, $password, $email, $firstname, $lastname, $phone, $level, $is_admin]);
            $message = 'เพิ่มผู้ใช้เรียบร้อยแล้ว';
        }
    }
    
    if(isset($_POST['edit_user'])) {
        // แก้ไขผู้ใช้
        $user_id = $_POST['user_id'];
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $phone = trim($_POST['phone']);
        $level = $_POST['level'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        $status = $_POST['status'];
        
        $sql = "UPDATE users SET firstname=?, lastname=?, phone=?, level=?, is_admin=?, status=? WHERE id=?";
        query($sql, [$firstname, $lastname, $phone, $level, $is_admin, $status, $user_id]);
        
        if(!empty($_POST['new_password'])) {
            $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            query("UPDATE users SET password=? WHERE id=?", [$password, $user_id]);
        }
        
        $message = 'อัปเดตข้อมูลเรียบร้อยแล้ว';
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$users = fetchAll("SELECT * FROM users ORDER BY id DESC");

// ดึงข้อมูลผู้ใช้สำหรับแก้ไข
$edit_user = null;
if(isset($_GET['edit'])) {
    $edit_user = fetchOne("SELECT * FROM users WHERE id = ?", [$_GET['edit']]);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - SHOP.COM</title>
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
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 0.3rem;
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
        
        .admin-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .admin-role {
            font-size: 0.8rem;
            opacity: 0.7;
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
            transition: all 0.3s;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.2);
            color: white;
            border-left: 4px solid #ffd700;
        }
        
        .nav-item i {
            width: 20px;
        }
        
        /* Main Content */
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title h1 {
            font-size: 1.5rem;
            color: #333;
        }
        
        .logout-btn {
            padding: 0.5rem 1.5rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .card-header h2 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .admin-badge {
            background: #ffd700;
            color: #333;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.7rem;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin: 0 2px;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }
            .main-content {
                margin-left: 0;
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
                <div class="admin-role">Administrator</div>
            </div>
            
            <div class="nav-menu">
                <a href="admin.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                </a>
                <a href="admin_users.php" class="nav-item active">
                    <i class="fas fa-users"></i> จัดการผู้ใช้
                </a>
                <a href="admin_products.php" class="nav-item">
                    <i class="fas fa-box"></i> จัดการสินค้า
                </a>
                <a href="admin_orders.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i> จัดการออเดอร์
                </a>
                <a href="admin_categories.php" class="nav-item">
                    <i class="fas fa-tags"></i> จัดการหมวดหมู่
                </a>
                <a href="admin_sellers.php" class="nav-item">
                    <i class="fas fa-store"></i> จัดการร้านค้า
                </a>
                <a href="admin_settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> ตั้งค่า
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1><i class="fas fa-users"></i> จัดการผู้ใช้</h1>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> รายชื่อผู้ใช้ทั้งหมด</h2>
                    <button class="btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> เพิ่มผู้ใช้ใหม่
                    </button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทร</th>
                            <th>ระดับ</th>
                            <th>แอดมิน</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['phone'] ?? '-'; ?></td>
                            <td><?php echo $user['level']; ?></td>
                            <td>
                                <?php if($user['is_admin']): ?>
                                    <span class="admin-badge"><i class="fas fa-crown"></i> Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo $user['status'] == 'active' ? 'ใช้งาน' : 'ระงับ'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn btn-warning" onclick="editUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($user['id'] != $_SESSION['admin_id']): ?>
                                    <button class="action-btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มผู้ใช้ -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> เพิ่มผู้ใช้ใหม่</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>ชื่อผู้ใช้</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>ชื่อ</label>
                    <input type="text" name="firstname" required>
                </div>
                <div class="form-group">
                    <label>นามสกุล</label>
                    <input type="text" name="lastname" required>
                </div>
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>ระดับสมาชิก</label>
                    <select name="level">
                        <option value="Bronze">Bronze</option>
                        <option value="Silver">Silver</option>
                        <option value="Gold">Gold</option>
                        <option value="Platinum">Platinum</option>
                    </select>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_admin" id="is_admin">
                    <label for="is_admin">ตั้งเป็นแอดมิน</label>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_user" class="btn-success">บันทึก</button>
                    <button type="button" class="btn-danger" onclick="closeModal('addModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal แก้ไขผู้ใช้ -->
    <?php if($edit_user): ?>
    <div id="editModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> แก้ไขผู้ใช้</h2>
                <span class="close" onclick="window.location.href='admin_users.php'">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <div class="form-group">
                    <label>ชื่อผู้ใช้</label>
                    <input type="text" value="<?php echo $edit_user['username']; ?>" disabled>
                </div>
                <div class="form-group">
                    <label>ชื่อ</label>
                    <input type="text" name="firstname" value="<?php echo $edit_user['firstname']; ?>" required>
                </div>
                <div class="form-group">
                    <label>นามสกุล</label>
                    <input type="text" name="lastname" value="<?php echo $edit_user['lastname']; ?>" required>
                </div>
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" value="<?php echo $edit_user['email']; ?>" disabled>
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="<?php echo $edit_user['phone']; ?>">
                </div>
                <div class="form-group">
                    <label>รหัสผ่านใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                    <input type="password" name="new_password">
                </div>
                <div class="form-group">
                    <label>ระดับสมาชิก</label>
                    <select name="level">
                        <option value="Bronze" <?php echo $edit_user['level']=='Bronze'?'selected':''; ?>>Bronze</option>
                        <option value="Silver" <?php echo $edit_user['level']=='Silver'?'selected':''; ?>>Silver</option>
                        <option value="Gold" <?php echo $edit_user['level']=='Gold'?'selected':''; ?>>Gold</option>
                        <option value="Platinum" <?php echo $edit_user['level']=='Platinum'?'selected':''; ?>>Platinum</option>
                    </select>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_admin" id="edit_is_admin" <?php echo $edit_user['is_admin'] ? 'checked' : ''; ?>>
                    <label for="edit_is_admin">เป็นแอดมิน</label>
                </div>
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active" <?php echo $edit_user['status']=='active'?'selected':''; ?>>ใช้งาน</option>
                        <option value="inactive" <?php echo $edit_user['status']=='inactive'?'selected':''; ?>>ระงับ</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="edit_user" class="btn-success">บันทึกการเปลี่ยนแปลง</button>
                    <a href="admin_users.php" class="btn-danger" style="padding: 8px 16px; text-decoration: none; border-radius: 5px;">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editUser(id) {
            window.location.href = 'admin_users.php?edit=' + id;
        }
        
        function deleteUser(id) {
            if(confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?')) {
                window.location.href = 'admin_users.php?delete=' + id;
            }
        }
        
        // ปิด modal เมื่อคลิกนอก
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>