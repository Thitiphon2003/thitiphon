<?php
// ตรวจสอบการเชื่อมต่อฐานข้อมูลและสร้างแอดมิน
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>แก้ไขปัญหาแอดมิน</title>
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
        h1 {
            color: #333;
        }
        h2 {
            color: #555;
            font-size: 1.3rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 5px;
            border-radius: 3px;
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
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 เครื่องมือแก้ไขปัญหาแอดมิน</h1>";

        // 1. ตรวจสอบไฟล์ db_connect.php
        echo "<div class='card'>";
        echo "<h2>📁 1. ตรวจสอบไฟล์ db_connect.php</h2>";
        
        if(file_exists('db_connect.php')) {
            echo "<div class='success'>✅ พบไฟล์ db_connect.php</div>";
            
            // ทดสอบการเชื่อมต่อ
            try {
                require_once 'db_connect.php';
                echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูลสำเร็จ</div>";
                
                // ตรวจสอบตาราง users
                $tables = fetchAll("SHOW TABLES");
                echo "<div>📊 พบ " . count($tables) . " ตารางในฐานข้อมูล</div>";
                
                // ตรวจสอบว่ามีตาราง users หรือไม่
                $has_users = false;
                foreach($tables as $table) {
                    if(in_array('users', $table)) {
                        $has_users = true;
                        break;
                    }
                }
                
                if($has_users) {
                    echo "<div class='success'>✅ พบตาราง users</div>";
                    
                    // ตรวจสอบแอดมิน
                    $admins = fetchAll("SELECT * FROM users WHERE level = 'admin' OR level = 'Admin'");
                    
                    if(count($admins) > 0) {
                        echo "<div class='success'>✅ พบแอดมิน " . count($admins) . " คนในระบบ</div>";
                        echo "<ul>";
                        foreach($admins as $admin) {
                            echo "<li>" . $admin['username'] . " (" . $admin['email'] . ") - " . $admin['status'] . "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<div class='warning'>⚠️ ยังไม่พบแอดมินในระบบ</div>";
                        echo "<a href='create_admin.php' class='btn'>➕ สร้างแอดมิน</a>";
                    }
                } else {
                    echo "<div class='error'>❌ ไม่พบตาราง users กรุณาสร้างฐานข้อมูลก่อน</div>";
                }
                
            } catch(Exception $e) {
                echo "<div class='error'>❌ ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>❌ ไม่พบไฟล์ db_connect.php</div>";
            echo "<p>กรุณาสร้างไฟล์ db_connect.php ในโฟลเดอร์เดียวกัน</p>";
            echo "<pre>
&lt;?php
// db_connect.php
\$host = 'localhost';
\$dbname = 'shop_db';
\$username = 'root';
\$password = '';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    function query(\$sql, \$params = []) {
        global \$pdo;
        \$stmt = \$pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt;
    }
    
    function fetchOne(\$sql, \$params = []) {
        return query(\$sql, \$params)->fetch();
    }
    
} catch(PDOException \$e) {
    die(\"Connection failed: \" . \$e->getMessage());
}
?&gt;
            </pre>";
        }
        echo "</div>";
        
        // 2. คำแนะนำ
        echo "<div class='card'>";
        echo "<h2>📝 คำแนะนำ</h2>";
        echo "<ol>";
        echo "<li>ตรวจสอบว่า XAMPP เปิดอยู่ (Apache และ MySQL)</li>";
        echo "<li>ตรวจสอบว่าสร้างฐานข้อมูล shop_db แล้ว</li>";
        echo "<li>รันไฟล์ SQL เพื่อสร้างตาราง</li>";
        echo "<li>เข้าไปที่ <a href='create_admin.php'>create_admin.php</a> เพื่อสร้างแอดมิน</li>";
        echo "<li>เข้าสู่ระบบที่ <a href='admin_login.php'>admin_login.php</a> ด้วย username: admin, password: admin123</li>";
        echo "</ol>";
        echo "</div>";
        
        // 3. ลิงก์ด่วน
        echo "<div class='card'>";
        echo "<h2>🔗 ลิงก์ด่วน</h2>";
        echo "<a href='create_admin.php' class='btn'>สร้างแอดมิน</a>";
        echo "<a href='admin_login.php' class='btn'>หน้า Login แอดมิน</a>";
        echo "<a href='index.php' class='btn'>หน้าหลัก</a>";
        echo "</div>";
        
echo "</div>
</body>
</html>";
?>