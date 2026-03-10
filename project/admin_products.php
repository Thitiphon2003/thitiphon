<?php
// เปิด error display เพื่อดูข้อผิดพลาด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
// ฟังก์ชันจัดการรูปภาพ (เฉพาะใน admin_products.php)
// ============================================

/**
 * อัปโหลดรูปภาพสินค้า
 */
function uploadProductImage($file, $product_id, $is_primary = false) {
    global $pdo;
    
    // ตรวจสอบว่ามีไฟล์หรือไม่
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'ไม่มีไฟล์ที่อัปโหลด'];
    }
    
    $upload_dir = "uploads/products/";
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            return ['success' => false, 'message' => 'ไม่สามารถสร้างโฟลเดอร์ uploads/products/ ได้'];
        }
    }
    
    // ตรวจสอบสิทธิ์การเขียน
    if (!is_writable($upload_dir)) {
        chmod($upload_dir, 0777);
        if (!is_writable($upload_dir)) {
            return ['success' => false, 'message' => 'โฟลเดอร์ uploads/products/ ไม่สามารถเขียนได้'];
        }
    }
    
    // ตรวจสอบข้อผิดพลาด
    if ($file['error'] != UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'ไฟล์มีขนาดใหญ่เกินไป (จำกัด ' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE => 'ไฟล์มีขนาดใหญ่เกินไป',
            UPLOAD_ERR_PARTIAL => 'อัปโหลดไฟล์ได้เพียงบางส่วน',
            UPLOAD_ERR_NO_FILE => 'ไม่ได้เลือกไฟล์',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่มีโฟลเดอร์ชั่วคราว',
            UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนไฟล์ลงดิสก์ได้',
            UPLOAD_ERR_EXTENSION => 'ส่วนขยาย PHP หยุดการอัปโหลด'
        ];
        $error_msg = $error_messages[$file['error']] ?? 'ข้อผิดพลาดที่ไม่ทราบสาเหตุ';
        return ['success' => false, 'message' => $error_msg];
    }
    
    // ตรวจสอบขนาดไฟล์ (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB'];
    }
    
    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'รองรับเฉพาะไฟล์รูปภาพ JPG, PNG, GIF, WEBP เท่านั้น'];
    }
    
    // ตรวจสอบนามสกุลไฟล์
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowed_ext)) {
        return ['success' => false, 'message' => 'นามสกุลไฟล์ไม่ถูกต้อง'];
    }
    
    // สร้างชื่อไฟล์ตาม ID สินค้า
    $timestamp = time();
    $filename = $product_id . '_' . $timestamp . '.' . $extension;
    $target_path = $upload_dir . $filename;
    
    // ย้ายไฟล์
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        chmod($target_path, 0644);
        
        // หา sort_order ล่าสุด
        $max_sort = fetchOne("SELECT MAX(sort_order) as max FROM product_images WHERE product_id = ?", [$product_id]);
        $sort_order = ($max_sort['max'] ?? 0) + 1;
        
        // บันทึกลง database
        $sql = "INSERT INTO product_images (product_id, image_path, is_primary, sort_order, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$product_id, $filename, $is_primary ? 1 : 0, $sort_order]);
        
        if (!$result) {
            // ถ้าบันทึก database ไม่สำเร็จ ให้ลบไฟล์ทิ้ง
            unlink($target_path);
            return ['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลรูปภาพลงฐานข้อมูลได้'];
        }
        
        $image_id = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'อัปโหลดรูปภาพสำเร็จ',
            'image_id' => $image_id,
            'filename' => $filename,
            'path' => $target_path
        ];
    } else {
        $error = error_get_last();
        return [
            'success' => false,
            'message' => 'ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ปลายทางได้: ' . ($error['message'] ?? 'ไม่ทราบสาเหตุ')
        ];
    }
}

/**
 * ดึงรูปภาพหลักของสินค้า
 */
function getProductImage($product_id) {
    $image = fetchOne("SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1", [$product_id]);
    if ($image) {
        return $image;
    }
    
    // ถ้าไม่มีรูปหลัก ให้ดึงรูปแรก
    $image = fetchOne("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order LIMIT 1", [$product_id]);
    return $image;
}

/**
 * ดึงรูปภาพทั้งหมดของสินค้า
 */
function getProductImages($product_id) {
    return fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order", [$product_id]);
}

/**
 * สร้าง slug
 */
function createSlug($text) {
    $thai_to_eng = [
        'ก' => 'k', 'ข' => 'kh', 'ค' => 'kh', 'ง' => 'ng', 'จ' => 'ch',
        'ช' => 'ch', 'ซ' => 's', 'ญ' => 'y', 'ด' => 'd', 'ต' => 't',
        'ท' => 'th', 'น' => 'n', 'บ' => 'b', 'ป' => 'p', 'พ' => 'ph',
        'ฟ' => 'f', 'ม' => 'm', 'ย' => 'y', 'ร' => 'r', 'ล' => 'l',
        'ว' => 'w', 'ส' => 's', 'ห' => 'h', 'อ' => 'a', 'ฮ' => 'h'
    ];
    
    $text = strtr($text, $thai_to_eng);
    $text = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($text)));
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-') ?: 'product-' . time();
}

// ============================================
// จัดการการลบรูปภาพ
// ============================================
if(isset($_GET['delete_image'])) {
    $image_id = (int)$_GET['delete_image'];
    $product_id = (int)$_GET['product_id'];
    
    // ตรวจสอบว่าเป็นรูปหลักหรือไม่
    $image = fetchOne("SELECT * FROM product_images WHERE id = ?", [$image_id]);
    if ($image && $image['is_primary']) {
        // หารูปอื่นมาเป็นรูปหลักแทน
        $other = fetchOne("SELECT id FROM product_images WHERE product_id = ? AND id != ? ORDER BY sort_order LIMIT 1", 
                         [$product_id, $image_id]);
        if ($other) {
            query("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$other['id']]);
        }
    }
    
    // ลบไฟล์รูปภาพ
    $file_path = "uploads/products/" . $image['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // ลบข้อมูลใน database
    query("DELETE FROM product_images WHERE id = ?", [$image_id]);
    
    $_SESSION['success'] = 'ลบรูปภาพเรียบร้อย';
    header('Location: admin_products.php?edit=' . $product_id);
    exit();
}

// ============================================
// จัดการการตั้งรูปหลัก
// ============================================
if(isset($_GET['set_primary'])) {
    $product_id = (int)$_GET['product_id'];
    $image_id = (int)$_GET['set_primary'];
    
    // ยกเลิกรูปหลักเก่า
    query("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$product_id]);
    // ตั้งรูปหลักใหม่
    query("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?", [$image_id, $product_id]);
    
    $_SESSION['success'] = 'ตั้งรูปหลักเรียบร้อย';
    header('Location: admin_products.php?edit=' . $product_id);
    exit();
}

// ============================================
// จัดการการลบสินค้า
// ============================================
if(isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // ดึงรายชื่อรูปภาพทั้งหมด
        $images = fetchAll("SELECT * FROM product_images WHERE product_id = ?", [$product_id]);
        
        // ลบไฟล์รูปภาพ
        foreach ($images as $img) {
            $file_path = "uploads/products/" . $img['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // ลบข้อมูลรูปภาพ
        query("DELETE FROM product_images WHERE product_id = ?", [$product_id]);
        
        // ลบข้อมูลสินค้า
        query("DELETE FROM products WHERE id = ?", [$product_id]);
        
        $pdo->commit();
        $_SESSION['success'] = 'ลบสินค้าเรียบร้อย';
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
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $seller_id = !empty($_POST['seller_id']) ? intval($_POST['seller_id']) : null;
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        $errors = [];
        if(empty($name)) $errors[] = 'กรุณากรอกชื่อสินค้า';
        if($price <= 0) $errors[] = 'กรุณากรอกราคาที่ถูกต้อง';
        
        if(empty($errors)) {
            // สร้าง slug
            $slug = createSlug($name);
            
            // ตรวจสอบ slug ซ้ำ
            $check = fetchOne("SELECT id FROM products WHERE slug = ?", [$slug]);
            if ($check) {
                $slug = $slug . '-' . time();
            }
            
            // บันทึกสินค้า
            $sql = "INSERT INTO products (name, slug, description, price, original_price, stock, category_id, seller_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            query($sql, [$name, $slug, $description, $price, $original_price, $stock, $category_id, $seller_id, $status]);
            
            $product_id = $pdo->lastInsertId();
            $uploaded_count = 0;
            
            // อัปโหลดรูปภาพ (ถ้ามี)
            if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                
                for($i = 0; $i < count($files['name']); $i++) {
                    if($files['error'][$i] == UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        $is_primary = ($i == 0); // รูปแรกเป็นรูปหลัก
                        $result = uploadProductImage($file, $product_id, $is_primary);
                        
                        if($result['success']) {
                            $uploaded_count++;
                        }
                    }
                }
            }
            
            $_SESSION['success'] = "เพิ่มสินค้าเรียบร้อย (อัปโหลด $uploaded_count รูป)";
            header('Location: admin_products.php');
            exit();
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
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $seller_id = !empty($_POST['seller_id']) ? intval($_POST['seller_id']) : null;
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        $errors = [];
        if(empty($name)) $errors[] = 'กรุณากรอกชื่อสินค้า';
        if($price <= 0) $errors[] = 'กรุณากรอกราคาที่ถูกต้อง';
        
        if(empty($errors)) {
            // อัปเดตสินค้า
            $sql = "UPDATE products SET 
                    name = ?, description = ?, price = ?, original_price = ?, 
                    stock = ?, category_id = ?, seller_id = ?, status = ? 
                    WHERE id = ?";
            query($sql, [$name, $description, $price, $original_price, $stock, $category_id, $seller_id, $status, $product_id]);
            
            $upload_errors = [];
            $uploaded_count = 0;
            
            // ตรวจสอบว่ามีรูปภาพอยู่แล้วหรือไม่
            $existing_images = getProductImages($product_id);
            $has_primary = false;
            foreach ($existing_images as $img) {
                if ($img['is_primary']) {
                    $has_primary = true;
                    break;
                }
            }
            
            // อัปโหลดรูปภาพใหม่ (ถ้ามี)
            if(isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
                $files = $_FILES['new_images'];
                
                for($i = 0; $i < count($files['name']); $i++) {
                    if($files['error'][$i] == UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        
                        // รูปแรกที่อัปโหลดจะเป็นรูปหลักถ้ายังไม่มีรูปหลัก
                        $is_primary = (!$has_primary && $i == 0);
                        
                        $result = uploadProductImage($file, $product_id, $is_primary);
                        
                        if($result['success']) {
                            $uploaded_count++;
                            if ($is_primary) {
                                $has_primary = true;
                            }
                        } else {
                            $upload_errors[] = "รูปที่ " . ($i+1) . ": " . $result['message'];
                        }
                    }
                }
            }
            
            $message = "แก้ไขสินค้าเรียบร้อย";
            if ($uploaded_count > 0) {
                $message .= " (อัปโหลด $uploaded_count รูป)";
            }
            $_SESSION['success'] = $message;
            
            if (!empty($upload_errors)) {
                $_SESSION['warning'] = implode('<br>', $upload_errors);
            }
            
            header('Location: admin_products.php');
            exit();
        } else {
            $error_message = implode('<br>', $errors);
        }
        
    } catch(Exception $e) {
        $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// ============================================
// ดึงข้อมูลสำหรับแสดงผล
// ============================================

// ดึงข้อมูลสินค้า
$products = fetchAll("SELECT p.*, c.name as category_name, s.name as seller_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN sellers s ON p.seller_id = s.id 
                      ORDER BY p.id DESC");

// ดึงข้อมูลหมวดหมู่
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// ดึงข้อมูลร้านค้า
$sellers = fetchAll("SELECT * FROM sellers WHERE status = 'active' ORDER BY name");

// ดึงข้อมูลสินค้าสำหรับแก้ไข (ถ้ามี)
$edit_product = null;
$edit_images = [];
if(isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_product = fetchOne("SELECT * FROM products WHERE id = ?", [$edit_id]);
    if($edit_product) {
        $edit_images = getProductImages($edit_id);
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
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .gallery-item {
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 0.5rem;
            text-align: center;
        }
        .gallery-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 0.375rem;
        }
        .gallery-item .badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
        }
        .gallery-item .actions {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.25rem;
            justify-content: center;
        }
        .stat-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.25rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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

                <?php if(isset($_SESSION['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
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
                                                <?php 
                                                $image = getProductImage($product['id']);
                                                if($image): ?>
                                                    <img src="uploads/products/<?php echo $image['image_path']; ?>?t=<?php echo time(); ?>" class="product-thumb" onerror="this.src='https://via.placeholder.com/60x60?text=Error'">
                                                <?php else: ?>
                                                    <img src="https://via.placeholder.com/60x60?text=No+Image" class="product-thumb">
                                                <?php endif; ?>
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
                                <label class="form-label">สต็อก</label>
                                <input type="number" class="form-control" name="stock" value="0">
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
                            <label class="form-label">รูปภาพ (เลือกได้หลายรูป)</label>
                            <input type="file" class="form-control" name="images[]" accept="image/*" multiple onchange="previewImages(this, 'addPreview')">
                            <div class="image-gallery" id="addPreview"></div>
                            <small class="text-muted">รูปแรกจะเป็นรูปหลัก</small>
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
                                <label class="form-label">สต็อก</label>
                                <input type="number" class="form-control" name="stock" value="<?php echo $edit_product['stock']; ?>">
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
                        
                        <!-- รูปภาพที่มีอยู่แล้ว -->
                        <?php if(!empty($edit_images)): ?>
                        <div class="mb-3">
                            <label class="form-label">รูปภาพที่มีอยู่</label>
                            <div class="image-gallery">
                                <?php foreach($edit_images as $img): ?>
                                <div class="gallery-item">
                                    <?php if($img['is_primary']): ?>
                                        <span class="badge bg-primary">หลัก</span>
                                    <?php endif; ?>
                                    <img src="uploads/products/<?php echo $img['image_path']; ?>?t=<?php echo time(); ?>" alt="" onerror="this.src='https://via.placeholder.com/100x100?text=Error'">
                                    <div class="actions">
                                        <?php if(!$img['is_primary']): ?>
                                            <a href="?set_primary=<?php echo $img['id']; ?>&product_id=<?php echo $edit_product['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary btn-action" title="ตั้งเป็นรูปหลัก">
                                                <i class="fas fa-star"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete_image=<?php echo $img['id']; ?>&product_id=<?php echo $edit_product['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger btn-action" 
                                           onclick="return confirm('ลบรูปภาพนี้?')" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- อัปโหลดรูปภาพใหม่ -->
                        <div class="mb-3">
                            <label class="form-label">เพิ่มรูปภาพใหม่</label>
                            <input type="file" class="form-control" name="new_images[]" accept="image/*" multiple onchange="previewImages(this, 'editPreview')">
                            <div class="image-gallery" id="editPreview"></div>
                            <small class="text-muted">ระบบจะบันทึกชื่อไฟล์ตาม ID สินค้า</small>
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
        function previewImages(input, previewId) {
            const gallery = document.getElementById(previewId);
            gallery.innerHTML = '';
            
            if (input.files) {
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'gallery-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" style="width:100%; height:100px; object-fit:cover;">
                            <div class="small text-muted mt-1">${file.name.substring(0,15)}</div>
                        `;
                        gallery.appendChild(div);
                    }
                    
                    reader.readAsDataURL(file);
                }
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