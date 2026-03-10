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
    
    // ========== ฟังก์ชันจัดการโฟลเดอร์ ==========
    
    /**
     * ตรวจสอบและสร้างโฟลเดอร์ พร้อมตั้งสิทธิ์
     */
    function ensureUploadDirectory($folder = 'products') {
        $upload_dir = "uploads/$folder/";
        
        // สร้างโฟลเดอร์ uploads ก่อนถ้ายังไม่มี
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
            chmod('uploads', 0777);
        }
        
        // สร้างโฟลเดอร์ย่อย
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            chmod($upload_dir, 0777);
        }
        
        // ตรวจสอบสิทธิ์การเขียน
        if (!is_writable($upload_dir)) {
            chmod($upload_dir, 0777);
        }
        
        return is_writable($upload_dir);
    }
    
    /**
     * แสดงรูปภาพ
     */
    function showImage($filename, $folder = 'products') {
        if (!empty($filename) && file_exists("uploads/$folder/" . $filename)) {
            return "uploads/$folder/" . $filename;
        }
        return "https://via.placeholder.com/300x300?text=No+Image";
    }
    
    /**
     * ลบรูปภาพ
     */
    function deleteImage($filename, $folder = 'products') {
        if (empty($filename)) return true;
        $file_path = "uploads/$folder/" . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true;
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>