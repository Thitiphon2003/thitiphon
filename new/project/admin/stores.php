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

// Handle store actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_store'])) {
        $name = sanitize($_POST['store_name']);
        $description = sanitize($_POST['store_description']);
        
        // Handle logo upload
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $target_dir = "../assets/images/stores/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo = 'store_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $logo;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                // Success
            }
        }
        
        $query = "INSERT INTO stores (store_name, store_description, store_logo) 
                  VALUES ('$name', '$description', '$logo')";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "เพิ่มร้านค้าสำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        header("Location: stores.php");
        exit();
    }
    
    if (isset($_POST['edit_store'])) {
        $id = (int)$_POST['store_id'];
        $name = sanitize($_POST['store_name']);
        $description = sanitize($_POST['store_description']);
        $current_logo = $_POST['current_logo'];
        
        $logo = $current_logo;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $target_dir = "../assets/images/stores/";
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_logo = 'store_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_logo;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                if ($current_logo && file_exists($target_dir . $current_logo)) {
                    unlink($target_dir . $current_logo);
                }
                $logo = $new_logo;
            }
        }
        
        $query = "UPDATE stores SET 
                  store_name = '$name',
                  store_description = '$description',
                  store_logo = '$logo'
                  WHERE id = $id";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "แก้ไขร้านค้าสำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        header("Location: stores.php");
        exit();
    }
    
    if (isset($_POST['delete_store'])) {
        $id = (int)$_POST['store_id'];
        
        // Check if store has products
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE store_id = $id");
        $result = $check->fetch_assoc();
        
        if ($result['count'] == 0) {
            // Delete logo file
            $logo_query = $conn->query("SELECT store_logo FROM stores WHERE id = $id");
            if ($logo_query && $logo_query->num_rows > 0) {
                $store = $logo_query->fetch_assoc();
                if ($store['store_logo'] && file_exists("../assets/images/stores/" . $store['store_logo'])) {
                    unlink("../assets/images/stores/" . $store['store_logo']);
                }
            }
            
            if ($conn->query("DELETE FROM stores WHERE id = $id")) {
                $_SESSION['success'] = "ลบร้านค้าสำเร็จ";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "ไม่สามารถลบร้านค้าที่มีสินค้าอยู่ได้";
        }
        header("Location: stores.php");
        exit();
    }
}

// Get all stores
$stores = $conn->query("SELECT s.*, COUNT(p.id) as product_count 
                        FROM stores s 
                        LEFT JOIN products p ON s.id = p.store_id 
                        GROUP BY s.id 
                        ORDER BY s.created_at DESC");

// Get admin info
$admin = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการร้านค้า - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
    <style>
        .store-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .store-logo-placeholder {
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
        .product-count-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--light);
            border-radius: 999px;
            font-size: 0.875rem;
            color: var(--secondary);
        }
        .logo-preview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
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
                    <a href="categories.php" class="menu-item">
                        <i class="fas fa-tags"></i>
                        <span>จัดการหมวดหมู่</span>
                    </a>
                    <a href="stores.php" class="menu-item active">
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
                <h1 class="page-title">จัดการร้านค้า</h1>
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
                    <input type="text" id="searchInput" placeholder="ค้นหาร้านค้า...">
                    <button onclick="searchStores()"><i class="fas fa-search"></i> ค้นหา</button>
                </div>
                <button class="btn btn-success" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> เพิ่มร้านค้าใหม่
                </button>
            </div>
            
            <!-- Stores Table -->
            <div class="card">
                <div class="card-header">
                    <h3>รายการร้านค้าทั้งหมด</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="storesTable">
                            <thead>
                                <tr>
                                    <th>โลโก้</th>
                                    <th>ชื่อร้านค้า</th>
                                    <th>รายละเอียด</th>
                                    <th>จำนวนสินค้า</th>
                                    <th>วันที่สร้าง</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($store = $stores->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if ($store['store_logo'] && file_exists("../assets/images/stores/" . $store['store_logo'])): ?>
                                                <img src="../assets/images/stores/<?php echo $store['store_logo']; ?>" 
                                                     alt="<?php echo $store['store_name']; ?>" 
                                                     class="store-logo">
                                            <?php else: ?>
                                                <div class="store-logo-placeholder">
                                                    <i class="fas fa-store"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($store['store_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($store['store_description'] ?: '-'); ?></td>
                                        <td>
                                            <span class="product-count-badge">
                                                <?php echo $store['product_count']; ?> รายการ
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($store['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-primary btn-sm" onclick="editStore(<?php echo htmlspecialchars(json_encode($store)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($store['product_count'] == 0): ?>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteStore(<?php echo $store['id']; ?>)">
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
    
    <!-- Add Store Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>เพิ่มร้านค้าใหม่</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>ชื่อร้านค้า *</label>
                        <input type="text" name="store_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>รายละเอียดร้านค้า</label>
                        <textarea name="store_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>โลโก้ร้านค้า</label>
                        <div class="image-upload" onclick="document.getElementById('addLogo').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>คลิกเพื่อเลือกรูปภาพ</p>
                            <input type="file" id="addLogo" name="logo" accept="image/*" style="display: none;" onchange="previewAddLogo(this)">
                        </div>
                        <img id="addLogoPreview" class="logo-preview" style="display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_store" class="btn btn-success">บันทึก</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Store Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>แก้ไขร้านค้า</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="store_id" id="edit_id">
                <input type="hidden" name="current_logo" id="edit_current_logo">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>ชื่อร้านค้า *</label>
                        <input type="text" name="store_name" id="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>รายละเอียดร้านค้า</label>
                        <textarea name="store_description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>โลโก้ปัจจุบัน</label>
                        <img id="currentLogoDisplay" class="logo-preview" style="max-width: 100px;">
                    </div>
                    
                    <div class="form-group">
                        <label>เปลี่ยนโลโก้ใหม่</label>
                        <div class="image-upload" onclick="document.getElementById('editLogo').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>คลิกเพื่อเลือกรูปภาพใหม่</p>
                            <input type="file" id="editLogo" name="logo" accept="image/*" style="display: none;" onchange="previewEditLogo(this)">
                        </div>
                        <img id="editLogoPreview" class="logo-preview" style="display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_store" class="btn btn-success">บันทึก</button>
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
    
    function editStore(store) {
        document.getElementById('edit_id').value = store.id;
        document.getElementById('edit_name').value = store.store_name;
        document.getElementById('edit_description').value = store.store_description;
        document.getElementById('edit_current_logo').value = store.store_logo;
        
        if (store.store_logo) {
            document.getElementById('currentLogoDisplay').src = '../assets/images/stores/' + store.store_logo;
            document.getElementById('currentLogoDisplay').style.display = 'block';
        } else {
            document.getElementById('currentLogoDisplay').style.display = 'none';
        }
        
        document.getElementById('editLogoPreview').style.display = 'none';
        document.getElementById('editLogo').value = '';
        
        showModal('editModal');
    }
    
    function deleteStore(id) {
        if (confirm('ต้องการลบร้านค้านี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="store_id" value="${id}"><input type="hidden" name="delete_store" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function previewAddLogo(input) {
        const preview = document.getElementById('addLogoPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function previewEditLogo(input) {
        const preview = document.getElementById('editLogoPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function searchStores() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('storesTable');
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