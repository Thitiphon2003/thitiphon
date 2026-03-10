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

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize($_POST['category_name']);
        $description = sanitize($_POST['category_description']);
        
        $query = "INSERT INTO categories (category_name, category_description) 
                  VALUES ('$name', '$description')";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "เพิ่มหมวดหมู่สำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        header("Location: categories.php");
        exit();
    }
    
    if (isset($_POST['edit_category'])) {
        $id = (int)$_POST['category_id'];
        $name = sanitize($_POST['category_name']);
        $description = sanitize($_POST['category_description']);
        
        $query = "UPDATE categories SET 
                  category_name = '$name',
                  category_description = '$description'
                  WHERE id = $id";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "แก้ไขหมวดหมู่สำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        header("Location: categories.php");
        exit();
    }
    
    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['category_id'];
        
        // Check if category has products
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id");
        $result = $check->fetch_assoc();
        
        if ($result['count'] == 0) {
            if ($conn->query("DELETE FROM categories WHERE id = $id")) {
                $_SESSION['success'] = "ลบหมวดหมู่สำเร็จ";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่ได้";
        }
        header("Location: categories.php");
        exit();
    }
}

// Get all categories
$categories = $conn->query("SELECT c.*, COUNT(p.id) as product_count 
                           FROM categories c 
                           LEFT JOIN products p ON c.id = p.category_id 
                           GROUP BY c.id 
                           ORDER BY c.created_at DESC");

// Get admin info
$admin = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
    <style>
        .category-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        .product-count {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--light);
            border-radius: 999px;
            font-size: 0.875rem;
            color: var(--secondary);
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
                    <a href="orders.php" class="menu-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>จัดการออเดอร์</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">จัดการระบบ</div>
                    <a href="categories.php" class="menu-item active">
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
                <h1 class="page-title">จัดการหมวดหมู่</h1>
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
            
            <!-- Action Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div class="search-box" style="flex: 1; margin-right: 1rem;">
                    <input type="text" id="searchInput" placeholder="ค้นหาหมวดหมู่...">
                    <button onclick="searchCategories()"><i class="fas fa-search"></i> ค้นหา</button>
                </div>
                <button class="btn btn-success" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> เพิ่มหมวดหมู่ใหม่
                </button>
            </div>
            
            <!-- Categories Grid/Table -->
            <div class="card">
                <div class="card-header">
                    <h3>รายการหมวดหมู่ทั้งหมด</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>ไอคอน</th>
                                    <th>ชื่อหมวดหมู่</th>
                                    <th>รายละเอียด</th>
                                    <th>จำนวนสินค้า</th>
                                    <th>วันที่สร้าง</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="category-icon">
                                                <i class="fas fa-tag"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['category_description'] ?: '-'); ?></td>
                                        <td>
                                            <span class="product-count">
                                                <?php echo $category['product_count']; ?> รายการ
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-primary btn-sm" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($category['product_count'] == 0): ?>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-danger btn-sm" disabled title="ไม่สามารถลบได้ เนื่องจากมีสินค้าอยู่">
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
    
    <!-- Add Category Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>เพิ่มหมวดหมู่ใหม่</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>ชื่อหมวดหมู่ *</label>
                        <input type="text" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>รายละเอียด</label>
                        <textarea name="category_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_category" class="btn btn-success">บันทึก</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>แก้ไขหมวดหมู่</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="category_id" id="edit_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>ชื่อหมวดหมู่ *</label>
                        <input type="text" name="category_name" id="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>รายละเอียด</label>
                        <textarea name="category_description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_category" class="btn btn-success">บันทึก</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    function showAddModal() {
        showModal('addModal');
    }
    
    function editCategory(category) {
        document.getElementById('edit_id').value = category.id;
        document.getElementById('edit_name').value = category.category_name;
        document.getElementById('edit_description').value = category.category_description;
        showModal('editModal');
    }
    
    function deleteCategory(id) {
        if (confirm('ต้องการลบหมวดหมู่นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="category_id" value="${id}"><input type="hidden" name="delete_category" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function searchCategories() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('categoriesTable');
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