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

// ฟังก์ชันสร้าง slug จากชื่อสินค้า
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
// จัดการการลบสินค้า
// ============================================
if(isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    try {
        // เริ่ม transaction
        $pdo->beginTransaction();
        
        // ลบรูปภาพ (ถ้ามี)
        $image_path = "uploads/products/" . $product_id . ".jpg";
        if(file_exists($image_path)) {
            unlink($image_path);
        }
        
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
            }
        }
        
        if(!empty($errors)) {
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
        
        // อัปเดตข้อมูล (ไม่ต้องเปลี่ยน slug)
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

// ============================================
// ฟังก์ชันอัปโหลดรูปภาพเฉพาะ (ใช้ ID เป็นชื่อไฟล์)
// ============================================
function uploadProductImage($file, $product_id) {
    $upload_dir = "uploads/products/";
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if(!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // ตรวจสอบข้อผิดพลาด
    if($file['error'] != UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'ไฟล์มีขนาดใหญ่เกินไป (จำกัดโดย server)',
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
    
    // ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
    if($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB'];
    }
    
    // ตรวจสอบประเภทไฟล์
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if(!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'รองรับเฉพาะไฟล์รูปภาพ JPG, PNG, GIF, WEBP เท่านั้น'];
    }
    
    // ใช้นามสกุล .jpg เสมอ (เพื่อความง่าย)
    $target_path = $upload_dir . $product_id . '.jpg';
    
    // ลบรูปเก่าถ้ามี
    if(file_exists($target_path)) {
        unlink($target_path);
    }
    
    // ย้ายไฟล์
    if(move_uploaded_file($file['tmp_name'], $target_path)) {
        chmod($target_path, 0644);
        return ['success' => true, 'message' => 'อัปโหลดไฟล์สำเร็จ'];
    } else {
        return ['success' => false, 'message' => 'ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ปลายทางได้'];
    }
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* ===== Sidebar ===== */
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
            font-weight: 700;
            color: white;
            margin-bottom: 0.3rem;
        }

        .sidebar-header p {
            color: #94a3b8;
            font-size: 0.9rem;
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
            font-size: 1.1rem;
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
            font-size: 1.1rem;
        }

        /* ===== Main Content ===== */
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
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title h1 i {
            color: #3b82f6;
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
            font-weight: 500;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        /* ===== Content Cards ===== */
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

        .card-header h2 i {
            color: #3b82f6;
        }

        /* ===== Buttons ===== */
        .btn-primary {
            padding: 0.75rem 1.5rem;
            background: #0f172a;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #1e293b;
        }

        .btn-success {
            padding: 0.75rem 1.5rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            padding: 0.75rem 1.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-edit, .btn-delete {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            cursor: pointer;
            margin: 0 0.2rem;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #f59e0b;
            color: white;
        }

        .btn-edit:hover {
            background: #d97706;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        /* ===== Forms ===== */
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
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input.error {
            border-color: #ef4444;
        }

        /* ===== Image Preview ===== */
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
            border: 1px solid #e2e8f0;
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ===== Table ===== */
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
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
            vertical-align: middle;
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

        /* ===== Alerts ===== */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* ===== Stats Cards ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: #dbeafe;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.3rem;
        }

        .stat-info .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* ===== Modal ===== */
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
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
            text-decoration: none;
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

        /* ===== Responsive ===== */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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
            
            .stats-grid {
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
                <div class="admin-name"><?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
            
            <div class="nav-menu">
                <a href="admin.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>แดชบอร์ด</span>
                </a>
                <a href="admin_users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>จัดการผู้ใช้</span>
                </a>
                <a href="admin_products.php" class="nav-item active">
                    <i class="fas fa-box"></i>
                    <span>จัดการสินค้า</span>
                </a>
                <a href="admin_orders.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>จัดการออเดอร์</span>
                </a>
                <a href="admin_categories.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    <span>จัดการหมวดหมู่</span>
                </a>
                <a href="admin_sellers.php" class="nav-item">
                    <i class="fas fa-store"></i>
                    <span>จัดการร้านค้า</span>
                </a>
                <a href="admin_settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>ตั้งค่าระบบ</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>
                        <i class="fas fa-box"></i>
                        จัดการสินค้า
                    </h1>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    ออกจากระบบ
                </a>
            </div>
            
            <!-- Stats Cards -->
            <?php if($total_products > 0): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3>สินค้าทั้งหมด</h3>
                        <div class="value"><?php echo number_format($total_products); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>กำลังขาย</h3>
                        <div class="value"><?php echo number_format($active_products); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="stat-info">
                        <h3>สต็อกทั้งหมด</h3>
                        <div class="value"><?php echo number_format($total_stock); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>มูลค่าสินค้า</h3>
                        <div class="value">฿<?php echo number_format($total_value); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Display Messages -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Product Button -->
            <div style="margin-bottom: 1.5rem;">
                <button class="btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i>
                    เพิ่มสินค้าใหม่
                </button>
            </div>
            
            <!-- Products Table -->
            <div class="content-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        รายการสินค้าทั้งหมด (<?php echo count($products); ?>)
                    </h2>
                </div>
                
                <?php if(empty($products)): ?>
                    <div style="text-align: center; padding: 3rem;">
                        <i class="fas fa-box-open" style="font-size: 4rem; color: #94a3b8; margin-bottom: 1rem;"></i>
                        <h3 style="color: #0f172a; margin-bottom: 0.5rem;">ยังไม่มีสินค้าในระบบ</h3>
                        <p style="color: #64748b; margin-bottom: 1.5rem;">คลิกปุ่ม "เพิ่มสินค้าใหม่" เพื่อเริ่มต้น</p>
                    </div>
                <?php else: ?>
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
                                    <td>#<?php echo $product['id']; ?></td>
                                    <td>
                                        <div class="product-thumb">
                                            <?php 
                                            $image_path = "uploads/products/" . $product['id'] . ".jpg";
                                            if(file_exists($image_path)) {
                                                echo '<img src="' . $image_path . '?t=' . time() . '" alt="' . htmlspecialchars($product['name']) . '">';
                                            } else {
                                                echo '<img src="https://via.placeholder.com/60x60/e2e8f0/64748b?text=No+Image" alt="No Image">';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <?php if(!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                            <br><small style="color: #94a3b8;">เต็ม ฿<?php echo number_format($product['original_price']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>฿<?php echo number_format($product['price']); ?></strong></td>
                                    <td><?php echo number_format($product['stock']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($product['seller_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php echo $product['status'] == 'active' ? 'กำลังขาย' : 'หยุดขาย'; ?>
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
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มสินค้า -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-plus-circle"></i>
                    เพิ่มสินค้าใหม่
                </h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อสินค้า <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>ราคา (บาท) <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="price" min="0" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ราคาเดิม (บาท)</label>
                        <input type="number" name="original_price" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>จำนวนคงเหลือ <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="stock" min="0" value="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <select name="category_id">
                            <option value="">ไม่มีหมวดหมู่</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ร้านค้า</label>
                        <select name="seller_id">
                            <option value="">ไม่มีร้านค้า</option>
                            <?php foreach($sellers as $seller): ?>
                                <option value="<?php echo $seller['id']; ?>">
                                    <?php echo htmlspecialchars($seller['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียดสินค้า</label>
                    <textarea name="description" rows="3" placeholder="รายละเอียดสินค้า..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>รูปภาพสินค้า</label>
                    <input type="file" name="image" id="add_image" accept="image/*" onchange="previewImage(this, 'add_preview')">
                    <div class="image-preview" id="add_preview">
                        <i class="fas fa-image"></i>
                    </div>
                    <small style="color: #64748b;">ขนาดไม่เกิน 5MB, รองรับ JPG, PNG, GIF, WEBP (ระบบจะบันทึกเป็น [ID].jpg)</small>
                </div>
                
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active">กำลังขาย</option>
                        <option value="inactive">หยุดขาย</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_product" class="btn-success">
                        <i class="fas fa-save"></i> บันทึกสินค้า
                    </button>
                    <button type="button" class="btn-danger" onclick="closeModal('addModal')">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal แก้ไขสินค้า -->
    <?php if($edit_product): ?>
    <div id="editModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-edit"></i>
                    แก้ไขสินค้า #<?php echo $edit_product['id']; ?>
                </h2>
                <a href="admin_products.php" class="close">&times;</a>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อสินค้า <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>ราคา (บาท) <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="price" value="<?php echo $edit_product['price']; ?>" min="0" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ราคาเดิม (บาท)</label>
                        <input type="number" name="original_price" value="<?php echo $edit_product['original_price']; ?>" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>จำนวนคงเหลือ <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="stock" value="<?php echo $edit_product['stock']; ?>" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <select name="category_id">
                            <option value="">ไม่มีหมวดหมู่</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $edit_product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
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
                                    <?php echo htmlspecialchars($seller['name']); ?>
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
                        <?php 
                        $current_image_path = "uploads/products/" . $edit_product['id'] . ".jpg";
                        if(file_exists($current_image_path)) {
                            echo '<img src="' . $current_image_path . '?t=' . time() . '" alt="current" style="width: 100%; height: 100%; object-fit: cover;">';
                        } else {
                            echo '<img src="https://via.placeholder.com/150x150/e2e8f0/64748b?text=No+Image" alt="No Image">';
                        }
                        ?>
                    </div>
                    
                    <label>เปลี่ยนรูปภาพใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                    <input type="file" name="image" id="edit_image" accept="image/*" onchange="previewImage(this, 'edit_preview')">
                    <div class="image-preview" id="edit_preview">
                        <i class="fas fa-image"></i>
                    </div>
                    <small style="color: #64748b;">ระบบจะบันทึกเป็น <?php echo $edit_product['id']; ?>.jpg</small>
                </div>
                
                <div class="form-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active" <?php echo $edit_product['status'] == 'active' ? 'selected' : ''; ?>>กำลังขาย</option>
                        <option value="inactive" <?php echo $edit_product['status'] == 'inactive' ? 'selected' : ''; ?>>หยุดขาย</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_product" class="btn-success">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                    <a href="admin_products.php" class="btn-danger" style="padding: 0.75rem 1.5rem; text-decoration: none;">
                        <i class="fas fa-times"></i> ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // แสดง Modal เพิ่มสินค้า
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        // ปิด Modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // ไปที่หน้าแก้ไขสินค้า
        function editProduct(id) {
            window.location.href = 'admin_products.php?edit=' + id;
        }
        
        // ลบสินค้า
        function deleteProduct(id) {
            if(confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?\nการดำเนินการนี้ไม่สามารถเรียกคืนได้')) {
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
        
        // ปิด Modal เมื่อคลิกด้านนอก
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>
</html>