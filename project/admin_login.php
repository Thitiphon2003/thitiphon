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
    $remember = isset($_POST['remember']);
    
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
                    // ตรวจสอบว่าเป็นแอดมินหรือไม่ (ใช้ is_admin)
                    if(isset($user['is_admin']) && $user['is_admin'] == 1) {
                        // อัปเดตการเข้าสู่ระบบ
                        $update_sql = "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?";
                        query($update_sql, [$user['id']]);
                        
                        // ตั้งค่า session
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_name'] = $user['firstname'] . ' ' . $user['lastname'];
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_level'] = $user['level'];
                        
                        // ไปที่หน้า admin
                        header('Location: admin.php');
                        exit();
                    } else {
                        $error = 'คุณไม่มีสิทธิ์เข้าใช้งานระบบแอดมิน (เฉพาะผู้ที่มีสิทธิ์แอดมินเท่านั้น)';
                    }
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                }
            } else {
                $error = 'ไม่พบบัญชีผู้ใช้นี้ในระบบ หรือบัญชีถูกระงับ';
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
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .admin-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            box-shadow: 0 10px 25px rgba(102,126,234,0.3);
        }
        
        .login-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group label i {
            color: #667eea;
            margin-right: 0.5rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i:first-child {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .form-group input::placeholder {
            color: #adb5bd;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            font-size: 1.1rem;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            font-size: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert.error i {
            color: #c33;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
            border: 1px solid #e1e5e9;
        }
        
        .info-box h3 {
            color: #333;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-box h3 i {
            color: #667eea;
        }
        
        .demo-account {
            background: #e9ecef;
            padding: 0.8rem;
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        
        .demo-account p {
            margin: 0.3rem 0;
            color: #555;
        }
        
        .demo-account code {
            background: #2d3748;
            color: #ffd700;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .quick-fix {
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            border-left: 4px solid #28a745;
        }
        
        .quick-fix h4 {
            color: #28a745;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-fix button {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 0.5rem;
            font-size: 1rem;
        }
        
        .quick-fix button:hover {
            background: #218838;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }
            
            .admin-icon {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Admin Login</h1>
            <p>เข้าสู่ระบบสำหรับผู้ดูแลระบบ</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['created']) && $_GET['created'] == 'success'): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                สร้างแอดมินสำเร็จ! กรุณาเข้าสู่ระบบ
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> ชื่อผู้ใช้ หรือ อีเมล
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="ป้อนชื่อผู้ใช้หรืออีเมล"
                        value="<?php echo htmlspecialchars($username); ?>"
                        required
                        autofocus
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> รหัสผ่าน
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="ป้อนรหัสผ่าน"
                        required
                    >
                    <i class="fas fa-eye password-toggle" id="togglePassword" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <span>จดจำฉันไว้</span>
                </label>
                <a href="forgot_password.php" class="forgot-link">
                    <i class="fas fa-question-circle"></i> ลืมรหัสผ่าน?
                </a>
            </div>
            
            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> ข้อมูลสำหรับทดสอบ</h3>
            <div class="demo-account">
                <p><i class="fas fa-user"></i> <strong>Username:</strong> <code>admin</code></p>
                <p><i class="fas fa-lock"></i> <strong>Password:</strong> <code>admin123</code></p>
                <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <code>admin@shop.com</code></p>
            </div>
        </div>
        
        <div class="quick-fix">
            <h4><i class="fas fa-tools"></i> แก้ไขปัญหาด่วน</h4>
            <p>ถ้ายังไม่มีแอดมิน หรือเข้าไม่ได้:</p>
            <form action="add_admin_field.php" method="GET">
                <button type="submit">
                    <i class="fas fa-wrench"></i> ตั้งค่าระบบแอดมินอัตโนมัติ
                </button>
            </form>
        </div>
        
        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
            </a>
        </div>
    </div>

    <script>
        // แสดง/ซ่อนรหัสผ่าน
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');
            
            if(passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // แสดง loading เมื่อกด submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.innerHTML = '<span class="loading"></span> กำลังเข้าสู่ระบบ...';
            btn.disabled = true;
        });
        
        // เติมข้อมูลทดสอบด้วย Ctrl+D
        document.addEventListener('keydown', function(e) {
            if(e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                document.getElementById('username').value = 'admin';
                document.getElementById('password').value = 'admin123';
                document.getElementById('remember').checked = true;
            }
        });
        
        // แสดง tooltip
        const tooltip = document.createElement('div');
        tooltip.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
            z-index: 9999;
            animation: fadeInOut 5s;
        `;
        tooltip.innerHTML = '<i class="fas fa-info-circle"></i> กด Ctrl + D เพื่อเติมข้อมูลทดสอบ';
        document.body.appendChild(tooltip);
        
        setTimeout(() => {
            tooltip.remove();
        }, 5000);
    </script>
</body>
</html>