<?php
require_once 'db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>ตรวจสอบรหัสผ่านแอดมิน</title>
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
        h2 { color: #555; }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
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
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #5a67d8;
        }
        input[type=text], input[type=password] {
            padding: 10px;
            width: 300px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 ตรวจสอบรหัสผ่านแอดมิน</h1>";

        // ตรวจสอบการเชื่อมต่อ
        try {
            // 1. ตรวจสอบว่ามีตาราง users หรือไม่
            $tables = fetchAll("SHOW TABLES");
            $has_users = false;
            foreach($tables as $table) {
                if(in_array('users', $table)) {
                    $has_users = true;
                    break;
                }
            }
            
            if(!$has_users) {
                echo "<div class='error'>❌ ไม่พบตาราง users ในฐานข้อมูล</div>";
                echo "<div class='warning'>👉 กรุณารันไฟล์ SQL เพื่อสร้างตารางก่อน</div>";
                exit();
            }
            
            // 2. ตรวจสอบข้อมูลแอดมินในฐานข้อมูล
            echo "<div class='card'>";
            echo "<h2>📋 ข้อมูลแอดมินในฐานข้อมูล</h2>";
            
            $admins = fetchAll("SELECT id, username, email, password, firstname, lastname, level, status FROM users WHERE level = 'admin' OR level = 'Admin'");
            
            if(count($admins) > 0) {
                echo "<table>";
                echo "<tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Password Hash</th>
                      </tr>";
                
                foreach($admins as $admin) {
                    echo "<tr>";
                    echo "<td>" . $admin['id'] . "</td>";
                    echo "<td>" . $admin['username'] . "</td>";
                    echo "<td>" . $admin['firstname'] . " " . $admin['lastname'] . "</td>";
                    echo "<td>" . $admin['email'] . "</td>";
                    echo "<td>" . $admin['level'] . "</td>";
                    echo "<td>" . $admin['status'] . "</td>";
                    echo "<td><small>" . substr($admin['password'], 0, 20) . "...</small></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ ยังไม่มีแอดมินในระบบ</div>";
                echo "<a href='create_admin.php' class='btn'>➕ สร้างแอดมิน</a>";
            }
            echo "</div>";
            
            // 3. ฟอร์มทดสอบรหัสผ่าน
            echo "<div class='card'>";
            echo "<h2>🔑 ทดสอบรหัสผ่าน</h2>";
            
            if(isset($_POST['test_password'])) {
                $test_username = $_POST['username'];
                $test_password = $_POST['password'];
                
                $user = fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$test_username, $test_username]);
                
                if($user) {
                    echo "<div class='info'>พบผู้ใช้: " . $user['username'] . "</div>";
                    
                    if(password_verify($test_password, $user['password'])) {
                        echo "<div class='success'>✅ รหัสผ่านถูกต้อง!</div>";
                        
                        // แสดงข้อมูลที่ควรจะอยู่ใน session
                        echo "<div class='success'>ข้อมูลที่ควรได้:<br>";
                        echo "- user_id: " . $user['id'] . "<br>";
                        echo "- username: " . $user['username'] . "<br>";
                        echo "- fullname: " . $user['firstname'] . " " . $user['lastname'] . "<br>";
                        echo "- level: " . $user['level'] . "</div>";
                    } else {
                        echo "<div class='error'>❌ รหัสผ่านไม่ถูกต้อง</div>";
                        
                        // ทดสอบรหัสผ่านที่ควรจะเป็น
                        $test_hashes = [
                            'admin123' => password_hash('admin123', PASSWORD_DEFAULT),
                            '123456' => password_hash('123456', PASSWORD_DEFAULT),
                            'password' => password_hash('password', PASSWORD_DEFAULT)
                        ];
                        
                        echo "<div class='warning'>💡 รหัสผ่านที่นิยมใช้: admin123, 123456, password</div>";
                    }
                } else {
                    echo "<div class='error'>❌ ไม่พบผู้ใช้: " . $test_username . "</div>";
                }
            }
            
            echo "<form method='POST'>";
            echo "<div style='margin: 10px 0'>";
            echo "<label>Username หรือ Email:</label><br>";
            echo "<input type='text' name='username' placeholder='admin' required>";
            echo "</div>";
            echo "<div style='margin: 10px 0'>";
            echo "<label>รหัสผ่าน:</label><br>";
            echo "<input type='password' name='password' placeholder='admin123' required>";
            echo "</div>";
            echo "<button type='submit' name='test_password'>ทดสอบรหัสผ่าน</button>";
            echo "</form>";
            echo "</div>";
            
            // 4. เครื่องมือสร้างรหัสผ่านใหม่
            echo "<div class='card'>";
            echo "<h2>🔄 ตั้งรหัสผ่านใหม่</h2>";
            
            if(isset($_POST['reset_password'])) {
                $reset_username = $_POST['reset_username'];
                $new_password = $_POST['new_password'];
                
                $user = fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$reset_username, $reset_username]);
                
                if($user) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $user['id']]);
                    
                    echo "<div class='success'>✅ ตั้งรหัสผ่านใหม่ให้ " . $user['username'] . " เรียบร้อยแล้ว</div>";
                    echo "<div class='info'>รหัสผ่านใหม่: " . $new_password . "</div>";
                } else {
                    echo "<div class='error'>❌ ไม่พบผู้ใช้</div>";
                }
            }
            
            echo "<form method='POST'>";
            echo "<div style='margin: 10px 0'>";
            echo "<label>Username หรือ Email:</label><br>";
            echo "<input type='text' name='reset_username' placeholder='admin' required>";
            echo "</div>";
            echo "<div style='margin: 10px 0'>";
            echo "<label>รหัสผ่านใหม่:</label><br>";
            echo "<input type='text' name='new_password' value='admin123' required>";
            echo "</div>";
            echo "<button type='submit' name='reset_password' style='background: #dc3545;'>ตั้งรหัสผ่านใหม่</button>";
            echo "</form>";
            echo "</div>";
            
        } catch(Exception $e) {
            echo "<div class='error'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
        }
        
        echo "<div style='text-align: center; margin-top: 20px;'>";
        echo "<a href='admin_login.php' class='btn'>ไปที่หน้า Login</a>";
        echo "<a href='create_admin.php' class='btn'>สร้างแอดมินใหม่</a>";
        echo "<a href='index.php' class='btn'>กลับหน้าหลัก</a>";
        echo "</div>";
        
echo "</div>
</body>
</html>";
?>