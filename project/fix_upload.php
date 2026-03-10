<?php
echo "<h1>🔧 แก้ไขปัญหาการอัปโหลดรูปภาพ</h1>";

// 1. ตรวจสอบและสร้างโฟลเดอร์
$folders = [
    'uploads',
    'uploads/products',
    'uploads/categories',
    'uploads/profiles',
    'uploads/slips'
];

echo "<h2>1. ตรวจสอบโฟลเดอร์</h2>";
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        if (mkdir($folder, 0777, true)) {
            echo "✅ สร้างโฟลเดอร์ $folder สำเร็จ<br>";
        } else {
            echo "❌ ไม่สามารถสร้างโฟลเดอร์ $folder ได้<br>";
        }
    } else {
        echo "✅ มีโฟลเดอร์ $folder แล้ว<br>";
        
        // ตรวจสอบสิทธิ์
        if (!is_writable($folder)) {
            if (chmod($folder, 0777)) {
                echo "&nbsp;&nbsp;✓ เปลี่ยนสิทธิ์เป็น 0777 สำเร็จ<br>";
            } else {
                echo "&nbsp;&nbsp;❌ ไม่สามารถเปลี่ยนสิทธิ์ได้<br>";
            }
        } else {
            echo "&nbsp;&nbsp;✓ สามารถเขียนได้<br>";
        }
    }
}

// 2. ตรวจสอบการตั้งค่า PHP
echo "<h2>2. ตรวจสอบการตั้งค่า PHP</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " วินาที<br>";
echo "max_input_time: " . ini_get('max_input_time') . " วินาที<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

// 3. ตรวจสอบตาราง product_images
echo "<h2>3. ตรวจสอบตาราง product_images</h2>";
try {
    global $pdo;
    $pdo = new PDO("mysql:host=localhost;dbname=shop_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('product_images', $tables)) {
        echo "✅ มีตาราง product_images แล้ว<br>";
        
        // ตรวจสอบโครงสร้าง
        $columns = $pdo->query("DESCRIBE product_images")->fetchAll();
        echo "&nbsp;&nbsp;✓ โครงสร้างตารางถูกต้อง<br>";
    } else {
        echo "❌ ไม่มีตาราง product_images<br>";
        echo "&nbsp;&nbsp;กำลังสร้างตาราง...<br>";
        
        $sql = "CREATE TABLE product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB";
        
        $pdo->exec($sql);
        echo "✅ สร้างตารางสำเร็จ<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
}

// 4. แนะนำการแก้ไข
echo "<h2>4. คำแนะนำ</h2>";
echo "<ul>";
echo "<li>ถ้าโฟลเดอร์ไม่สามารถเขียนได้ ให้คลิกขวาที่โฟลเดอร์ 'uploads' → Properties → Security → Edit → ให้สิทธิ์ Users แบบ Full control</li>";
echo "<li>ถ้าขนาดไฟล์ใหญ่เกินไป ให้แก้ไขไฟล์ C:\\xampp\\php\\php.ini และเปลี่ยนค่า upload_max_filesize = 20M, post_max_size = 20M แล้ว restart Apache</li>";
echo "<li>หลังจากแก้ไขแล้ว ให้ทดสอบอีกครั้งที่ <a href='test_upload_debug.php'>test_upload_debug.php</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='admin_products.php'>กลับไปหน้าจัดการสินค้า</a></p>";
?>