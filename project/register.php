<?php
session_start();
require_once 'db_connect.php';

// ถ้าเข้าสู่ระบบแล้วให้ไปที่หน้าหลัก
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$form_data = [];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $form_data = [
        'username' => trim($_POST['username'] ?? ''),
        'firstname' => trim($_POST['firstname'] ?? ''),
        'lastname' => trim($_POST['lastname'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? null,
        'gender' => $_POST['gender'] ?? 'other',
        'terms' => isset($_POST['terms'])
    ];
    
    // ตรวจสอบข้อมูล
    if(empty($form_data['username'])) {
        $errors['username'] = 'กรุณากรอกชื่อผู้ใช้';
    } elseif(strlen($form_data['username']) < 3) {
        $errors['username'] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors['username'] = 'ชื่อผู้ใช้สามารถใช้ได้เฉพาะตัวอักษร ตัวเลข และ _ เท่านั้น';
    } else {
        $check = fetchOne("SELECT id FROM users WHERE username = ?", [$form_data['username']]);
        if($check) {
            $errors['username'] = 'ชื่อผู้ใช้นี้มีผู้ใช้แล้ว';
        }
    }
    
    if(empty($form_data['firstname'])) {
        $errors['firstname'] = 'กรุณากรอกชื่อ';
    }
    
    if(empty($form_data['lastname'])) {
        $errors['lastname'] = 'กรุณากรอกนามสกุล';
    }
    
    if(empty($form_data['email'])) {
        $errors['email'] = 'กรุณากรอกอีเมล';
    } elseif(!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        $check = fetchOne("SELECT id FROM users WHERE email = ?", [$form_data['email']]);
        if($check) {
            $errors['email'] = 'อีเมลนี้มีผู้ใช้แล้ว';
        }
    }
    
    if(empty($form_data['phone'])) {
        $errors['phone'] = 'กรุณากรอกเบอร์โทรศัพท์';
    } elseif(!preg_match('/^[0-9]{10}$/', $form_data['phone'])) {
        $errors['phone'] = 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก';
    } else {
        $check = fetchOne("SELECT id FROM users WHERE phone = ?", [$form_data['phone']]);
        if($check) {
            $errors['phone'] = 'เบอร์โทรศัพท์นี้มีผู้ใช้แล้ว';
        }
    }
    
    if(empty($form_data['password'])) {
        $errors['password'] = 'กรุณากรอกรหัสผ่าน';
    } elseif(strlen($form_data['password']) < 6) {
        $errors['password'] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }
    
    if($form_data['password'] != $form_data['confirm_password']) {
        $errors['confirm_password'] = 'รหัสผ่านไม่ตรงกัน';
    }
    
    if(!$form_data['terms']) {
        $errors['terms'] = 'กรุณายอมรับข้อกำหนดและเงื่อนไข';
    }
    
    // ถ้าไม่มีข้อผิดพลาด บันทึกข้อมูล
    if(empty($errors)) {
        try {
            // เริ่ม transaction
            $pdo->beginTransaction();
            
            // เข้ารหัสรหัสผ่าน
            $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
            
            // บันทึกผู้ใช้
            $sql = "INSERT INTO users (username, password, email, firstname, lastname, phone, birthdate, gender, level, points, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Bronze', 100, 'active', NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $form_data['username'],
                $hashed_password,
                $form_data['email'],
                $form_data['firstname'],
                $form_data['lastname'],
                $form_data['phone'],
                $form_data['birthdate'],
                $form_data['gender']
            ]);
            
            $user_id = $pdo->lastInsertId();
            
            // บันทึกการสมัครใน logs
            $log_sql = "INSERT INTO system_logs (log_type, user_id, action, description, ip_address, user_agent, created_at) 
                        VALUES ('info', ?, 'register', 'สมัครสมาชิกใหม่', ?, ?, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $user_id,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            // ยืนยัน transaction
            $pdo->commit();
            
            // ไปที่หน้า login
            $_SESSION['register_success'] = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
            header('Location: login.php?registered=success');
            exit();
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $errors['database'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - SHOP.COM</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Kanit', sans-serif;
            background: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand a {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav-icons a {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
        }
        
        .auth-container {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .auth-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
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
        
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            opacity: 0.9;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group label i {
            color: #667eea;
            margin-right: 0.3rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Kanit', sans-serif;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .form-group input.error {
            border-color: #dc3545;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        .password-strength-text {
            font-size: 0.8rem;
            margin-top: 0.3rem;
            text-align: right;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 42px;
            color: #999;
            cursor: pointer;
        }
        
        .terms-group {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
        }
        
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: #555;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 3px;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Kanit', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        
        .btn-register:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .auth-footer {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #e1e5e9;
        }
        
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .footer {
            background: #2d3748;
            color: white;
            padding: 3rem 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #4a5568;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php">SHOP.COM</a>
            </div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.php">หน้าแรก</a></li>
                    <li><a href="index.php#categories">หมวดหมู่</a></li>
                    <li><a href="index.php#products">สินค้าทั้งหมด</a></li>
                    <li><a href="#contact">ติดต่อเรา</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="login.php" class="login-btn">เข้าสู่ระบบ</a>
                    <a href="register.php" class="register-btn">สมัครสมาชิก</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Auth Container -->
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>สมัครสมาชิก</h1>
                <p>สร้างบัญชีเพื่อเริ่มต้นช้อปปิ้ง</p>
            </div>
            
            <div class="auth-body">
                <?php if(!empty($errors)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>กรุณาตรวจสอบข้อมูลอีกครั้ง</span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> ชื่อผู้ใช้</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" 
                               class="<?php echo isset($errors['username']) ? 'error' : ''; ?>" 
                               placeholder="username" required>
                        <?php if(isset($errors['username'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $errors['username']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> ชื่อ</label>
                            <input type="text" name="firstname" value="<?php echo htmlspecialchars($form_data['firstname'] ?? ''); ?>" 
                                   class="<?php echo isset($errors['firstname']) ? 'error' : ''; ?>" 
                                   placeholder="ชื่อ" required>
                            <?php if(isset($errors['firstname'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> นามสกุล</label>
                            <input type="text" name="lastname" value="<?php echo htmlspecialchars($form_data['lastname'] ?? ''); ?>" 
                                   class="<?php echo isset($errors['lastname']) ? 'error' : ''; ?>" 
                                   placeholder="นามสกุล" required>
                            <?php if(isset($errors['lastname'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> อีเมล</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                               class="<?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                               placeholder="example@email.com" required>
                        <?php if(isset($errors['email'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $errors['email']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                               class="<?php echo isset($errors['phone']) ? 'error' : ''; ?>" 
                               placeholder="0812345678" maxlength="10" required>
                        <?php if(isset($errors['phone'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $errors['phone']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> วันเกิด</label>
                            <input type="date" name="birthdate" value="<?php echo htmlspecialchars($form_data['birthdate'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> เพศ</label>
                            <select name="gender">
                                <option value="male" <?php echo ($form_data['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>ชาย</option>
                                <option value="female" <?php echo ($form_data['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>หญิง</option>
                                <option value="other" <?php echo ($form_data['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> รหัสผ่าน</label>
                            <input type="password" name="password" id="password" 
                                   class="<?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                                   placeholder="รหัสผ่านอย่างน้อย 6 ตัว" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password', this)"></i>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrength"></div>
                            </div>
                            <div class="password-strength-text" id="passwordStrengthText"></div>
                            <?php if(isset($errors['password'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> ยืนยันรหัสผ่าน</label>
                            <input type="password" name="confirm_password" id="confirm_password" 
                                   class="<?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" 
                                   placeholder="ยืนยันรหัสผ่าน" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                            <?php if(isset($errors['confirm_password'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['confirm_password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="terms-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                            <span>ข้าพเจ้ายอมรับ <a href="#" onclick="showTerms()">ข้อกำหนดและเงื่อนไข</a> และ <a href="#" onclick="showPrivacy()">นโยบายความเป็นส่วนตัว</a></span>
                        </label>
                        <?php if(isset($errors['terms'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $errors['terms']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn-register" id="registerBtn">
                        <i class="fas fa-user-plus"></i> สมัครสมาชิก
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div id="termsModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 2rem; border-radius: 10px; width: 90%; max-width: 600px; position: relative;">
            <span onclick="closeTermsModal()" style="position: absolute; right: 1rem; top: 1rem; font-size: 2rem; cursor: pointer;">&times;</span>
            <h2>ข้อกำหนดและเงื่อนไข</h2>
            <div style="max-height: 400px; overflow-y: auto; padding: 1rem 0;">
                <h3>1. ข้อตกลงในการให้บริการ</h3>
                <p>การเข้าใช้งานเว็บไซต์นี้ถือว่าคุณยอมรับข้อกำหนดและเงื่อนไขทั้งหมด</p>
                <h3>2. การสมัครสมาชิก</h3>
                <p>คุณต้องให้ข้อมูลที่เป็นความจริง ถูกต้อง และครบถ้วนในการสมัครสมาชิก</p>
                <h3>3. ความรับผิดชอบของผู้ใช้</h3>
                <p>คุณมีหน้าที่รักษาความปลอดภัยของบัญชีผู้ใช้และรหัสผ่าน</p>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div id="privacyModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 2rem; border-radius: 10px; width: 90%; max-width: 600px; position: relative;">
            <span onclick="closePrivacyModal()" style="position: absolute; right: 1rem; top: 1rem; font-size: 2rem; cursor: pointer;">&times;</span>
            <h2>นโยบายความเป็นส่วนตัว</h2>
            <div style="max-height: 400px; overflow-y: auto; padding: 1rem 0;">
                <h3>1. ข้อมูลที่เก็บรวบรวม</h3>
                <p>เราเก็บข้อมูลส่วนบุคคลเพื่อการให้บริการ</p>
                <h3>2. การใช้ข้อมูล</h3>
                <p>เราใช้ข้อมูลเพื่อดำเนินการตามคำสั่งซื้อ</p>
                <h3>3. การเปิดเผยข้อมูล</h3>
                <p>เราจะไม่ขายหรือเปิดเผยข้อมูลของคุณให้บุคคลภายนอก</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 SHOP.COM - ร้านค้าออนไลน์. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId, icon) {
            const input = document.getElementById(fieldId);
            if(input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            if(password.length >= 6) strength += 25;
            if(password.match(/[a-z]+/)) strength += 25;
            if(password.match(/[A-Z]+/)) strength += 25;
            if(password.match(/[0-9]+/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if(strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
                strengthText.textContent = 'รหัสผ่านอ่อน';
                strengthText.style.color = '#dc3545';
            } else if(strength < 75) {
                strengthBar.style.backgroundColor = '#ffc107';
                strengthText.textContent = 'รหัสผ่านปานกลาง';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
                strengthText.textContent = 'รหัสผ่านแข็งแรง';
                strengthText.style.color = '#28a745';
            }
        });
        
        // Check password match
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if(confirm && password !== confirm) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
        
        // Phone number validation
        document.getElementById('phone')?.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Modal functions
        function showTerms() {
            document.getElementById('termsModal').style.display = 'block';
            return false;
        }
        
        function closeTermsModal() {
            document.getElementById('termsModal').style.display = 'none';
        }
        
        function showPrivacy() {
            document.getElementById('privacyModal').style.display = 'block';
            return false;
        }
        
        function closePrivacyModal() {
            document.getElementById('privacyModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if(event.target == document.getElementById('termsModal')) {
                closeTermsModal();
            }
            if(event.target == document.getElementById('privacyModal')) {
                closePrivacyModal();
            }
        };
        
        // Form submit loading
        document.getElementById('registerForm')?.addEventListener('submit', function() {
            document.getElementById('registerBtn').disabled = true;
            document.getElementById('registerBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสมัครสมาชิก...';
        });
    </script>
</body>
</html>