<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['product_name']);
        $description = sanitize($_POST['product_description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        $store_id = (int)$_POST['store_id'];
        
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../assets/images/";
            
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // สร้างชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $image;
            
            // ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // อัปโหลดสำเร็จ
                } else {
                    $error = "ไม่สามารถอัปโหลดรูปภาพได้";
                }
            } else {
                $error = "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ";
            }
        }
        
        if (!isset($error)) {
            $query = "INSERT INTO products (product_name, product_description, price, stock, image, category_id, store_id) 
                      VALUES ('$name', '$description', $price, $stock, '$image', $category_id, $store_id)";
            if ($conn->query($query)) {
                $success = "เพิ่มสินค้าสำเร็จ";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
        
    } elseif (isset($_POST['edit_product'])) {
        $id = (int)$_POST['product_id'];
        $name = sanitize($_POST['product_name']);
        $description = sanitize($_POST['product_description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        $store_id = (int)$_POST['store_id'];
        $current_image = $_POST['current_image'];
        
        // Handle image upload
        $image = $current_image; // ใช้รูปเดิมก่อน
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../assets/images/";
            
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // สร้างชื่อไฟล์ใหม่
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_image;
            
            // ตรวจสอบว่าเป็นไฟล์รูปภาพจริง
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // ลบรูปเก่าถ้ามี
                    if ($current_image && file_exists($target_dir . $current_image)) {
                        unlink($target_dir . $current_image);
                    }
                    $image = $new_image; // เปลี่ยนเป็นรูปใหม่
                }
            }
        }
        
        $query = "UPDATE products SET 
                  product_name = '$name',
                  product_description = '$description',
                  price = $price,
                  stock = $stock,
                  image = '$image',
                  category_id = $category_id,
                  store_id = $store_id
                  WHERE id = $id";
        
        if ($conn->query($query)) {
            $success = "แก้ไขสินค้าสำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        
    } elseif (isset($_POST['delete_product'])) {
        $id = (int)$_POST['product_id'];
        
        // ดึงชื่อรูปเพื่อลบไฟล์
        $img_query = $conn->query("SELECT image FROM products WHERE id = $id");
        if ($img_query && $img_query->num_rows > 0) {
            $img = $img_query->fetch_assoc();
            if ($img['image'] && file_exists("../assets/images/" . $img['image'])) {
                unlink("../assets/images/" . $img['image']);
            }
        }
        
        $query = "DELETE FROM products WHERE id = $id";
        if ($conn->query($query)) {
            $success = "ลบสินค้าสำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// Get all products with joins
$products = $conn->query("SELECT p.*, c.category_name, s.store_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          LEFT JOIN stores s ON p.store_id = s.id 
                          ORDER BY p.created_at DESC");

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

// Get stores for dropdown
$stores = $conn->query("SELECT * FROM stores ORDER BY store_name");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .no-image {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #999;
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
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2 style="padding: 0 1.5rem; margin-bottom: 2rem;">Admin Panel</h2>
            <a href="index.php">แดชบอร์ด</a>
            <a href="users.php">จัดการผู้ใช้</a>
            <a href="products.php" style="background: var(--primary-blue);">จัดการสินค้า</a>
            <a href="orders.php">จัดการออเดอร์</a>
            <a href="categories.php">จัดการหมวดหมู่</a>
            <a href="stores.php">จัดการร้านค้า</a>
            <a href="../logout.php">ออกจากระบบ</a>
        </div>
        
        <div class="admin-content">
            <h1>จัดการสินค้า</h1>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <button onclick="openAddModal()" class="btn" style="margin-bottom: 1rem;">+ เพิ่มสินค้าใหม่</button>
            
            <!-- Products Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อสินค้า</th>
                        <th>ราคา</th>
                        <th>สต็อก</th>
                        <th>หมวดหมู่</th>
                        <th>ร้านค้า</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($product['image'] && file_exists("../assets/images/" . $product['image'])): ?>
                                    <img src="../assets/images/<?php echo $product['image']; ?>" 
                                         alt="<?php echo $product['product_name']; ?>" 
                                         class="product-thumbnail">
                                <?php else: ?>
                                    <div class="no-image">ไม่มีรูป</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $product['product_name']; ?></td>
                            <td>฿<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td><?php echo $product['category_name']; ?></td>
                            <td><?php echo $product['store_name']; ?></td>
                            <td>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                        class="btn">แก้ไข</button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('ลบสินค้านี้?')">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-red">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAddModal()">&times;</span>
            <h2>เพิ่มสินค้าใหม่</h2>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-group">
                    <label>ชื่อสินค้า <span style="color: red;">*</span></label>
                    <input type="text" name="product_name" id="add_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea name="product_description" id="add_description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>ราคา <span style="color: red;">*</span></label>
                    <input type="number" name="price" id="add_price" class="form-control" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>จำนวนในสต็อก <span style="color: red;">*</span></label>
                    <input type="number" name="stock" id="add_stock" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>หมวดหมู่ <span style="color: red;">*</span></label>
                    <select name="category_id" id="add_category" class="form-control" required>
                        <option value="">เลือกหมวดหมู่</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>ร้านค้า <span style="color: red;">*</span></label>
                    <select name="store_id" id="add_store" class="form-control" required>
                        <option value="">เลือกร้านค้า</option>
                        <?php 
                        $stores->data_seek(0);
                        while ($store = $stores->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $store['id']; ?>"><?php echo $store['store_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>รูปภาพสินค้า</label>
                    <input type="file" name="image" id="add_image" class="form-control" accept="image/*" onchange="previewAddImage(this)">
                    <img id="add_image_preview" class="image-preview" style="display: none;">
                </div>
                
                <button type="submit" name="add_product" class="btn">บันทึก</button>
                <button type="button" onclick="closeAddModal()" class="btn btn-red">ยกเลิก</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h2>แก้ไขสินค้า</h2>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateEditForm()">
                <input type="hidden" name="product_id" id="edit_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                
                <div class="form-group">
                    <label>ชื่อสินค้า <span style="color: red;">*</span></label>
                    <input type="text" name="product_name" id="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea name="product_description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>ราคา <span style="color: red;">*</span></label>
                    <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>จำนวนในสต็อก <span style="color: red;">*</span></label>
                    <input type="number" name="stock" id="edit_stock" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>หมวดหมู่ <span style="color: red;">*</span></label>
                    <select name="category_id" id="edit_category" class="form-control" required>
                        <option value="">เลือกหมวดหมู่</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>ร้านค้า <span style="color: red;">*</span></label>
                    <select name="store_id" id="edit_store" class="form-control" required>
                        <option value="">เลือกร้านค้า</option>
                        <?php 
                        $stores->data_seek(0);
                        while ($store = $stores->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $store['id']; ?>"><?php echo $store['store_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>รูปภาพปัจจุบัน</label>
                    <div>
                        <img id="current_image_display" class="image-preview" style="max-width: 200px;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>อัปโหลดรูปภาพใหม่ (ถ้าต้องการเปลี่ยน)</label>
                    <input type="file" name="image" id="edit_image" class="form-control" accept="image/*" onchange="previewEditImage(this)">
                    <img id="edit_image_preview" class="image-preview" style="display: none;">
                </div>
                
                <button type="submit" name="edit_product" class="btn">บันทึก</button>
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
        document.getElementById('add_price').value = '';
        document.getElementById('add_stock').value = '';
        document.getElementById('add_category').value = '';
        document.getElementById('add_store').value = '';
        document.getElementById('add_image').value = '';
        document.getElementById('add_image_preview').style.display = 'none';
    }
    
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }
    
    // Edit Modal Functions
    function openEditModal(product) {
        document.getElementById('edit_id').value = product.id;
        document.getElementById('edit_name').value = product.product_name;
        document.getElementById('edit_description').value = product.product_description;
        document.getElementById('edit_price').value = product.price;
        document.getElementById('edit_stock').value = product.stock;
        document.getElementById('edit_category').value = product.category_id;
        document.getElementById('edit_store').value = product.store_id;
        document.getElementById('edit_current_image').value = product.image;
        
        // Show current image
        const currentImageDisplay = document.getElementById('current_image_display');
        if (product.image) {
            currentImageDisplay.src = '../assets/images/' + product.image;
            currentImageDisplay.style.display = 'block';
        } else {
            currentImageDisplay.style.display = 'none';
        }
        
        // Hide preview
        document.getElementById('edit_image_preview').style.display = 'none';
        document.getElementById('edit_image').value = '';
        
        document.getElementById('editModal').style.display = 'block';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Image Preview Functions
    function previewAddImage(input) {
        const preview = document.getElementById('add_image_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function previewEditImage(input) {
        const preview = document.getElementById('edit_image_preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Form Validation
    function validateForm() {
        const name = document.getElementById('add_name').value;
        const price = document.getElementById('add_price').value;
        const stock = document.getElementById('add_stock').value;
        const category = document.getElementById('add_category').value;
        const store = document.getElementById('add_store').value;
        
        if (!name || !price || !stock || !category || !store) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return false;
        }
        return true;
    }
    
    function validateEditForm() {
        const name = document.getElementById('edit_name').value;
        const price = document.getElementById('edit_price').value;
        const stock = document.getElementById('edit_stock').value;
        const category = document.getElementById('edit_category').value;
        const store = document.getElementById('edit_store').value;
        
        if (!name || !price || !stock || !category || !store) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
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