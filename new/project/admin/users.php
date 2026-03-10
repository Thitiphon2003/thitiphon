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
            if ($conn->query("DELETE FROM users WHERE id = $id")) {
                $_SESSION['success'] = "ลบผู้ใช้สำเร็จ";
            } else {
                $_SESSION['error'] = "ไม่สามารถลบผู้ใช้ได้";
            }
        }
        header("Location: users.php");
        exit();
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Get current admin info
$admin = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
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
                    <a href="users.php" class="menu-item active">
                        <i class="fas fa-users"></i>
                        <span>จัดการผู้ใช้</span>
                        <span class="badge"><?php echo $users->num_rows; ?></span>
                    </a>
                    <a href="products.php" class="menu-item">
                        <i class="fas fa-box"></i>
                        <span>จัดการสินค้า</span>
                    </a>
                    <a href="orders.php" class="menu-item">
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
                <h1 class="page-title">จัดการผู้ใช้</h1>
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
            
            <!-- Search Bar -->
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="ค้นหาผู้ใช้...">
                <button onclick="searchUsers()"><i class="fas fa-search"></i> ค้นหา</button>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3>รายการผู้ใช้ทั้งหมด</h3>
                    <button class="btn btn-success btn-sm" onclick="showAddUserModal()">
                        <i class="fas fa-plus"></i> เพิ่มผู้ใช้
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="usersTable">
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
                                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['fullname'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($user['role'] == 'admin'): ?>
                                                <span class="badge badge-danger">ผู้ดูแลระบบ</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">สมาชิก</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-primary btn-sm" onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View User Modal -->
    <div class="modal" id="viewUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>รายละเอียดผู้ใช้</h3>
                <button class="modal-close" onclick="closeModal('viewUserModal')">&times;</button>
            </div>
            <div class="modal-body" id="userDetails">
                <!-- User details will be populated here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewUserModal')">ปิด</button>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>เพิ่มผู้ใช้ใหม่</h3>
                <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST" action="add_user.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label>ชื่อผู้ใช้ *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>อีเมล *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>รหัสผ่าน *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล</label>
                        <input type="text" name="fullname" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>ที่อยู่</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>บทบาท</label>
                        <select name="role" class="form-control">
                            <option value="user">สมาชิก</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">บันทึก</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Modal functions
    function showModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    function viewUser(user) {
        const details = `
            <div style="margin-bottom: 1rem;">
                <p><strong>ID:</strong> ${user.id}</p>
                <p><strong>ชื่อผู้ใช้:</strong> ${user.username}</p>
                <p><strong>อีเมล:</strong> ${user.email}</p>
                <p><strong>ชื่อ-นามสกุล:</strong> ${user.fullname || '-'}</p>
                <p><strong>เบอร์โทร:</strong> ${user.phone || '-'}</p>
                <p><strong>ที่อยู่:</strong> ${user.address || '-'}</p>
                <p><strong>บทบาท:</strong> ${user.role == 'admin' ? 'ผู้ดูแลระบบ' : 'สมาชิก'}</p>
                <p><strong>วันที่สมัคร:</strong> ${new Date(user.created_at).toLocaleString('th-TH')}</p>
            </div>
        `;
        document.getElementById('userDetails').innerHTML = details;
        showModal('viewUserModal');
    }
    
    function deleteUser(userId) {
        if (confirm('ต้องการลบผู้ใช้นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="delete_user" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function showAddUserModal() {
        showModal('addUserModal');
    }
    
    function searchUsers() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('usersTable');
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
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>