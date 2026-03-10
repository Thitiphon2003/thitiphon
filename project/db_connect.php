<?php
// db_connect.php
$host = 'localhost';
$dbname = 'shop_db';
$username = 'root';
$password = 'r660109';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // ฟังก์ชัน query
    function query($sql, $params = []) {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    function fetchAll($sql, $params = []) {
        return query($sql, $params)->fetchAll();
    }
    
    function fetchOne($sql, $params = []) {
        return query($sql, $params)->fetch();
    }
    
    // ========== ฟังก์ชันจัดการรูปภาพ ==========
    
    /**
     * อัปโหลดรูปภาพสินค้า
     */
    function uploadProductImage($file, $product_id, $is_primary = false) {
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
        
        // สร้างชื่อไฟล์ที่ไม่ซ้ำ
        $timestamp = time();
        $random = rand(1000, 9999);
        $filename = $product_id . '_' . $timestamp . '_' . $random . '.' . $extension;
        $target_path = $upload_dir . $filename;
        
        // ย้ายไฟล์
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            chmod($target_path, 0644);
            
            // หา sort_order ล่าสุด
            $max_sort = fetchOne("SELECT MAX(sort_order) as max FROM product_images WHERE product_id = ?", [$product_id]);
            $sort_order = ($max_sort['max'] ?? 0) + 1;
            
            // บันทึกลง database
            global $pdo;
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
     * ลบรูปภาพสินค้า
     */
    function deleteProductImage($image_id) {
        $image = fetchOne("SELECT * FROM product_images WHERE id = ?", [$image_id]);
        if (!$image) return false;
        
        $file_path = "uploads/products/" . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        query("DELETE FROM product_images WHERE id = ?", [$image_id]);
        return true;
    }
    
    /**
     * ลบรูปภาพทั้งหมดของสินค้า
     */
    function deleteAllProductImages($product_id) {
        $images = fetchAll("SELECT * FROM product_images WHERE product_id = ?", [$product_id]);
        foreach ($images as $image) {
            $file_path = "uploads/products/" . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        query("DELETE FROM product_images WHERE product_id = ?", [$product_id]);
        return true;
    }
    
    /**
     * ตั้งรูปหลัก
     */
    function setPrimaryImage($product_id, $image_id) {
        global $pdo;
        $pdo->beginTransaction();
        
        query("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$product_id]);
        $result = query("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?", [$image_id, $product_id]);
        
        if ($result->rowCount() > 0) {
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
    }
    
    /**
     * แสดงรูปภาพ
     */
    function showImage($filename) {
        if (!empty($filename) && file_exists("uploads/products/" . $filename)) {
            return "uploads/products/" . $filename;
        }
        return "https://via.placeholder.com/300x300?text=No+Image";
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>