<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 แก้ไขสิทธิ์การอัปโหลด</h2>";

$paths = [
    '../assets/' => 'โฟลเดอร์ assets',
    '../assets/images/' => 'โฟลเดอร์ images',
    '../assets/images/stores/' => 'โฟลเดอร์ stores'
];

foreach ($paths as $path => $name) {
    echo "<h3>ตรวจสอบ: $name</h3>";
    
    if (!file_exists($path)) {
        echo "❌ ไม่มีโฟลเดอร์ กำลังสร้าง...<br>";
        if (mkdir($path, 0777, true)) {
            echo "✅ สร้างโฟลเดอร์สำเร็จ<br>";
        } else {
            echo "❌ ไม่สามารถสร้างโฟลเดอร์ได้<br>";
        }
    } else {
        echo "✅ มีโฟลเดอร์แล้ว<br>";
    }
    
    // ตั้งสิทธิ์
    if (chmod($path, 0777)) {
        echo "✅ ตั้งสิทธิ์เป็น 777 สำเร็จ<br>";
    } else {
        echo "❌ ไม่สามารถตั้งสิทธิ์ได้<br>";
    }
    
    // ตรวจสอบสิทธิ์
    $perms = substr(sprintf('%o', fileperms($path)), -4);
    echo "สิทธิ์ปัจจุบัน: $perms<br>";
    echo "สามารถเขียนได้: " . (is_writable($path) ? '✅ ใช่' : '❌ ไม่ใช่') . "<br>";
    echo "<hr>";
}

echo "<h3>📋 สรุป</h3>";
echo "<ul>";
echo "<li>Owner: " . get_current_user() . "</li>";
echo "<li>PHP User: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'ไม่สามารถตรวจสอบได้') . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "</ul>";

echo "<p><a href='products.php' class='btn'>กลับไปหน้าจัดการสินค้า</a></p>";
?>