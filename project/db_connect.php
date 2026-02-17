<?php
// db_connect.php - วางในโฟลเดอร์เดียวกับ login.php และ register.php

$host = 'localhost';
$dbname = 'shop_db';
$username = 'root';
$password = 'r660109';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // ฟังก์ชัน Helper สำหรับ query
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
    
    function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return query($sql, $params)->rowCount();
    }
    function uploadImage($file, $folder = 'products', $max_size = 5) {
        $target_dir = "uploads/$folder/";
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if(!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // ตรวจสอบไฟล์
        if($file['error'] != UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปโหลด'];
        }
        
        // ตรวจสอบขนาดไฟล์ (default 5MB)
        if($file['size'] > $max_size * 1024 * 1024) {
            return ['success' => false, 'message' => "ไฟล์ต้องไม่เกิน $max_size MB"];
        }
        
        // ตรวจสอบประเภทไฟล์
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if(!in_array($mime_type, $allowed_types)) {
            return ['success' => false, 'message' => 'รองรับเฉพาะไฟล์รูปภาพ JPG, PNG, GIF, WEBP'];
        }
        
        // สร้างชื่อไฟล์ใหม่
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $extension;
        $target_file = $target_dir . $new_filename;
        
        // อัปโหลดไฟล์
        if(move_uploaded_file($file['tmp_name'], $target_file)) {
            return [
                'success' => true,
                'filename' => $new_filename,
                'path' => $target_file,
                'url' => $target_file
            ];
        } else {
            return ['success' => false, 'message' => 'ไม่สามารถอัปโหลดไฟล์ได้'];
        }
    }

    // ฟังก์ชันลบรูปภาพ
    function deleteImage($filename, $folder = 'products') {
        if(empty($filename)) return true;
        
        $filepath = "uploads/$folder/$filename";
        if(file_exists($filepath)) {
            return unlink($filepath);
        }
        return true;
    }

    // ฟังก์ชันแสดงรูปภาพ (พร้อม fallback)
    function showImage($filename, $folder = 'products', $default = 'default.jpg') {
        // ถ้าไม่มีชื่อไฟล์ หรือไฟล์ไม่มีอยู่จริง
        if(empty($filename) || !file_exists("uploads/$folder/$filename")) {
            // เช็คว่ามีรูป default หรือไม่
            if(file_exists("uploads/$folder/$default")) {
                return "uploads/$folder/$default";
            }
            // ถ้าไม่มีรูป default ให้ใช้ placeholder
            return "https://via.placeholder.com/300x300?text=No+Image";
        }
        return "uploads/$folder/$filename";
    }
}
?>