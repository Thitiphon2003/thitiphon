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

// จัดการการลบสินค้า
if(isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // ลบรูปภาพก่อน
    $product = fetchOne("SELECT image FROM products WHERE id = ?", [$product_id]);
    if($product && !empty($product['image'])) {
        deleteImage($product['image'], 'products');
    }
    
    delete('products', 'id = ?', [$product_id]);
    $_SESSION['success'] = 'ลบสินค้าเรียบร้อยแล้ว';
    header('Location: admin_products.php');
    exit();
}

// จัดการการเพิ่มสินค้า
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'] ?: null;
        $seller_id = $_POST['seller_id'] ?: null;
        $status = $_POST['status'];
        $description = trim($_POST['description'] ?? '');
        $original_price = !empty($_POST['original_price']) ? $_POST['original_price'] : null;
        $image = '';
        
        // จัดการอัปโหลดรูป
        if(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['image'], 'products');
            if($upload['success']) {
                $image = $upload['filename'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if(empty($error)) {
            $sql = "INSERT INTO products (name, image, description, price, original_price, stock, category_id, seller_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            query($sql, [$name, $image, $description, $price, $original_price, $stock, $category_id, $seller_id, $status]);
            $message = 'เพิ่มสินค้าเรียบร้อยแล้ว';
        }
    }
    
    if(isset($_POST['edit_product'])) {
        $product_id = $_POST['product_id'];
        $name = trim($_POST['name']);
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'] ?: null;
        $seller_id = $_POST['seller_id'] ?: null;
        $status = $_POST['status'];
        $description = trim($_POST['description'] ?? '');
        $original_price = !empty($_POST['original_price']) ? $_POST['original_price'] : null;
        
        // ดึงรูปเก่า
        $old_product = fetchOne("SELECT image FROM products WHERE id = ?", [$product_id]);
        $image = $old_product['image'];
        
        // จัดการอัปโหลดรูปใหม่
        if(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['image'], 'products');
            if($upload['success']) {
                // ลบรูปเก่า
                if(!empty($old_product['image'])) {
                    deleteImage($old_product['image'], 'products');
                }
                $image = $upload['filename'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if(empty($error)) {
            $sql = "UPDATE products SET name=?, image=?, description=?, price=?, original_price=?, stock=?, category_id=?, seller_id=?, status=? WHERE id=?";
            query($sql, [$name, $image, $description, $price, $original_price, $stock, $category_id, $seller_id, $status, $product_id]);
            $message = 'อัปเดตสินค้าเรียบร้อยแล้ว';
        }
    }
}

// ดึงข้อมูลสินค้า
$products = fetchAll("SELECT p.*, c.name as category_name, s.name as seller_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN sellers s ON p.seller_id = s.id 
                      ORDER BY p.id DESC");

// ดึงข้อมูลหมวดหมู่และร้านค้า
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active'");
$sellers = fetchAll("SELECT * FROM sellers WHERE status = 'active'");

$edit_product = null;
if(isset($_GET['edit'])) {
    $edit_product = fetchOne("SELECT * FROM products WHERE id = ?", [$_GET['edit']]);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - SHOP.COM</title>
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .logout-btn:hover {
            background: #c82333;
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
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
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
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin: 0 2px;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin: 0 2px;
        }
        
        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        /* Image Preview */
        .image-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #e1e5e9;
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview i {
            color: #999;
            font-size: 2rem;
        }
        
        .product-thumb {
            width: 60px;
            height: 60px;
            border-radius: 5px;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Table */
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
            overflow-y: auto;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
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
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
            .form-row {
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
                <div class="admin-role">Administrator</div>
            </div>
            
            <div class="nav-menu">
                <a href="admin.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                </a>
                <a href="admin_users.php" class="nav-item">
                    <i class="fas fa-users"></i> จัดการผู้ใช้
                </a>
                <a href="admin_products.php" class="nav-item active">
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
                    <h1><i class="fas fa-box"></i> จัดการสินค้า</h1>
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
            
            <!-- ฟอร์มเพิ่มสินค้า -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> เพิ่มสินค้าใหม่</h2>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>ชื่อสินค้า <span style="color: red;">*</span></label>
                            <input type="text" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>ราคา <span style="color: red;">*</span></label>
                            <input type="number" name="price" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>ราคาเดิม (ก่อนลด)</label>
                            <input type="number" name="original_price">
                        </div>
                        
                        <div class="form-group">
                            <label>จำนวนคงเหลือ <span style="color: red;">*</span></label>
                            <input type="number" name="stock" value="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>หมวดหมู่</label>
                            <select name="category_id">
                                <option value="">ไม่มีหมวดหมู่</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>ร้านค้า</label>
                            <select name="seller_id">
                                <option value="">ไม่มีร้านค้า</option>
                                <?php foreach($sellers as $seller): ?>
                                    <option value="<?php echo $seller['id']; ?>"><?php echo $seller['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>รายละเอียดสินค้า</label>
                        <textarea name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>รูปภาพสินค้า</label>
                        <input type="file" name="image" id="productImage" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview" id="imagePreview">
                            <i class="fas fa-image"></i>
                        </div>
                        <small style="color: #999;">ขนาดไม่เกิน 5MB, รองรับ JPG, PNG, GIF, WEBP</small>
                    </div>
                    
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select name="status">
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_product" class="btn-primary">
                        <i class="fas fa-save"></i> บันทึกสินค้า
                    </button>
                </form>
            </div>
            
            <!-- รายการสินค้า -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> รายการสินค้าทั้งหมด (<?php echo count($products); ?>)</h2>
                </div>
                
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>รูป</th>
                                <th>ชื่อสินค้า</th>
                                <th>ราคา</th>
                                <th>สต็อก</th>
                                <th>หมวดหมู่</th>
                                <th>ร้านค้า</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($products)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem;">ไม่มีสินค้าในระบบ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="product-thumb">
                                            <img src="<?php echo showImage($product['image'], 'products', 'default-product.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>฿<?php echo number_format($product['price']); ?></td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td><?php echo $product['category_name'] ?? '-'; ?></td>
                                    <td><?php echo $product['seller_name'] ?? '-'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php echo $product['status'] == 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal แก้ไขสินค้า -->
    <?php if($edit_product): ?>
    <div id="editModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> แก้ไขสินค้า</h2>
                <span class="close" onclick="window.location.href='admin_products.php'">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อสินค้า</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>ราคา</label>
                        <input type="number" name="price" value="<?php echo $edit_product['price']; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ราคาเดิม</label>
                        <input type="number" name="original_price" value="<?php echo $edit_product['original_price']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>จำนวนคงเหลือ</label>
                        <input type="number" name="stock" value="<?php echo $edit_product['stock']; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <select name="category_id">
                            <option value="">ไม่มีหมวดหมู่</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $edit_product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>ร้านค้า</label>
                        <select name="seller_id">
                            <option value="">ไม่มีร้านค้า</option>
                            <?php foreach($sellers as $seller): ?>
                                <option value="<?php echo $seller['id']; ?>" <?php echo $edit_product['seller_id'] == $seller['id'] ? 'selected' : ''; ?>>
                                    <?php echo $seller['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียดสินค้า</label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>รูปภาพปัจจุบัน</label>
                    <div class="image-preview" style="margin-bottom: 10px;">
                        <img src="<?php echo showImage($edit_product['image'], 'products', 'default-product.jpg'); ?>" 
                             alt="current image">
                    </div>
                    <label>เปลี่ยนรูปภาพใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                    <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                </div>
                
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active" <?php echo $edit_product['status'] == 'active' ? 'selected' : ''; ?>>ใช้งาน</option>
                        <option value="inactive" <?php echo $edit_product['status'] == 'inactive' ? 'selected' : ''; ?>>ไม่ใช้งาน</option>
                    </select>
                </div>
                
                <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="submit" name="edit_product" class="btn-success">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                    <a href="admin_products.php" class="btn-danger" style="padding: 8px 16px; text-decoration: none; border-radius: 5px;">
                        <i class="fas fa-times"></i> ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '<i class="fas fa-image" style="color: #999; font-size: 2rem;"></i>';
            }
        }
        
        function editProduct(id) {
            window.location.href = 'admin_products.php?edit=' + id;
        }
        
        function deleteProduct(id) {
            if(confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')) {
                window.location.href = 'admin_products.php?delete=' + id;
            }
        }
    </script>
</body>
</html>