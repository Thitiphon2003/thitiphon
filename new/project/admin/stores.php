<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isAdmin()) {
    redirect('../login.php');
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
            
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // สร้างชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo = 'store_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $logo;
            
            // ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
            $check = getimagesize($_FILES['logo']['tmp_name']);
            if ($check !== false) {
                // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
                if ($_FILES['logo']['size'] <= 2 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                        // อัปโหลดสำเร็จ
                    } else {
                        $error = "ไม่สามารถอัปโหลดโลโก้ได้";
                    }
                } else {
                    $error = "ไฟล์รูปต้องมีขนาดไม่เกิน 2MB";
                }
            } else {
                $error = "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ";
            }
        }
        
        if (!isset($error)) {
            $query = "INSERT INTO stores (store_name, store_description, store_logo) 
                      VALUES ('$name', '$description', '$logo')";
            if ($conn->query($query)) {
                $success = "เพิ่มร้านค้าสำเร็จ";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
        
    } elseif (isset($_POST['edit_store'])) {
        $id = (int)$_POST['store_id'];
        $name = sanitize($_POST['store_name']);
        $description = sanitize($_POST['store_description']);
        $current_logo = $_POST['current_logo'];
        
        // Handle logo upload
        $logo = $current_logo; // ใช้โลโก้เดิมก่อน
        
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $target_dir = "../assets/images/stores/";
            
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // สร้างชื่อไฟล์ใหม่
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_logo = 'store_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_logo;
            
            // ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
            $check = getimagesize($_FILES['logo']['tmp_name']);
            if ($check !== false) {
                // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
                if ($_FILES['logo']['size'] <= 2 * 1024 * 1024) {
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                        // ลบโลโก้เก่าถ้ามี
                        if ($current_logo && file_exists($target_dir . $current_logo)) {
                            unlink($target_dir . $current_logo);
                        }
                        $logo = $new_logo; // เปลี่ยนเป็นโลโก้ใหม่
                    }
                } else {
                    $error = "ไฟล์รูปต้องมีขนาดไม่เกิน 2MB";
                }
            } else {
                $error = "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ";
            }
        }
        
        if (!isset($error)) {
            $query = "UPDATE stores SET 
                      store_name = '$name',
                      store_description = '$description',
                      store_logo = '$logo'
                      WHERE id = $id";
            
            if ($conn->query($query)) {
                $success = "แก้ไขร้านค้าสำเร็จ";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
        
    } elseif (isset($_POST['delete_store'])) {
        $id = (int)$_POST['store_id'];
        
        // Check if store has products
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE store_id = $id");
        $result = $check->fetch_assoc();
        
        if ($result['count'] == 0) {
            // ดึงชื่อโลโก้เพื่อลบไฟล์
            $logo_query = $conn->query("SELECT store_logo FROM stores WHERE id = $id");
            if ($logo_query && $logo_query->num_rows > 0) {
                $store = $logo_query->fetch_assoc();
                if ($store['store_logo'] && file_exists("../assets/images/stores/" . $store['store_logo'])) {
                    unlink("../assets/images/stores/" . $store['store_logo']);
                }
            }
            
            $query = "DELETE FROM stores WHERE id = $id";
            if ($conn->query($query)) {
                $success = "ลบร้านค้าสำเร็จ";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        } else {
            $error = "ไม่สามารถลบร้านค้าที่มีสินค้าอยู่ได้";
        }
    }
}

// Get all stores with product count
$stores = $conn->query("SELECT s.*, COUNT(p.id) as product_count 
                        FROM stores s 
                        LEFT JOIN products p ON s.id = p.store_id 
                        GROUP BY s.id 
                        ORDER BY s.created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการร้านค้า - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .store-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .no-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        .logo-preview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            padding: 2rem;
            border-radius: 10px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        .close-modal:hover {
            color: var(--primary-red);
        }
        .current-logo {
            text-align: center;
            margin: 1rem 0;
        }
        .current-logo img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2 style="padding: 0 1.5rem; margin-bottom: 2rem;">Admin Panel</h2>
            <a href="index.php">แดชบอร์ด</a>
            <a href="users.php">จัดการผู้ใช้</a>
            <a href="products.php">จัดการสินค้า</a>
            <a href="orders.php">จัดการออเดอร์</a>
            <a href="categories.php">จัดการหมวดหมู่</a>
            <a href="stores.php" style="background: var(--primary-blue);">จัดการร้านค้า</a>
            <a href="../logout.php">ออกจากระบบ</a>
        </div>
        
        <div class="admin-content">
            <h1>จัดการร้านค้า</h1>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <button onclick="openAddModal()" class="btn" style="margin-bottom: 1rem;">+ เพิ่มร้านค้าใหม่</button>
            
            <!-- Stores Table -->
            <table class="table">
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
                                    <div class="no-logo">
                                        <?php echo mb_substr($store['store_name'], 0, 1, 'utf-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $store['store_name']; ?></strong></td>
                            <td><?php echo $store['store_description']; ?></td>
                            <td><?php echo $store['product_count']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($store['created_at'])); ?></td>
                            <td>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($store)); ?>)" 
                                        class="btn">แก้ไข</button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('ลบร้านค้านี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')">
                                    <input type="hidden" name="store_id" value="<?php echo $store['id']; ?>">
                                    <button type="submit" name="delete_store" class="btn btn-red">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Store Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAddModal()">&times;</span>
            <h2>เพิ่มร้านค้าใหม่</h2>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateAddForm()">
                <div class="form-group">
                    <label>ชื่อร้านค้า <span style="color: red;">*</span></label>
                    <input type="text" name="store_name" id="add_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียดร้านค้า</label>
                    <textarea name="store_description" id="add_description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>โลโก้ร้านค้า</label>
                    <input type="file" name="logo" id="add_logo" class="form-control" accept="image/*" onchange="previewAddLogo(this)">
                    <div style="text-align: center;">
                        <img id="add_logo_preview" class="logo-preview" style="display: none;">
                    </div>
                    <small style="color: #666;">ขนาดไฟล์ไม่เกิน 2MB, รองรับ JPG, PNG, GIF</small>
                </div>
                
                <button type="submit" name="add_store" class="btn">บันทึก</button>
                <button type="button" onclick="closeAddModal()" class="btn btn-red">ยกเลิก</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Store Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h2>แก้ไขร้านค้า</h2>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateEditForm()">
                <input type="hidden" name="store_id" id="edit_id">
                <input type="hidden" name="current_logo" id="edit_current_logo">
                
                <div class="form-group">
                    <label>ชื่อร้านค้า <span style="color: red;">*</span></label>
                    <input type="text" name="store_name" id="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียดร้านค้า</label>
                    <textarea name="store_description" id="edit_description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label>โลโก้ปัจจุบัน</label>
                    <div class="current-logo">
                        <img id="current_logo_display" class="logo-preview" style="max-width: 100px;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>เปลี่ยนโลโก้ใหม่ (ถ้าต้องการ)</label>
                    <input type="file" name="logo" id="edit_logo" class="form-control" accept="image/*" onchange="previewEditLogo(this)">
                    <div style="text-align: center;">
                        <img id="edit_logo_preview" class="logo-preview" style="display: none;">
                    </div>
                    <small style="color: #666;">ขนาดไฟล์ไม่เกิน 2MB, รองรับ JPG, PNG, GIF</small>
                </div>
                
                <button type="submit" name="edit_store" class="btn">บันทึก</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-red">ปิด</button>
            </form>
        </div>
    </div>
    
    <script>
    // Add Modal Functions
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
        document.getElementById('add_name').value = '';
        document.getElementById('add_description').value = '';
        document.getElementById('add_logo').value = '';
        document.getElementById('add_logo_preview').style.display = 'none';
    }
    
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }
    
    // Edit Modal Functions
    function openEditModal(store) {
        document.getElementById('edit_id').value = store.id;
        document.getElementById('edit_name').value = store.store_name;
        document.getElementById('edit_description').value = store.store_description;
        document.getElementById('edit_current_logo').value = store.store_logo;
        
        // Show current logo
        const currentLogoDisplay = document.getElementById('current_logo_display');
        if (store.store_logo) {
            currentLogoDisplay.src = '../assets/images/stores/' + store.store_logo;
            currentLogoDisplay.style.display = 'inline-block';
        } else {
            currentLogoDisplay.style.display = 'none';
        }
        
        // Hide preview
        document.getElementById('edit_logo_preview').style.display = 'none';
        document.getElementById('edit_logo').value = '';
        
        document.getElementById('editModal').style.display = 'block';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Image Preview Functions
    function previewAddLogo(input) {
        const preview = document.getElementById('add_logo_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'inline-block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function previewEditLogo(input) {
        const preview = document.getElementById('edit_logo_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'inline-block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Form Validation
    function validateAddForm() {
        const name = document.getElementById('add_name').value.trim();
        if (!name) {
            alert('กรุณากรอกชื่อร้านค้า');
            return false;
        }
        return true;
    }
    
    function validateEditForm() {
        const name = document.getElementById('edit_name').value.trim();
        if (!name) {
            alert('กรุณากรอกชื่อร้านค้า');
            return false;
        }
        return true;
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeAddModal();
            closeEditModal();
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>