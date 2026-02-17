<?php
require_once 'db_connect.php';

// ข้อมูลแอดมินที่จะเพิ่ม
$admins = [
    [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'email' => 'admin@shop.com',
        'firstname' => 'Admin',
        'lastname' => 'System',
        'phone' => '0999999999',
        'level' => 'admin',
        'status' => 'active'
    ],
    [
        'username' => 'thitiphon',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'email' => 'thitiphon@shop.com',
        'firstname' => 'ฐิติพร',
        'lastname' => 'แอดมิน',
        'phone' => '0888888888',
        'level' => 'admin',
        'status' => 'active'
    ]
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>สร้างแอดมิน</title>
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
        h1 {
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .info {
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }
        th {
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>👤 สร้างบัญชีแอดมิน</h1>";
        
        // ตรวจสอบการเชื่อมต่อฐานข้อมูล
        try {
            // ตรวจสอบว่ามีตาราง users หรือไม่
            $tables = fetchAll("SHOW TABLES LIKE 'users'");
            if(count($tables) == 0) {
                echo "<div class='error'>❌ ไม่พบตาราง users ในฐานข้อมูล กรุณาสร้างฐานข้อมูลก่อน</div>";
                echo "<div class='warning'>⚠️ วิธีแก้ไข: ให้รันไฟล์ SQL ที่ให้ไว้ก่อนหน้านี้</div>";
                echo "<a href='javascript:history.back()' class='btn'>← กลับ</a>";
                exit();
            }
            
            $success_count = 0;
            $error_count = 0;
            
            foreach($admins as $admin) {
                // ตรวจสอบว่ามี username หรือ email ซ้ำหรือไม่
                $check = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", 
                                  [$admin['username'], $admin['email']]);
                
                if($check) {
                    echo "<div class='error'>⚠️ ผู้ใช้ " . $admin['username'] . " (" . $admin['email'] . ") มีอยู่แล้วในระบบ</div>";
                    $error_count++;
                } else {
                    // เพิ่มแอดมินใหม่
                    $sql = "INSERT INTO users (username, password, email, firstname, lastname, phone, level, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
                    query($sql, [
                        $admin['username'],
                        $admin['password'],
                        $admin['email'],
                        $admin['firstname'],
                        $admin['lastname'],
                        $admin['phone'],
                        $admin['level'],
                        $admin['status']
                    ]);
                    
                    echo "<div class='success'>✅ เพิ่มแอดมิน " . $admin['username'] . " เรียบร้อยแล้ว</div>";
                    $success_count++;
                }
            }
            
            echo "<div class='info'>";
            echo "<strong>📊 สรุปการเพิ่มแอดมิน:</strong><br>";
            echo "✅ สำเร็จ: " . $success_count . " รายการ<br>";
            echo "⚠️ ซ้ำ/ผิดพลาด: " . $error_count . " รายการ";
            echo "</div>";
            
            // แสดงรายการแอดมินทั้งหมดในระบบ
            $all_admins = fetchAll("SELECT id, username, email, firstname, lastname, level, status, created_at 
                                   FROM users WHERE level = 'admin' OR level = 'Admin' 
                                   ORDER BY id DESC");
            
            if(count($all_admins) > 0) {
                echo "<h2>📋 รายชื่อแอดมินในระบบ</h2>";
                echo "<table>";
                echo "<tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                        <th>ระดับ</th>
                        <th>สถานะ</th>
                      </tr>";
                
                foreach($all_admins as $admin) {
                    echo "<tr>";
                    echo "<td>" . $admin['id'] . "</td>";
                    echo "<td>" . $admin['username'] . "</td>";
                    echo "<td>" . $admin['firstname'] . " " . $admin['lastname'] . "</td>";
                    echo "<td>" . $admin['email'] . "</td>";
                    echo "<td>" . $admin['level'] . "</td>";
                    echo "<td><span style='color: " . ($admin['status'] == 'active' ? 'green' : 'red') . "'>" . $admin['status'] . "</span></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ ยังไม่มีแอดมินในระบบ</div>";
            }
            
        } catch(Exception $e) {
            echo "<div class='error'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
        }
        
        echo "<div style='margin-top: 2rem;'>";
        echo "<h3>🔑 ข้อมูลการเข้าสู่ระบบ:</h3>";
        echo "<table>";
        echo "<tr><th>Username</th><th>Password</th><th>Email</th></tr>";
        echo "<tr><td>admin</td><td>admin123</td><td>admin@shop.com</td></tr>";
        echo "<tr><td>thitiphon</td><td>admin123</td><td>thitiphon@shop.com</td></tr>";
        echo "</table>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin-top: 2rem;'>";
        echo "<a href='admin_login.php' class='btn'><i class='fas fa-sign-in-alt'></i> ไปที่หน้า Login</a> ";
        echo "<a href='index.php' class='btn' style='background: #6c757d;'>กลับหน้าหลัก</a>";
        echo "</div>";
        
echo "</div>
</body>
</html>";
?>