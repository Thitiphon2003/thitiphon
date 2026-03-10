<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize($_POST['category_name']);
        $description = sanitize($_POST['category_description']);
        
        $query = "INSERT INTO categories (category_name, category_description) 
                  VALUES ('$name', '$description')";
        $conn->query($query);
        
    } elseif (isset($_POST['edit_category'])) {
        $id = (int)$_POST['category_id'];
        $name = sanitize($_POST['category_name']);
        $description = sanitize($_POST['category_description']);
        
        $query = "UPDATE categories SET 
                  category_name = '$name',
                  category_description = '$description'
                  WHERE id = $id";
        $conn->query($query);
        
    } elseif (isset($_POST['delete_category'])) {
        $id = (int)$_POST['category_id'];
        
        // Check if category has products
        $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id");
        $result = $check->fetch_assoc();
        
        if ($result['count'] == 0) {
            $query = "DELETE FROM categories WHERE id = $id";
            $conn->query($query);
            $success = "ลบหมวดหมู่สำเร็จ";
        } else {
            $error = "ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่ได้";
        }
    }
}

// Get all categories
$categories = $conn->query("SELECT c.*, COUNT(p.id) as product_count 
                           FROM categories c 
                           LEFT JOIN products p ON c.id = p.category_id 
                           GROUP BY c.id 
                           ORDER BY c.created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
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
            <a href="users.php">จัดการผู้ใช้</a>
            <a href="products.php">จัดการสินค้า</a>
            <a href="orders.php">จัดการออเดอร์</a>
            <a href="categories.php" style="background: var(--primary-blue);">จัดการหมวดหมู่</a>
            <a href="stores.php">จัดการร้านค้า</a>
            <a href="../logout.php">ออกจากระบบ</a>
        </div>
        
        <div class="admin-content">
            <h1>จัดการหมวดหมู่</h1>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <button onclick="showAddForm()" class="btn" style="margin-bottom: 1rem;">+ เพิ่มหมวดหมู่ใหม่</button>
            
            <!-- Add Category Form -->
            <div id="addForm" style="display: none; background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h3>เพิ่มหมวดหมู่ใหม่</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>ชื่อหมวดหมู่</label>
                        <input type="text" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>รายละเอียด</label>
                        <textarea name="category_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="add_category" class="btn">บันทึก</button>
                    <button type="button" onclick="hideAddForm()" class="btn btn-red">ยกเลิก</button>
                </form>
            </div>
            
            <!-- Categories Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
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
                            <td><?php echo $category['id']; ?></td>
                            <td><?php echo $category['category_name']; ?></td>
                            <td><?php echo $category['category_description']; ?></td>
                            <td><?php echo $category['product_count']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                            <td>
                                <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                        class="btn">แก้ไข</button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('ลบหมวดหมู่นี้?')">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-red">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; width: 90%; max-width: 500px; margin: 50px auto; padding: 2rem; border-radius: 10px;">
            <h3>แก้ไขหมวดหมู่</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="category_id" id="edit_id">
                
                <div class="form-group">
                    <label>ชื่อหมวดหมู่</label>
                    <input type="text" name="category_name" id="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea name="category_description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" name="edit_category" class="btn">บันทึก</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-red">ปิด</button>
            </form>
        </div>
    </div>
    
    <script>
    function showAddForm() {
        document.getElementById('addForm').style.display = 'block';
    }
    
    function hideAddForm() {
        document.getElementById('addForm').style.display = 'none';
    }
    
    function editCategory(category) {
        document.getElementById('edit_id').value = category.id;
        document.getElementById('edit_name').value = category.category_name;
        document.getElementById('edit_description').value = category.category_description;
        document.getElementById('editModal').style.display = 'block';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>