<?php
require_once 'includes/config.php';

// 1. ลบข้อมูล admin เดิมทิ้ง
$conn->query("DELETE FROM users WHERE username = 'admin'");

// 2. เพิ่ม admin ใหม่ โดยเก็บรหัสผ่านเป็น plain text (ไม่เข้ารหัส)
$sql = "INSERT INTO users (username, password, email, fullname, role) 
        VALUES ('admin', '12345', 'admin@example.com', 'Admin User', 'admin')";

if ($conn->query($sql)) {
    echo "✅ แก้ไขสำเร็จ!<br>";
    echo "========================<br>";
    echo "👤 ชื่อผู้ใช้: <strong>admin</strong><br>";
    echo "🔑 รหัสผ่าน: <strong>12345</strong><br>";
    echo "========================<br><br>";
    
    // 3. ทดสอบการ login โดยตรง
    session_start();
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'admin';
    
    echo "🎉 กำลังพาคุณเข้าสู่ระบบ admin...<br>";
    echo "ถ้าไม่ไปอัตโนมัติ <a href='admin/'>คลิกที่นี่</a>";
    
    // redirect ไปหน้า admin
    header("Refresh: 3; url=admin/");
} else {
    echo "❌ เกิดข้อผิดพลาด: " . $conn->error;
}

// 4. แสดงข้อมูลในฐานข้อมูลเพื่อตรวจสอบ
echo "<br><br>📋 ข้อมูลในตาราง users:<br>";
$result = $conn->query("SELECT id, username, password, role FROM users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Password</th><th>Role</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['password'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>