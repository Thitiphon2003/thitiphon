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
    delete('products', 'id = ?', [$product_id]);
    $_SESSION['success'] = 'ลบสินค้าเรียบร้อยแล้ว';
    header('Location: admin_products.php');
    exit();
}

// จัดการการเพิ่ม/แก้ไขสินค้า
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', $name)));
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'];
        $seller_id = $_POST['seller_id'];
        $status = $_POST['status'];
        
        $sql = "INSERT INTO products (name, slug, price, stock, category_id, seller_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        query($sql, [$name, $slug, $price, $stock, $category_id, $seller_id, $status]);
        $message = 'เพิ่มสินค้าเรียบร้อยแล้ว';
    }
    
    if(isset($_POST['edit_product'])) {
        $product_id = $_POST['product_id'];
        $name = trim($_POST['name']);
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category_id = $_POST['category_id'];
        $seller_id = $_POST['seller_id'];
        $status = $_POST['status'];
        
        $sql = "UPDATE products SET name=?, price=?, stock=?, category_id=?, seller_id=?, status=? WHERE id=?";
        query($sql, [$name, $price, $stock, $category_id, $seller_id, $status, $product_id]);
        $message = 'อัปเดตสินค้าเรียบร้อยแล้ว';
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
        
        .logout-btn {
            padding: 0.5rem 1.5rem;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .btn-primary {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
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
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .product-thumb {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin: 0 2px;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
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
            </div>
            
            <div class="nav-menu">
                <a href="admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
                <a href="admin_users.php" class="nav-item"><i class="fas fa-users"></i> จัดการผู้ใช้</a>
                <a href="admin_products.php" class="nav-item active"><i class="fas fa-box"></i> จัดการสินค้า</a>
                <a href="admin_orders.php" class="nav-item"><i class="fas fa-shopping-cart"></i> จัดการออเดอร์</a>
                <a href="admin_categories.php" class="nav-item"><i class="fas fa-tags"></i> จัดการหมวดหมู่</a>
                <a href="admin_sellers.php" class="nav-item"><i class="fas fa-store"></i> จัดการร้านค้า</a>
                <a href="admin_settings.php" class="nav-item"><i class="fas fa-cog"></i> ตั้งค่า</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-box"></i> จัดการสินค้า</h1>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
            </div>
            
            <?php if($message): ?>
                <div class="alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="card-header">
                    <h2>รายการสินค้าทั้งหมด</h2>
                    <button class="btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> เพิ่มสินค้าใหม่
                    </button>
                </div>
                
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
                                    <i class="fas fa-image"></i>
                                </div>
                            </td>
                            <td><?php echo $product['name']; ?></td>
                            <td>฿<?php echo number_format($product['price']); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td><?php echo $product['category_name'] ?? '-'; ?></td>
                            <td><?php echo $product['seller_name'] ?? '-'; ?></td>
                            <td><?php echo $product['status']; ?></td>
                            <td>
                                <button class="action-btn btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มสินค้า -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>เพิ่มสินค้าใหม่</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>ชื่อสินค้า</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>ราคา</label>
                    <input type="number" name="price" required>
                </div>
                <div class="form-group">
                    <label>สต็อก</label>
                    <input type="number" name="stock" required>
                </div>
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
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active">ใช้งาน</option>
                        <option value="inactive">ไม่ใช้งาน</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_product" class="btn-success">บันทึก</button>
                    <button type="button" class="btn-danger" onclick="closeModal('addModal')">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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