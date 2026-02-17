<?php
session_start();
require_once 'db_connect.php';

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if(empty($email)) {
        $error = 'กรุณากรอกอีเมล';
    } else {
        $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if($user) {
            // สร้าง token สำหรับรีเซ็ตรหัสผ่าน
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // บันทึก token (ต้องสร้างตาราง password_resets)
            // $sql = "INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)";
            // query($sql, [$email, $token, $expiry]);
            
            // ส่งอีเมล (ตัวอย่าง)
            $reset_link = "http://{$_SERVER['HTTP_HOST']}/project/reset_password.php?token=$token";
            // mail($email, "รีเซ็ตรหัสผ่าน", "คลิกลิงก์: $reset_link");
            
            $message = 'ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณแล้ว';
        } else {
            $error = 'ไม่พบอีเมลนี้ในระบบ';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ลืมรหัสผ่าน</title>
    <meta charset="utf-8">
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
            max-width: 400px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .message {
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
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ลืมรหัสผ่าน?</h1>
        
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="ป้อนอีเมลของคุณ" required>
            </div>
            <button type="submit">ส่งลิงก์รีเซ็ตรหัสผ่าน</button>
        </form>
        
        <div class="back-link">
            <a href="admin_login.php">กลับไปหน้าเข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>