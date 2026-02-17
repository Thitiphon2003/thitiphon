<?php
session_start();
require_once 'db_connect.php';

// ถ้าเข้าสู่ระบบแล้วให้ไปที่หน้าหลัก
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($login) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้/อีเมลและรหัสผ่าน';
    } else {
        try {
            // ค้นหาผู้ใช้จาก username หรือ email
            $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
            $user = fetchOne($sql, [$login, $login]);
            
            if($user && password_verify($password, $user['password'])) {
                // อัปเดตการเข้าสู่ระบบ
                $update_sql = "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?";
                query($update_sql, [$user['id']]);
                
                // ตั้งค่า session ให้ครบถ้วน
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['points'] = $user['points'];
                
                // ไปที่หน้าหลัก
                $redirect = $_GET['redirect'] ?? 'index.php';
                header("Location: $redirect");
                exit();
                
            } else {
                $error = 'ชื่อผู้ใช้/อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch(Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - SHOP.COM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>เข้าสู่ระบบ</h1>
            <p>ยินดีต้อนรับกลับ!</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>ชื่อผู้ใช้ หรือ อีเมล</label>
                <input type="text" name="login" required>
            </div>
            
            <div class="form-group">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
        </form>
        
        <div class="register-link">
            ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
        </div>
    </div>
</body>
</html>