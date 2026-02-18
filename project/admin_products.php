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

// จัดการการเพิ่ม/แก้ไขสินค้า
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // เพิ่มสินค้าใหม่
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
            $_SESSION['success'] = 'เพิ่มสินค้าเรียบร้อยแล้ว';
            header('Location: admin_products.php');
            exit();
        }
    }
    
    // แก้ไขสินค้า
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
            $_SESSION['success'] = 'อัปเดตสินค้าเรียบร้อยแล้ว';
            header('Location: admin_products.php');
            exit();
        }
    }
}

// ดึงข้อมูลสินค้าทั้งหมด
$products = fetchAll("SELECT p.*, c.name as category_name, s.name as seller_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN sellers s ON p.seller_id = s.id 
                      ORDER BY p.id DESC");

// ดึงข้อมูลหมวดหมู่และร้านค้า
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active'");
$sellers = fetchAll("SELECT * FROM sellers WHERE status = 'active'");

// ดึงข้อมูลสินค้าสำหรับแก้ไข
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
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #f8fafc;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #0f172a;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-header p {
            color: #94a3b8;
            font-size: 0.9rem;
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
            background: #334155;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
        
        .admin-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .admin-role {
            color: #94a3b8;
            font-size: 0.8rem;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .nav-item:hover {
            background: #1e293b;
            color: white;
        }
        
        .nav-item.active {
            background: #1e293b;
            color: white;
            border-left: 4px solid #3b82f6;
        }
        
        .nav-item i {
            width: 20px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .top-bar {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .page-title h1 {
            font-size: 1.5rem;
            color: #0f172a;
        }
        
        .logout-btn {
            padding: 0.75rem 1.5rem;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .logout-btn:hover {
            background: #dc2626;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .card-header h2 {
            font-size: 1.3rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            padding: 0.75rem 1.5rem;
            background: #0f172a;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background: #1e293b;
        }
        
        .btn-success {
            padding: 0.5rem 1rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }
        
        .btn-warning {
            padding: 0.5rem 1rem;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }
        
        .btn-danger {
            padding: 0.5rem 1rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }
        
        .btn-edit, .btn-delete {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 0 0.2rem;
        }
        
        .btn-edit {
            background: #f59e0b;
            color: white;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0f172a;
            box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1);
        }
        
        /* Image Preview */
        .image-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8fafc;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview i {
            color: #94a3b8;
            font-size: 2rem;
        }
        
        .product-thumb {
            width: 60px;
            height: 60px;
            border-radius: 0.375rem;
            overflow: hidden;
            background: #f8fafc;
        }
        
        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 700px;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .modal-header h2 {
            font-size: 1.3rem;
            color: #0f172a;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }
        
        .close:hover {
            color: #0f172a;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
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
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- ปุ่มเพิ่มสินค้า -->
            <div style="margin-bottom: 1rem;">
                <button class="btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> เพิ่มสินค้าใหม่
                </button>
            </div>
            
            <!-- ตารางแสดงสินค้า -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> รายการสินค้าทั้งหมด (<?php echo count($products); ?>)</h2>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
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
                            <?php foreach($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <div class="product-thumb">
                                        <img src="<?php echo showImage($product['image'], 'products', 'default-product.jpg'); ?>" 
                                             alt="<?php echo $product['name']; ?>">
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
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>
                                    <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มสินค้า -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> เพิ่มสินค้าใหม่</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
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
                    <textarea name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>รูปภาพสินค้า</label>
                    <input type="file" name="image" id="addImage" accept="image/*" onchange="previewImage(this, 'addPreview')">
                    <div class="image-preview" id="addPreview">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active">ใช้งาน</option>
                        <option value="inactive">ไม่ใช้งาน</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_product" class="btn-success">บันทึกสินค้า</button>
                    <button type="button" class="btn-danger" onclick="closeModal('addModal')">ยกเลิก</button>
                </div>
            </form>
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
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>รูปภาพปัจจุบัน</label>
                    <div class="image-preview" style="margin-bottom: 1rem;">
                        <img src="<?php echo showImage($edit_product['image'], 'products', 'default-product.jpg'); ?>" alt="current">
                    </div>
                    <label>เปลี่ยนรูปภาพใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                    <input type="file" name="image" id="editImage" accept="image/*" onchange="previewImage(this, 'editPreview')">
                    <div class="image-preview" id="editPreview">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active" <?php echo $edit_product['status'] == 'active' ? 'selected' : ''; ?>>ใช้งาน</option>
                        <option value="inactive" <?php echo $edit_product['status'] == 'inactive' ? 'selected' : ''; ?>>ไม่ใช้งาน</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_product" class="btn-success">บันทึกการเปลี่ยนแปลง</button>
                    <a href="admin_products.php" class="btn-danger" style="padding: 0.75rem 1.5rem; text-decoration: none;">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // แสดง modal เพิ่มสินค้า
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        // ปิด modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // แก้ไขสินค้า
        function editProduct(id) {
            window.location.href = 'admin_products.php?edit=' + id;
        }
        
        // ลบสินค้า
        function deleteProduct(id) {
            if(confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')) {
                window.location.href = 'admin_products.php?delete=' + id;
            }
        }
        
        // แสดงตัวอย่างรูปภาพ
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '<i class="fas fa-image"></i>';
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