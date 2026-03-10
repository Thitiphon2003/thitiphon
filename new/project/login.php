<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password == $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                redirect('admin/');
            } else {
                redirect('index.php');
            }
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบผู้ใช้นี้";
    }
}

include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>ยินดีต้อนรับสู่ ShopHub</h1>
        <p>เข้าสู่ระบบบัญชีของคุณเพื่อดำเนินการช้อปปิ้งต่อ</p>
    </div>
</div>

<div class="container">
    <div class="auth-container">
        <h2 class="auth-title">เข้าสู่ระบบ</h2>
        
        <?php if (isset($error)): ?>
            <div style="background: #fef2f2; color: var(--primary-red); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border-left: 4px solid var(--primary-red);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['register_success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border-left: 4px solid #28a745;">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--medium-gray);"></i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="ป้อนชื่อผู้ใช้หรืออีเมลของคุณ" required style="padding-left: 3rem;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--medium-gray);"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="ป้อนรหัสผ่านของคุณ" required style="padding-left: 3rem;">
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <label>
                    <input type="checkbox" name="remember"> จดจำฉัน
                </label>
                <a href="forgot-password.php" style="color: var(--primary-blue); text-decoration: none;">ลืมรหัสผ่าน?</a>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--light-gray);">
            <p style="color: var(--medium-gray);">ไม่มีบัญชีใช่ไหม? <a href="register.php" style="color: var(--primary-blue); font-weight: 600; text-decoration: none;">สร้างบัญชี</a></p>
        </div>
        
        <!-- Demo Accounts -->
        <div style="margin-top: 2rem; background: var(--light-gray); padding: 1rem; border-radius: 10px;">
            <p style="font-weight: 600; margin-bottom: 0.5rem;">Demo Accounts:</p>
            <p>Admin: admin / 12345</p>
            <p>User: user1 / 123456</p>
        </div>
    </div>
</div>

<?php include 'includes/new-footer.php'; ?>