<?php
echo "<h1>🔧 แก้ไขสิทธิ์โฟลเดอร์</h1>";

$folders = [
    'uploads',
    'uploads/products',
    'uploads/categories',
    'uploads/profiles',
    'uploads/slips'
];

foreach ($folders as $folder) {
    echo "<h3>ตรวจสอบ: $folder</h3>";
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!file_exists($folder)) {
        if (mkdir($folder, 0777, true)) {
            echo "✅ สร้างโฟลเดอร์สำเร็จ<br>";
        } else {
            echo "❌ ไม่สามารถสร้างโฟลเดอร์ได้<br>";
            continue;
        }
    } else {
        echo "✅ มีโฟลเดอร์อยู่แล้ว<br>";
    }
    
    // เปลี่ยนสิทธิ์
    if (chmod($folder, 0777)) {
        echo "✅ เปลี่ยนสิทธิ์เป็น 0777 สำเร็จ<br>";
    } else {
        echo "❌ ไม่สามารถเปลี่ยนสิทธิ์ได้<br>";
    }
    
    // ตรวจสอบสิทธิ์
    if (is_writable($folder)) {
        echo "✅ สามารถเขียนได้<br>";
    } else {
        echo "❌ ไม่สามารถเขียนได้<br>";
    }
    
    echo "<br>";
}

echo "<hr>";
echo "<p><a href='admin_products.php'>กลับไปหน้าจัดการสินค้า</a></p>";
?>