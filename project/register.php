<?php
session_start();
require_once 'db_connect.php';

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$form_data = [];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'username' => trim($_POST['username'] ?? ''),
        'firstname' => trim($_POST['firstname'] ?? ''),
        'lastname' => trim($_POST['lastname'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'birthdate' => $_POST['birthdate'] ?? '',
        'gender' => $_POST['gender'] ?? 'other'
    ];
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if(empty($form_data['username'])) $errors['username'] = 'กรุณากรอกชื่อผู้ใช้';
    elseif(strlen($form_data['username']) < 3) $errors['username'] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    
    if(empty($form_data['firstname'])) $errors['firstname'] = 'กรุณากรอกชื่อ';
    if(empty($form_data['lastname'])) $errors['lastname'] = 'กรุณากรอกนามสกุล';
    
    if(empty($form_data['email'])) $errors['email'] = 'กรุณากรอกอีเมล';
    elseif(!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    
    if(empty($form_data['phone'])) $errors['phone'] = 'กรุณากรอกเบอร์โทรศัพท์';
    elseif(!preg_match('/^[0-9]{10}$/', $form_data['phone'])) $errors['phone'] = 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก';
    
    if(empty($password)) $errors['password'] = 'กรุณากรอกรหัสผ่าน';
    elseif(strlen($password) < 6) $errors['password'] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    
    if($password != $confirm_password) $errors['confirm_password'] = 'รหัสผ่านไม่ตรงกัน';
    
    if(empty($errors)) {
        $check = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$form_data['username'], $form_data['email']]);
        if($check) {
            $errors['duplicate'] = 'ชื่อผู้ใช้หรืออีเมลนี้มีผู้ใช้แล้ว';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, email, firstname, lastname, phone, birthdate, gender, level, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Bronze', 'active', NOW())";
            query($sql, [
                $form_data['username'], 
                $hashed_password, 
                $form_data['email'], 
                $form_data['firstname'], 
                $form_data['lastname'], 
                $form_data['phone'],
                $form_data['birthdate'] ?: null,
                $form_data['gender']
            ]);
            
            $_SESSION['register_success'] = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
            header('Location: login.php');
            exit();
        }
    }
}

$page_title = 'สมัครสมาชิก';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box" style="max-width: 550px;">
        <div class="auth-header">
            <h1>สมัครสมาชิก</h1>
            <p>สร้างบัญชีผู้ใช้เพื่อเริ่มต้นช้อปปิ้ง</p>
        </div>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                กรุณาตรวจสอบข้อมูลอีกครั้ง
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>ชื่อผู้ใช้</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" class="<?php echo isset($errors['username']) ? 'error' : ''; ?>" required>
                <?php if(isset($errors['username'])): ?>
                    <div class="error-message"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>ชื่อ</label>
                    <input type="text" name="firstname" value="<?php echo htmlspecialchars($form_data['firstname'] ?? ''); ?>" class="<?php echo isset($errors['firstname']) ? 'error' : ''; ?>" required>
                    <?php if(isset($errors['firstname'])): ?>
                        <div class="error-message"><?php echo $errors['firstname']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>นามสกุล</label>
                    <input type="text" name="lastname" value="<?php echo htmlspecialchars($form_data['lastname'] ?? ''); ?>" class="<?php echo isset($errors['lastname']) ? 'error' : ''; ?>" required>
                    <?php if(isset($errors['lastname'])): ?>
                        <div class="error-message"><?php echo $errors['lastname']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label>อีเมล</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" class="<?php echo isset($errors['email']) ? 'error' : ''; ?>" required>
                <?php if(isset($errors['email'])): ?>
                    <div class="error-message"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>เบอร์โทรศัพท์</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" class="<?php echo isset($errors['phone']) ? 'error' : ''; ?>" maxlength="10" required>
                <?php if(isset($errors['phone'])): ?>
                    <div class="error-message"><?php echo $errors['phone']; ?></div>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>วันเกิด</label>
                    <input type="date" name="birthdate" value="<?php echo htmlspecialchars($form_data['birthdate'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>เพศ</label>
                    <select name="gender">
                        <option value="male" <?php echo ($form_data['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>ชาย</option>
                        <option value="female" <?php echo ($form_data['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>หญิง</option>
                        <option value="other" <?php echo ($form_data['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>อื่นๆ</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" class="<?php echo isset($errors['password']) ? 'error' : ''; ?>" required>
                        <i class="fas fa-eye" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('password', this)"></i>
                    </div>
                    <?php if(isset($errors['password'])): ?>
                        <div class="error-message"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>ยืนยันรหัสผ่าน</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="confirm_password" class="<?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" required>
                        <i class="fas fa-eye" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    <?php if(isset($errors['confirm_password'])): ?>
                        <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">สมัครสมาชิก</button>
        </form>
        
        <div class="auth-footer">
            <p>มีบัญชีผู้ใช้แล้ว?</p>
            <a href="login.php">เข้าสู่ระบบ</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>