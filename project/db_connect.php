<?php
// db_connect.php
$host = 'localhost';
$dbname = 'shop_db';
$username = 'root';
$password = '';

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
    
    function insert($table, $data) {
        global $pdo;
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        
        foreach($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $pdo->lastInsertId();
    }
    
    function update($table, $data, $where, $whereParams = []) {
        global $pdo;
        $set = [];
        foreach(array_keys($data) as $key) {
            $set[] = "$key = :$key";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        $stmt = $pdo->prepare($sql);
        
        foreach($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        foreach($whereParams as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        return $stmt->execute();
    }
    
    function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return query($sql, $params)->rowCount();
    }
    
    // ========== ฟังก์ชันจัดการรูปภาพ ==========
    
    /**
     * อัปโหลดรูปภาพ
     */
    function uploadImage($file, $folder = 'products', $max_size = 5) {
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        $upload_dir = "uploads/$folder/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
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
        
        // ตรวจสอบขนาดไฟล์
        if ($file['size'] > $max_size * 1024 * 1024) {
            return ['success' => false, 'message' => "ไฟล์ต้องมีขนาดไม่เกิน $max_size MB"];
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
        
        // สร้างชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
        $new_filename = uniqid() . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $new_filename;
        
        // ย้ายไฟล์
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // ตั้งสิทธิ์ให้ไฟล์
            chmod($target_path, 0644);
            
            return [
                'success' => true,
                'filename' => $new_filename,
                'path' => $target_path,
                'message' => 'อัปโหลดไฟล์สำเร็จ'
            ];
        } else {
            return ['success' => false, 'message' => 'ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ปลายทางได้'];
        }
    }
    
    /**
     * ลบรูปภาพ
     */
    function deleteImage($filename, $folder = 'products') {
        if (empty($filename)) {
            return true;
        }
        
        $file_path = "uploads/$folder/$filename";
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true;
    }
    
    /**
     * แสดงรูปภาพ (พร้อม fallback)
     */
    function showImage($filename, $folder = 'products', $default = 'default.jpg') {
        // ถ้ามีชื่อไฟล์และไฟล์มีอยู่จริง
        if (!empty($filename) && file_exists("uploads/$folder/$filename")) {
            return "uploads/$folder/$filename";
        }
        
        // ตรวจสอบว่ามีไฟล์เริ่มต้นหรือไม่
        if (file_exists("uploads/$folder/$default")) {
            return "uploads/$folder/$default";
        }
        
        // ใช้ placeholder
        return "https://via.placeholder.com/300x300?text=No+Image";
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>