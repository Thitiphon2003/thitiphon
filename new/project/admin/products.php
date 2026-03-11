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
            
            // Create folder if not exists
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $_SESSION['error'] = "รองรับเฉพาะไฟล์ JPG, PNG, GIF, WEBP เท่านั้น";
                header("Location: products.php");
                exit();
            }
            
            // Validate file size (max 2MB)
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = "ไฟล์รูปต้องมีขนาดไม่เกิน 2MB";
                header("Location: products.php");
                exit();
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $image;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $_SESSION['error'] = "ไม่สามารถอัปโหลดรูปภาพได้";
                header("Location: products.php");
                exit();
            }
        }
        
        $query = "INSERT INTO products (product_name, product_description, price, stock, image, category_id, store_id) 
                  VALUES ('$name', '$description', $price, $stock, '$image', $category_id, $store_id)";
        
        if ($conn->query($query)) {
            $_SESSION['success'] = "เพิ่มสินค้าสำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        header("Location: products.php");
        exit();
    }
    
    if (isset($_POST['edit_product'])) {
        $id = (int)$_POST['product_id'];
        $name = sanitize($_POST['product_name']);
        $description = sanitize($_POST['product_description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        $store_id = (int)$_POST['store_id'];
        $current_image = $_POST['current_image'];
        
        $image = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../assets/images/";
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $_SESSION['error'] = "รองรับเฉพาะไฟล์ JPG, PNG, GIF, WEBP เท่านั้น";
                header("Location: products.php");
                exit();
            }
            
            // Validate file size
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = "ไฟล์รูปต้องมีขนาดไม่เกิน 2MB";
                header("Location: products.php");
                exit();
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete old image from both folders
                if ($current_image) {
                    if (file_exists($target_dir . $current_image)) {
                        unlink($target_dir . $current_image);
                    }
                    if (file_exists("../assets/images/stores/" . $current_image)) {
                        unlink("../assets/images/stores/" . $current_image);
                    }
                }
                $image = $new_image;
            } else {
                $_SESSION['error'] = "ไม่สามารถอัปโหลดรูปภาพได้";
                header("Location: products.php");
                exit();
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
            $_SESSION['success'] = "แก้ไขสินค้าสำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        header("Location: products.php");
        exit();
    }
    
    if (isset($_POST['delete_product'])) {
        $id = (int)$_POST['product_id'];
        
        // Delete image file from both folders
        $img_query = $conn->query("SELECT image FROM products WHERE id = $id");
        if ($img_query && $img_query->num_rows > 0) {
            $img = $img_query->fetch_assoc();
            // Delete from images folder
            if ($img['image'] && file_exists("../assets/images/" . $img['image'])) {
                unlink("../assets/images/" . $img['image']);
            }
            // Delete from stores folder (just in case)
            if ($img['image'] && file_exists("../assets/images/stores/" . $img['image'])) {
                unlink("../assets/images/stores/" . $img['image']);
            }
        }
        
        if ($conn->query("DELETE FROM products WHERE id = $id")) {
            $_SESSION['success'] = "ลบสินค้าสำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        header("Location: products.php");
        exit();
    }
}

// Get all products
$products = $conn->query("SELECT p.*, c.category_name, s.store_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          LEFT JOIN stores s ON p.store_id = s.id 
                          ORDER BY p.created_at DESC");

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

// Get stores for dropdown
$stores = $conn->query("SELECT * FROM stores ORDER BY store_name");

// Get admin info
$admin = $conn->query("SELECT * FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin-style.css">
    <style>
        .product-image-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .no-image-thumb {
            width: 60px;
            height: 60px;
            background: var(--light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            font-size: 1.5rem;
        }
        .stock-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .stock-high {
            background: #d1fae5;
            color: #065f46;
        }
        .stock-medium {
            background: #fef3c7;
            color: #92400e;
        }
        .stock-low {
            background: #fee2e2;
            color: #991b1b;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filter-item {
            flex: 1;
            min-width: 150px;
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
                    <a href="products.php" class="menu-item active">
                        <i class="fas fa-box"></i>
                        <span>จัดการสินค้า</span>
                        <span class="badge"><?php echo $products->num_rows; ?></span>
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
                <h1 class="page-title">จัดการสินค้า</h1>
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
                <div class="filter-section">
                    <div class="filter-item">
                        <select class="form-control" id="categoryFilter" onchange="filterProducts()">
                            <option value="">ทั้งหมด</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <select class="form-control" id="storeFilter" onchange="filterProducts()">
                            <option value="">ทั้งหมด</option>
                            <?php 
                            $stores->data_seek(0);
                            while ($store = $stores->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $store['id']; ?>"><?php echo $store['store_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-success" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> เพิ่มสินค้าใหม่
                </button>
            </div>
            
            <!-- Search Box -->
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="ค้นหาสินค้า..." onkeyup="filterProducts()">
                <button onclick="filterProducts()"><i class="fas fa-search"></i> ค้นหา</button>
            </div>
            
            <!-- Products Table -->
            <div class="card">
                <div class="card-header">
                    <h3>รายการสินค้าทั้งหมด</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="productsTable">
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
                                    <tr data-category="<?php echo $product['category_id']; ?>" data-store="<?php echo $product['store_id']; ?>">
                                        <td>
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="../assets/images/<?php echo $product['image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                                     class="product-image-thumb"
                                                     onerror="this.onerror=null; this.src='../assets/images/stores/<?php echo $product['image']; ?>'; this.onerror=function(){this.style.display='none'; this.parentNode.innerHTML='<div class=\'no-image-thumb\'><i class=\'fas fa-image\'></i></div>';}">
                                            <?php else: ?>
                                                <div class="no-image-thumb">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                            <br>
                                            <small style="color: var(--secondary);"><?php echo substr($product['product_description'], 0, 50); ?>...</small>
                                        </td>
                                        <td><strong style="color: var(--primary);">฿<?php echo number_format($product['price'], 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $stock_class = 'stock-high';
                                            if ($product['stock'] < 5) {
                                                $stock_class = 'stock-low';
                                            } elseif ($product['stock'] < 20) {
                                                $stock_class = 'stock-medium';
                                            }
                                            ?>
                                            <span class="stock-badge <?php echo $stock_class; ?>">
                                                <?php echo $product['stock']; ?> ชิ้น
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['store_name']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-primary btn-sm" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
    
    <!-- Add Product Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>เพิ่มสินค้าใหม่</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>ชื่อสินค้า *</label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>ราคา *</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>จำนวนในสต็อก *</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>หมวดหมู่ *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">เลือกหมวดหมู่</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>ร้านค้า *</label>
                            <select name="store_id" class="form-control" required>
                                <option value="">เลือกร้านค้า</option>
                                <?php 
                                $stores->data_seek(0);
                                while ($store = $stores->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $store['id']; ?>"><?php echo $store['store_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>รายละเอียดสินค้า</label>
                            <textarea name="product_description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>รูปภาพสินค้า</label>
                            <div class="image-upload" onclick="document.getElementById('addImage').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>คลิกเพื่อเลือกรูปภาพ</p>
                                <input type="file" id="addImage" name="image" accept="image/*" style="display: none;" onchange="previewAddImage(this)">
                            </div>
                            <img id="addImagePreview" class="image-preview" style="display: none;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_product" class="btn btn-success">บันทึก</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>แก้ไขสินค้า</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>ชื่อสินค้า *</label>
                            <input type="text" name="product_name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>ราคา *</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>จำนวนในสต็อก *</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>หมวดหมู่ *</label>
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
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>ร้านค้า *</label>
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
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>รายละเอียดสินค้า</label>
                            <textarea name="product_description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>รูปภาพปัจจุบัน</label>
                            <div>
                                <img id="currentImageDisplay" class="image-preview" style="max-width: 150px; max-height: 150px; display: none;">
                            </div>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1/-1;">
                            <label>เปลี่ยนรูปภาพใหม่</label>
                            <div class="image-upload" onclick="document.getElementById('editImage').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>คลิกเพื่อเลือกรูปภาพใหม่</p>
                                <input type="file" id="editImage" name="image" accept="image/*" style="display: none;" onchange="previewEditImage(this)">
                            </div>
                            <img id="editImagePreview" class="image-preview" style="display: none;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_product" class="btn btn-success">บันทึก</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">ยกเลิก</button>
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
    
    function showAddModal() {
        document.getElementById('addModal').classList.add('active');
    }
    
    function checkImageExists(url, callback) {
        const img = new Image();
        img.onload = function() { callback(true); };
        img.onerror = function() { callback(false); };
        img.src = url;
    }
    
    function editProduct(product) {
        document.getElementById('edit_id').value = product.id;
        document.getElementById('edit_name').value = product.product_name;
        document.getElementById('edit_description').value = product.product_description;
        document.getElementById('edit_price').value = product.price;
        document.getElementById('edit_stock').value = product.stock;
        document.getElementById('edit_category').value = product.category_id;
        document.getElementById('edit_store').value = product.store_id;
        document.getElementById('edit_current_image').value = product.image;
        
        // Show current image
        const currentImageDisplay = document.getElementById('currentImageDisplay');
        if (product.image) {
            // Try images folder first
            const imagesPath = '../assets/images/' + product.image;
            const storesPath = '../assets/images/stores/' + product.image;
            
            checkImageExists(imagesPath, function(exists) {
                if (exists) {
                    currentImageDisplay.src = imagesPath;
                    currentImageDisplay.style.display = 'block';
                } else {
                    // Try stores folder
                    checkImageExists(storesPath, function(exists2) {
                        if (exists2) {
                            currentImageDisplay.src = storesPath;
                            currentImageDisplay.style.display = 'block';
                        } else {
                            currentImageDisplay.style.display = 'none';
                        }
                    });
                }
            });
        } else {
            currentImageDisplay.style.display = 'none';
        }
        
        document.getElementById('editImagePreview').style.display = 'none';
        document.getElementById('editImage').value = '';
        
        showModal('editModal');
    }
    
    function deleteProduct(id) {
        if (confirm('ต้องการลบสินค้านี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="product_id" value="${id}"><input type="hidden" name="delete_product" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function previewAddImage(input) {
        const preview = document.getElementById('addImagePreview');
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
        const preview = document.getElementById('editImagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function filterProducts() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const categoryFilter = document.getElementById('categoryFilter').value;
        const storeFilter = document.getElementById('storeFilter').value;
        const table = document.getElementById('productsTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let row of rows) {
            const category = row.getAttribute('data-category');
            const store = row.getAttribute('data-store');
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            // Check category and store filters
            if (categoryFilter && category != categoryFilter) {
                row.style.display = 'none';
                continue;
            }
            if (storeFilter && store != storeFilter) {
                row.style.display = 'none';
                continue;
            }
            
            // Check search text
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