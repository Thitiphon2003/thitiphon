<?php
require_once 'db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>ตรวจสอบแอดมิน</title>
    <meta charset='utf-8'>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
        }
        .admin-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        .user-badge {
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
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
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 ตรวจสอบข้อมูลแอดมิน</h1>";

        // ดึงข้อมูลผู้ใช้ทั้งหมด
        $users = fetchAll("SELECT id, username, email, firstname, lastname, level, status FROM users ORDER BY id");
        
        echo "<div class='card'>";
        echo "<h2>📋 รายชื่อผู้ใช้ทั้งหมด</h2>";
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Username</th>
                <th>ชื่อ-นามสกุล</th>
                <th>Email</th>
                <th>Level</th>
                <th>สถานะ</th>
                <th>สิทธิ์</th>
              </tr>";
        
        foreach($users as $user) {
            $is_admin = in_array($user['level'], ['admin', 'Admin', 'ADMIN', 'administrator']);
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['firstname'] . " " . $user['lastname'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td><strong>" . $user['level'] . "</strong></td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "<td>" . ($is_admin ? "<span class='admin-badge'>Admin</span>" : "<span class='user-badge'>User</span>") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // ตรวจสอบผู้ใช้ 'admin' โดยเฉพาะ
        $admin_user = fetchOne("SELECT * FROM users WHERE username = 'admin' OR email = 'admin@shop.com'");
        
        echo "<div class='card'>";
        echo "<h2>👤 ตรวจสอบผู้ใช้ 'admin'</h2>";
        
        if($admin_user) {
            echo "<table>";
            echo "<tr><th>ฟิลด์</th><th>ค่า</th></tr>";
            foreach($admin_user as $key => $value) {
                if($key != 'password') {
                    echo "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>";
                } else {
                    echo "<tr><td>" . $key . "</td><td><code>" . substr($value, 0, 30) . "...</code></td></tr>";
                }
            }
            echo "</table>";
            
            // ตรวจสอบว่าเป็นแอดมินหรือไม่
            $is_admin = in_array($admin_user['level'], ['admin', 'Admin', 'ADMIN', 'administrator']);
            
            if($is_admin) {
                echo "<p style='color: green; font-weight: bold;'>✅ ผู้ใช้ 'admin' มีสิทธิ์แอดมินแล้ว (Level: " . $admin_user['level'] . ")</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ ผู้ใช้ 'admin' ยังไม่มีสิทธิ์แอดมิน (Level: " . $admin_user['level'] . ")</p>";
                echo "<p>👉 กรุณาอัปเกรด Level เป็น 'admin'</p>";
                echo "<a href='upgrade_to_admin.php?user_id=" . $admin_user['id'] . "' class='btn btn-success'>อัปเกรดเป็นแอดมิน</a>";
            }
        } else {
            echo "<p style='color: red;'>❌ ไม่พบผู้ใช้ 'admin' ในระบบ</p>";
            echo "<a href='create_admin.php' class='btn btn-success'>สร้างแอดมินใหม่</a>";
        }
        echo "</div>";
        
        echo "<div style='text-align: center; margin-top: 20px;'>";
        echo "<a href='admin_login.php' class='btn'>กลับไปหน้า Login</a>";
        echo "<a href='create_admin.php' class='btn btn-success'>สร้างแอดมินใหม่</a>";
        echo "<a href='index.php' class='btn btn-warning'>หน้าหลัก</a>";
        echo "</div>";
        
echo "</div>
</body>
</html>";
?>