<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    
    if (empty($form_data['username'])) $errors['username'] = 'กรุณากรอกชื่อผู้ใช้';
    elseif (strlen($form_data['username']) < 3) $errors['username'] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    
    if (empty($form_data['firstname'])) $errors['firstname'] = 'กรุณากรอกชื่อ';
    if (empty($form_data['lastname'])) $errors['lastname'] = 'กรุณากรอกนามสกุล';
    
    if (empty($form_data['email'])) $errors['email'] = 'กรุณากรอกอีเมล';
    elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    
    if (empty($form_data['phone'])) $errors['phone'] = 'กรุณากรอกเบอร์โทรศัพท์';
    elseif (!preg_match('/^[0-9]{10}$/', $form_data['phone'])) $errors['phone'] = 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก';
    
    if (empty($password)) $errors['password'] = 'กรุณากรอกรหัสผ่าน';
    elseif (strlen($password) < 6) $errors['password'] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    
    if ($password != $confirm_password) $errors['confirm_password'] = 'รหัสผ่านไม่ตรงกัน';
    
    if (empty($errors)) {
        $check = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$form_data['username'], $form_data['email']]);
        if ($check) {
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="stat-icon mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h2 class="fw-bold">สมัครสมาชิก</h2>
                        <p class="text-muted">สร้างบัญชีเพื่อเริ่มต้นช้อปปิ้ง</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            กรุณาตรวจสอบข้อมูลอีกครั้ง
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                   name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['firstname']) ? 'is-invalid' : ''; ?>" 
                                       name="firstname" value="<?php echo htmlspecialchars($form_data['firstname'] ?? ''); ?>" required>
                                <?php if (isset($errors['firstname'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['firstname']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['lastname']) ? 'is-invalid' : ''; ?>" 
                                       name="lastname" value="<?php echo htmlspecialchars($form_data['lastname'] ?? ''); ?>" required>
                                <?php if (isset($errors['lastname'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['lastname']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                   name="phone" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" maxlength="10" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">วันเกิด</label>
                                <input type="date" class="form-control" name="birthdate" value="<?php echo htmlspecialchars($form_data['birthdate'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">เพศ</label>
                                <select class="form-select" name="gender">
                                    <option value="male" <?php echo ($form_data['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>ชาย</option>
                                    <option value="female" <?php echo ($form_data['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>หญิง</option>
                                    <option value="other" <?php echo ($form_data['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>อื่นๆ</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           name="password" id="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           name="confirm_password" id="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    ยอมรับ <a href="#" class="text-primary">ข้อกำหนดและเงื่อนไข</a>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                            <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">มีบัญชีอยู่แล้ว? <a href="login.php" class="text-primary fw-bold">เข้าสู่ระบบ</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ตรวจสอบความแข็งแรงของรหัสผ่าน
document.getElementById('password')?.addEventListener('input', function() {
    const strength = checkPasswordStrength(this.value);
    // สามารถเพิ่ม indicator ได้
});
</script>

<?php include 'includes/footer.php'; ?>