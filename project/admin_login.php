<?php
session_start();
require_once 'db_connect.php';

// ถ้าเป็นแอดมินอยู่แล้ว ให้ไปที่หน้า admin
if(isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit();
}

$error = '';
$username = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            // ค้นหาผู้ใช้จาก username หรือ email
            $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
            $user = fetchOne($sql, [$username, $username]);
            
            if($user) {
                // ตรวจสอบรหัสผ่าน
                if(password_verify($password, $user['password'])) {
                    // ตรวจสอบว่าเป็นแอดมินหรือไม่ (รองรับหลายรูปแบบ)
                    $is_admin = false;
                    $admin_levels = ['admin', 'Admin', 'ADMIN', 'administrator', 'Administrator'];
                    
                    if(in_array($user['level'], $admin_levels) || (isset($user['is_admin']) && $user['is_admin'] == 1)) {
                        $is_admin = true;
                    }
                    
                    if($is_admin) {
                        // อัปเดตการเข้าสู่ระบบ
                        $update_sql = "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?";
                        query($update_sql, [$user['id']]);
                        
                        // ตั้งค่า session
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_name'] = $user['firstname'] . ' ' . $user['lastname'];
                        $_SESSION['admin_email'] = $user['email'];
                        
                        // ไปที่หน้า admin
                        header('Location: admin.php');
                        exit();
                    } else {
                        $error = 'คุณไม่มีสิทธิ์เข้าใช้งานระบบแอดมิน';
                    }
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                }
            } else {
                $error = 'ไม่พบบัญชีผู้ใช้นี้ในระบบ';
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
    <title>Admin Login - SHOP.COM</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .demo-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            border: 1px solid #e2e8f0;
        }
        .demo-info p {
            margin-bottom: 0.3rem;
            color: #666;
        }
        .demo-info code {
            background: #e2e8f0;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            color: #333;
        }
        .alert {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-store fa-2x text-primary mb-3"></i>
            <h1>SHOP.COM</h1>
            <p>Admin Login</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">ชื่อผู้ใช้ หรือ อีเมล</label>
                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
            </button>
        </form>
        
        <div class="demo-info">
            <p class="fw-bold mb-2"><i class="fas fa-info-circle me-1"></i> ข้อมูลทดสอบ</p>
            <p><code>admin</code> / <code>admin123</code></p>
            <p class="mt-2 mb-0"><small>หากยังไม่มีบัญชีแอดมิน กรุณาไปที่ <a href="create_admin.php" class="text-primary">create_admin.php</a></small></p>
        </div>
        
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left me-1"></i>กลับหน้าหลัก</a>
        </div>
    </div>
</body>
</html>