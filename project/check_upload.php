<?php
echo "<h2>ตรวจสอบระบบอัปโหลด</h2>";

$upload_dir = "uploads/products/";

// 1. ตรวจสอบว่าโฟลเดอร์มีอยู่หรือไม่
echo "<h3>1. ตรวจสอบโฟลเดอร์</h3>";
if (file_exists($upload_dir)) {
    echo "✅ มีโฟลเดอร์: $upload_dir<br>";
} else {
    echo "❌ ไม่มีโฟลเดอร์: $upload_dir<br>";
    echo "กำลังสร้างโฟลเดอร์...<br>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "✅ สร้างโฟลเดอร์สำเร็จ<br>";
    } else {
        echo "❌ ไม่สามารถสร้างโฟลเดอร์ได้<br>";
    }
}

// 2. ตรวจสอบสิทธิ์การเขียน
echo "<h3>2. ตรวจสอบสิทธิ์</h3>";
if (is_writable($upload_dir)) {
    echo "✅ โฟลเดอร์สามารถเขียนได้<br>";
} else {
    echo "❌ โฟลเดอร์ไม่สามารถเขียนได้<br>";
    echo "กำลังเปลี่ยนสิทธิ์...<br>";
    if (chmod($upload_dir, 0777)) {
        echo "✅ เปลี่ยนสิทธิ์สำเร็จ<br>";
    } else {
        echo "❌ ไม่สามารถเปลี่ยนสิทธิ์ได้<br>";
    }
}

// 3. ตรวจสอบการอัปโหลด
echo "<h3>3. ตรวจสอบการตั้งค่า PHP</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " วินาที<br>";

// 4. ทดสอบการเขียนไฟล์
echo "<h3>4. ทดสอบการเขียนไฟล์</h3>";
$test_file = $upload_dir . "test.txt";
if (file_put_contents($test_file, "test")) {
    echo "✅ สามารถเขียนไฟล์ทดสอบได้<br>";
    unlink($test_file);
} else {
    echo "❌ ไม่สามารถเขียนไฟล์ทดสอบได้<br>";
}

// 5. แสดงเส้นทางจริง
echo "<h3>5. เส้นทางจริง</h3>";
echo "Absolute path: " . realpath($upload_dir) . "<br>";
?>