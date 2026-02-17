<?php
require_once 'db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>เพิ่มฟิลด์แอดมิน</title>
    <meta charset='utf-8'>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 1rem; }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 1rem; 
            border-radius: 8px; 
            margin: 1rem 0;
        }
        .info { 
            background: #e8f5e9; 
            padding: 1rem; 
            border-radius: 8px; 
            margin: 1rem 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn-success {
            background: #28a745;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>👑 ตั้งค่าระบบแอดมิน</h1>";

try {
    // 1. ตรวจสอบและเพิ่มฟิลด์ is_admin
    $check = fetchOne("SHOW COLUMNS FROM users LIKE 'is_admin'");
    
    if(!$check) {
        query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER level");
        echo "<div class='success'>✅ เพิ่มฟิลด์ is_admin เรียบร้อยแล้ว</div>";
    } else {
        echo "<div class='success'>✅ มีฟิลด์ is_admin อยู่แล้ว</div>";
    }
    
    // 2. ตรวจสอบและสร้างแอดมิน
    $admin_user = fetchOne("SELECT * FROM users WHERE username = 'admin'");
    
    if($admin_user) {
        // อัปเกรดผู้ใช้ admin เป็นแอดมิน
        query("UPDATE users SET is_admin = 1 WHERE username = 'admin'");
        echo "<div class='success'>✅ ตั้งค่าให้ผู้ใช้ 'admin' เป็นแอดมินเรียบร้อย</div>";
    } else {
        // สร้างแอดมินใหม่
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, firstname, lastname, level, is_admin, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'Platinum', 1, 'active', NOW())";
        query($sql, ['admin', $password, 'admin@shop.com', 'Admin', 'System']);
        echo "<div class='success'>✅ สร้างแอดมินใหม่ 'admin' เรียบร้อย</div>";
    }
    
    // 3. แสดงรายชื่อแอดมินทั้งหมด
    $admins = fetchAll("SELECT id, username, email, firstname, lastname, level, is_admin FROM users WHERE is_admin = 1");
    
    if(count($admins) > 0) {
        echo "<h3>📋 รายชื่อแอดมินในระบบ</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>ชื่อ-นามสกุล</th><th>Email</th><th>Level</th></tr>";
        foreach($admins as $a) {
            echo "<tr>";
            echo "<td>" . $a['id'] . "</td>";
            echo "<td>" . $a['username'] . "</td>";
            echo "<td>" . $a['firstname'] . " " . $a['lastname'] . "</td>";
            echo "<td>" . $a['email'] . "</td>";
            echo "<td>" . $a['level'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div class='info'>";
    echo "<strong>🔑 ข้อมูลการเข้าสู่ระบบ:</strong><br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong><br>";
    echo "Email: <strong>admin@shop.com</strong><br>";
    echo "</div>";
    
} catch(Exception $e) {
    echo "<div class='success' style='background: #f8d7da; color: #721c24;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
}

echo "<div style='text-align: center; margin-top: 2rem;'>";
echo "<a href='admin_login.php' class='btn btn-success'>ไปที่หน้า Login</a>";
echo "<a href='index.php' class='btn'>กลับหน้าหลัก</a>";
echo "</div>";

echo "</div>
</body>
</html>";
?>