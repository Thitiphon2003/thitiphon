<?php
session_start();
require_once 'db_connect.php';

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $user = fetchOne("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'", [$username, $username]);
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}

$page_title = 'เข้าสู่ระบบ';
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>เข้าสู่ระบบ</h1>
            <p>ยินดีต้อนรับกลับ! กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['register_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>ชื่อผู้ใช้ หรือ อีเมล</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>รหัสผ่าน</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" required>
                    <i class="fas fa-eye" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('password', this)"></i>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">เข้าสู่ระบบ</button>
        </form>
        
        <div class="auth-footer">
            <p>ยังไม่มีบัญชีผู้ใช้?</p>
            <a href="register.php">สมัครสมาชิก</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>