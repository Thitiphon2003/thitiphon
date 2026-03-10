<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบแอดมิน
if(!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// ตรวจสอบว่าเป็นแอดมินจริง
$admin = fetchOne("SELECT * FROM users WHERE id = ? AND is_admin = 1", [$_SESSION['admin_id']]);
if(!$admin) {
    session_destroy();
    header('Location: admin_login.php?error=unauthorized');
    exit();
}

// ตัวแปรสำหรับข้อความแจ้งเตือน
$success_message = '';
$error_message = '';

// ============================================
// จัดการการลบสินค้า
// ============================================
if(isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    try {
        // เริ่ม transaction
        $pdo->beginTransaction();
        
        // ลบรูปภาพ
        deleteProductImage($product_id);
        
        // ลบข้อมูลสินค้า
        $sql = "DELETE FROM products WHERE id = ?";
        query($sql, [$product_id]);
        
        $pdo->commit();
        $_SESSION['success'] = 'ลบสินค้าเรียบร้อยแล้ว';
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
    
    header('Location: admin_products.php');
    exit();
}

// ============================================
// จัดการการเพิ่มสินค้าใหม่
// ============================================
if(isset($_POST['add_product'])) {
    try {
        // รับข้อมูลจากฟอร์ม
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $seller_id = !empty($_POST['seller_id']) ? intval($_POST['seller_id']) : null;
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        // ตรวจสอบข้อมูลที่จำเป็น
        $errors = [];
        if(empty($name)) $errors[] = 'กรุณากรอกชื่อสินค้า';
        if($price <= 0) $errors[] = 'กรุณากรอกราคาที่ถูกต้อง';
        if($stock < 0) $errors[] = 'กรุณากรอกจำนวนคงเหลือที่ถูกต้อง';
        
        if(empty($errors)) {
            // สร้าง slug จากชื่อสินค้า
            $slug = createSlug($name);
            
            // ตรวจสอบ slug ซ้ำ
            $check_slug = fetchOne("SELECT id FROM products WHERE slug = ?", [$slug]);
            if($check_slug) {
                $slug = $slug . '-' . time();
            }
            
            // บันทึกข้อมูลสินค้าก่อนเพื่อให้ได้ ID
            $sql = "INSERT INTO products (name, slug, description, price, original_price, stock, category_id, seller_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            query($sql, [
                $name,
                $slug,
                $description,
                $price,
                $original_price,
                $stock,
                $category_id,
                $seller_id,
                $status
            ]);
            
            $new_product_id = $pdo->lastInsertId();
            
            // จัดการอัปโหลดรูปภาพ (ใช้ ID เป็นชื่อไฟล์)
            if(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $upload_result = uploadProductImage($_FILES['image'], $new_product_id);
                if(!$upload_result['success']) {
                    $errors[] = 'อัปโหลดรูปไม่สำเร็จ: ' . $upload_result['message'];
                }
            }
            
            if(empty($errors)) {
                $_SESSION['success'] = 'เพิ่มสินค้า "' . $name . '" เรียบร้อยแล้ว (ID: ' . $new_product_id . ')';
                header('Location: admin_products.php');
                exit();
            } else {
                // ถ้ามี error ในการอัปโหลดรูป แต่เพิ่มสินค้าไปแล้ว ให้ลบสินค้านั้นทิ้ง
                query("DELETE FROM products WHERE id = ?", [$new_product_id]);
                $error_message = implode('<br>', $errors);
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
        
    } catch(Exception $e) {
        $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// ============================================
// จัดการการแก้ไขสินค้า
// ============================================
if(isset($_POST['edit_product'])) {
    try {
        $product_id = intval($_POST['product_id']);
        
        // รับข้อมูลจากฟอร์ม
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $seller_id = !empty($_POST['seller_id']) ? intval($_POST['seller_id']) : null;
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        // ตรวจสอบข้อมูล
        $errors = [];
        if(empty($name)) $errors[] = 'กรุณากรอกชื่อสินค้า';
        if($price <= 0) $errors[] = 'กรุณากรอกราคาที่ถูกต้อง';
        if($stock < 0) $errors[] = 'กรุณากรอกจำนวนคงเหลือที่ถูกต้อง';
        
        // ตรวจสอบว่าสินค้ามีอยู่จริง
        $old_product = fetchOne("SELECT * FROM products WHERE id = ?", [$product_id]);
        if(!$old_product) {
            throw new Exception('ไม่พบสินค้าที่ต้องการแก้ไข');
        }
        
        // จัดการอัปโหลดรูปภาพใหม่ (ใช้ ID เป็นชื่อไฟล์)
        if(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_result = uploadProductImage($_FILES['image'], $product_id);
            if(!$upload_result['success']) {
                $errors[] = 'อัปโหลดรูปไม่สำเร็จ: ' . $upload_result['message'];
            }
        }
        
        // อัปเดตข้อมูล
        if(empty($errors)) {
            $sql = "UPDATE products SET 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    original_price = ?, 
                    stock = ?, 
                    category_id = ?, 
                    seller_id = ?, 
                    status = ? 
                    WHERE id = ?";
            
            query($sql, [
                $name,
                $description,
                $price,
                $original_price,
                $stock,
                $category_id,
                $seller_id,
                $status,
                $product_id
            ]);
            
            $_SESSION['success'] = 'แก้ไขสินค้า "' . $name . '" เรียบร้อยแล้ว';
            header('Location: admin_products.php');
            exit();
        } else {
            $error_message = implode('<br>', $errors);
        }
        
    } catch(Exception $e) {
        $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// ฟังก์ชันสร้าง slug
function createSlug($text) {
    // แปลงภาษาไทยเป็นภาษาอังกฤษ (แบบง่าย)
    $thai_to_eng = [
        'ก' => 'k', 'ข' => 'kh', 'ฃ' => 'kh', 'ค' => 'kh', 'ฅ' => 'kh', 'ฆ' => 'kh',
        'ง' => 'ng', 'จ' => 'ch', 'ฉ' => 'ch', 'ช' => 'ch', 'ซ' => 's', 'ฌ' => 'ch',
        'ญ' => 'y', 'ฎ' => 'd', 'ฏ' => 't', 'ฐ' => 'th', 'ฑ' => 'th', 'ฒ' => 'th',
        'ณ' => 'n', 'ด' => 'd', 'ต' => 't', 'ถ' => 'th', 'ท' => 'th', 'ธ' => 'th',
        'น' => 'n', 'บ' => 'b', 'ป' => 'p', 'ผ' => 'ph', 'ฝ' => 'f', 'พ' => 'ph',
        'ฟ' => 'f', 'ภ' => 'ph', 'ม' => 'm', 'ย' => 'y', 'ร' => 'r', 'ล' => 'l',
        'ว' => 'w', 'ศ' => 's', 'ษ' => 's', 'ส' => 's', 'ห' => 'h', 'ฬ' => 'l',
        'อ' => 'a', 'ฮ' => 'h',
        'ะ' => 'a', 'ั' => 'a', 'า' => 'a', 'ำ' => 'am', 'ิ' => 'i', 'ี' => 'i',
        'ึ' => 'ue', 'ื' => 'ue', 'ุ' => 'u', 'ู' => 'u', 'เ' => 'e', 'แ' => 'ae',
        'โ' => 'o', 'ใ' => 'ai', 'ไ' => 'ai', 'ๆ' => '', '็' => '', '่' => '',
        '้' => '', '๊' => '', '๋' => '', '์' => '', 'ํ' => ''
    ];
    
    // แทนที่ภาษาไทย
    $text = strtr($text, $thai_to_eng);
    
    // แทนที่ช่องว่างและอักขระพิเศษ
    $text = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($text)));
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return $text ?: 'product-' . time();
}

// ============================================
// ดึงข้อมูลสำหรับแสดงผล
// ============================================

// ดึงข้อมูลสินค้าทั้งหมด พร้อมชื่อหมวดหมู่และร้านค้า
try {
    $sql = "SELECT p.*, 
                   c.name as category_name, 
                   s.name as seller_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            ORDER BY p.id DESC";
    
    $products = fetchAll($sql);
    
    // เก็บจำนวนไว้ในตัวแปรเพื่อใช้กับ stats
    $total_products = count($products);
    $active_products = 0;
    $total_stock = 0;
    $total_value = 0;
    
    foreach($products as $product) {
        if($product['status'] == 'active') {
            $active_products++;
        }
        $total_stock += (int)$product['stock'];
        $total_value += (float)$product['price'] * (int)$product['stock'];
    }
    
} catch(Exception $e) {
    $error_message = 'ไม่สามารถดึงข้อมูลสินค้า: ' . $e->getMessage();
    $products = [];
    $total_products = 0;
    $active_products = 0;
    $total_stock = 0;
    $total_value = 0;
}

// ดึงข้อมูลหมวดหมู่
try {
    $categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
} catch(Exception $e) {
    $categories = [];
}

// ดึงข้อมูลร้านค้า
try {
    $sellers = fetchAll("SELECT * FROM sellers WHERE status = 'active' ORDER BY name");
} catch(Exception $e) {
    $sellers = [];
}

// ดึงข้อมูลสินค้าสำหรับแก้ไข (ถ้ามี)
$edit_product = null;
if(isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $edit_product = fetchOne("SELECT * FROM products WHERE id = ?", [$edit_id]);
        if(!$edit_product) {
            $_SESSION['error'] = 'ไม่พบสินค้าที่ต้องการแก้ไข';
            header('Location: admin_products.php');
            exit();
        }
    } catch(Exception $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        header('Location: admin_products.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - SHOP.COM Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.2rem 0;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }
        .sidebar .nav-link.active {
            background-color: #e7f1ff;
            color: #0d6efd;
        }
        .main-content {
            padding: 1.5rem;
        }
        .stat-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.25rem;
        }
        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
        }
        .image-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 p-0 sidebar">
                <div class="p-3">
                    <h4 class="fw-bold mb-4">SHOP.COM</h4>
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-light rounded-circle p-2 me-2">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?></div>
                            <small class="text-muted">Administrator</small>
                        </div>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="admin.php"><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</a>
                        <a class="nav-link" href="admin_users.php"><i class="fas fa-users"></i> ผู้ใช้</a>
                        <a class="nav-link active" href="admin_products.php"><i class="fas fa-box"></i> สินค้า</a>
                        <a class="nav-link" href="admin_orders.php"><i class="fas fa-shopping-cart"></i> ออเดอร์</a>
                        <a class="nav-link" href="admin_categories.php"><i class="fas fa-tags"></i> หมวดหมู่</a>
                        <a class="nav-link" href="admin_sellers.php"><i class="fas fa-store"></i> ร้านค้า</a>
                        <a class="nav-link" href="admin_settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a>
                        <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">จัดการสินค้า</h2>
                </div>

                <!-- Stats Cards -->
                <?php if($total_products > 0): ?>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card">
                            <div class="text-muted small">สินค้าทั้งหมด</div>
                            <div class="h4 mb-0"><?php echo number_format($total_products); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card">
                            <div class="text-muted small">กำลังขาย</div>
                            <div class="h4 mb-0"><?php echo number_format($active_products); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card">
                            <div class="text-muted small">สต็อกทั้งหมด</div>
                            <div class="h4 mb-0"><?php echo number_format($total_stock); ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="stat-card">
                            <div class="text-muted small">มูลค่าสินค้า</div>
                            <div class="h4 mb-0">฿<?php echo number_format($total_value); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Add Product Button -->
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
                </button>

                <!-- Products Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
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
                                    <?php if(empty($products)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">ไม่มีสินค้า</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($products as $product): ?>
                                        <tr>
                                            <td>#<?php echo $product['id']; ?></td>
                                            <td>
                                                <img src="<?php echo showProductImage($product['id'], 'thumb'); ?>" 
                                                     class="product-thumb" alt="">
                                            </td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td>฿<?php echo number_format($product['price']); ?></td>
                                            <td><?php echo number_format($product['stock']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($product['seller_name'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo $product['status'] == 'active' ? 'กำลังขาย' : 'หยุดขาย'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary btn-action me-1" 
                                                        onclick="editProduct(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-action" 
                                                        onclick="deleteProduct(<?php echo $product['id']; ?>)">
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
        </div>
    </div>

    <!-- Modal เพิ่มสินค้า -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มสินค้าใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ชื่อสินค้า <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ราคา <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ราคาเดิม</label>
                                <input type="number" class="form-control" name="original_price" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">สต็อก <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="stock" value="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">หมวดหมู่</label>
                                <select class="form-select" name="category_id">
                                    <option value="">ไม่มีหมวดหมู่</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ร้านค้า</label>
                                <select class="form-select" name="seller_id">
                                    <option value="">ไม่มีร้านค้า</option>
                                    <?php foreach($sellers as $seller): ?>
                                        <option value="<?php echo $seller['id']; ?>"><?php echo $seller['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รูปภาพ</label>
                            <input type="file" class="form-control" name="image" accept="image/*" onchange="previewImage(this, 'addPreview')">
                            <div class="image-preview" id="addPreview">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ</label>
                            <select class="form-select" name="status">
                                <option value="active">กำลังขาย</option>
                                <option value="inactive">หยุดขาย</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_product" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขสินค้า -->
    <?php if($edit_product): ?>
    <div class="modal fade show" id="editModal" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขสินค้า #<?php echo $edit_product['id']; ?></h5>
                    <a href="admin_products.php" class="btn-close"></a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ชื่อสินค้า <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ราคา <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="price" value="<?php echo $edit_product['price']; ?>" step="0.01" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ราคาเดิม</label>
                                <input type="number" class="form-control" name="original_price" value="<?php echo $edit_product['original_price']; ?>" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">สต็อก <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="stock" value="<?php echo $edit_product['stock']; ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">หมวดหมู่</label>
                                <select class="form-select" name="category_id">
                                    <option value="">ไม่มีหมวดหมู่</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $edit_product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ร้านค้า</label>
                                <select class="form-select" name="seller_id">
                                    <option value="">ไม่มีร้านค้า</option>
                                    <?php foreach($sellers as $seller): ?>
                                        <option value="<?php echo $seller['id']; ?>" <?php echo $edit_product['seller_id'] == $seller['id'] ? 'selected' : ''; ?>>
                                            <?php echo $seller['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รูปภาพปัจจุบัน</label>
                            <div class="mb-2">
                                <img src="<?php echo showProductImage($edit_product['id']); ?>" class="product-thumb" style="width: 100px; height: 100px;">
                            </div>
                            <label class="form-label">เปลี่ยนรูปภาพใหม่</label>
                            <input type="file" class="form-control" name="image" accept="image/*" onchange="previewImage(this, 'editPreview')">
                            <div class="image-preview" id="editPreview">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">สถานะ</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $edit_product['status'] == 'active' ? 'selected' : ''; ?>>กำลังขาย</option>
                                <option value="inactive" <?php echo $edit_product['status'] == 'inactive' ? 'selected' : ''; ?>>หยุดขาย</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="admin_products.php" class="btn btn-secondary">ยกเลิก</a>
                        <button type="submit" name="edit_product" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function editProduct(id) {
            window.location.href = 'admin_products.php?edit=' + id;
        }

        function deleteProduct(id) {
            if (confirm('ต้องการลบสินค้านี้?')) {
                window.location.href = 'admin_products.php?delete=' + id;
            }
        }
    </script>
</body>
</html>