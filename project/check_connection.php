<?php
require_once 'db_connect.php';

echo "<h2>ตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>";

try {
    // ทดสอบการเชื่อมต่อ
    $stmt = query("SELECT 1");
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br><br>";
    
    // ตรวจสอบตาราง users
    $tables = fetchAll("SHOW TABLES");
    echo "📊 ตารางในฐานข้อมูล: " . count($tables) . " ตาราง<br>";
    
    // นับจำนวนผู้ใช้
    $count = fetchOne("SELECT COUNT(*) as total FROM users");
    echo "👥 จำนวนผู้ใช้ในระบบ: " . $count['total'] . " คน<br><br>";
    
    // แสดงรายชื่อผู้ใช้ล่าสุด
    if($count['total'] > 0) {
        $users = fetchAll("SELECT id, username, email, firstname, lastname, created_at FROM users ORDER BY created_at DESC LIMIT 5");
        echo "📝 ผู้ใช้ล่าสุด 5 คน:<br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>ชื่อ-นามสกุล</th><th>วันที่สมัคร</th></tr>";
        foreach($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['firstname'] . " " . $user['lastname'] . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch(Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>