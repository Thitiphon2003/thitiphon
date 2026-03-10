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
     * สร้างโฟลเดอร์ถ้ายังไม่มี
     */
    function ensureDirectoryExists($folder) {
        $upload_dir = "uploads/$folder/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // ตรวจสอบสิทธิ์การเขียน
        return is_writable($upload_dir);
    }
    
    /**
     * อัปโหลดรูปภาพ (ใช้ ID เป็นชื่อไฟล์)
     */
    function uploadProductImage($file, $product_id) {
        $upload_dir = "uploads/products/";
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // ตรวจสอบสิทธิ์การเขียน
        if (!is_writable($upload_dir)) {
            // ลองเปลี่ยนสิทธิ์
            chmod($upload_dir, 0777);
            if (!is_writable($upload_dir)) {
                return ['success' => false, 'message' => 'โฟลเดอร์ uploads/products/ ไม่สามารถเขียนได้ กรุณาตั้งสิทธิ์โฟลเดอร์'];
            }
        }
        
        // ตรวจสอบข้อผิดพลาด
        if ($file['error'] != UPLOAD_ERR_OK) {
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
        
        // ใช้ .jpg เป็นหลัก
        $target_path = $upload_dir . $product_id . '.jpg';
        
        // ลบรูปเก่าถ้ามี
        if (file_exists($target_path)) {
            unlink($target_path);
        }
        
        // ย้ายไฟล์
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            chmod($target_path, 0644);
            
            // สร้างรูปขนาดเล็ก (thumbnail) ถ้าต้องการ
            createThumbnail($target_path, $upload_dir . $product_id . '_thumb.jpg', 200);
            
            return [
                'success' => true, 
                'message' => 'อัปโหลดไฟล์สำเร็จ',
                'filename' => $product_id . '.jpg',
                'path' => $target_path
            ];
        } else {
            // ตรวจสอบ error ล่าสุด
            $error = error_get_last();
            return [
                'success' => false, 
                'message' => 'ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ปลายทางได้: ' . ($error['message'] ?? 'ไม่ทราบสาเหตุ')
            ];
        }
    }
    
    /**
     * สร้างรูปขนาดเล็ก
     */
    function createThumbnail($source, $destination, $size = 200) {
        if (!file_exists($source)) return false;
        
        list($width, $height) = getimagesize($source);
        $ratio = $width / $height;
        
        if ($width > $height) {
            $new_width = $size;
            $new_height = $size / $ratio;
        } else {
            $new_height = $size;
            $new_width = $size * $ratio;
        }
        
        $thumb = imagecreatetruecolor($new_width, $new_height);
        
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        
        switch($extension) {
            case 'jpg':
            case 'jpeg':
                $source_img = imagecreatefromjpeg($source);
                imagecopyresampled($thumb, $source_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($thumb, $destination, 90);
                break;
            case 'png':
                $source_img = imagecreatefrompng($source);
                imagecopyresampled($thumb, $source_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagepng($thumb, $destination, 9);
                break;
            case 'gif':
                $source_img = imagecreatefromgif($source);
                imagecopyresampled($thumb, $source_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagegif($thumb, $destination);
                break;
        }
        
        imagedestroy($source_img);
        imagedestroy($thumb);
        return true;
    }
    
    /**
     * ลบรูปภาพ
     */
    function deleteProductImage($product_id) {
        $target_path = "uploads/products/" . $product_id . ".jpg";
        $thumb_path = "uploads/products/" . $product_id . "_thumb.jpg";
        
        if (file_exists($target_path)) {
            unlink($target_path);
        }
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
        return true;
    }
    
    /**
     * แสดงรูปภาพ
     */
    function showProductImage($product_id, $type = 'original') {
        $filename = $type == 'thumb' ? $product_id . '_thumb.jpg' : $product_id . '.jpg';
        $path = "uploads/products/" . $filename;
        
        if (file_exists($path)) {
            return $path;
        }
        
        // ถ้าไม่มีรูป ส่งกลับ placeholder
        return "https://via.placeholder.com/300x300?text=No+Image";
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>