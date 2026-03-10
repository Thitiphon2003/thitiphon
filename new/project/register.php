<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fullname = sanitize($_POST['fullname']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validate
    $errors = [];
    
    if ($password != $confirm_password) {
        $errors[] = "รหัสผ่านไม่ตรงกัน";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    }
    
    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = $conn->query($check_query);
    
    if ($check_result && $check_result->num_rows > 0) {
        $errors[] = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว";
    }
    
    if (empty($errors)) {
        // เก็บรหัสผ่านแบบ plain text (ตามที่คุณใช้)
        $insert_query = "INSERT INTO users (username, password, email, fullname, phone, address, role) 
                         VALUES ('$username', '$password', '$email', '$fullname', '$phone', '$address', 'user')";
        
        if ($conn->query($insert_query)) {
            $user_id = $conn->insert_id;
            
            // Create welcome notification
            $notify_query = "INSERT INTO notifications (user_id, title, message, type) 
                            VALUES ($user_id, 'Welcome to ShopHub!', 'Thank you for registering with us. Start shopping now!', 'system')";
            $conn->query($notify_query);
            
            $_SESSION['register_success'] = "Registration successful! Please sign in.";
            redirect('login.php');
        } else {
            $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก";
        }
    }
}

include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>สร้างบัญชี</h1>
        <p>สมัครสมาชิก ShopHub เพื่อเริ่มช้อปปิ้งได้เลย</p>
    </div>
</div>

<div class="container">
    <div class="auth-container">
        <h2 class="auth-title">สร้างบัญชี</h2>
        
        <?php if (!empty($errors)): ?>
            <div style="background: #fef2f2; color: var(--primary-red); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                <?php foreach ($errors as $err): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div style="background: #fef2f2; color: var(--primary-red); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" class="form-control" id="phone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <small style="color: var(--medium-gray);">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="terms" required> I agree to the <a href="terms.php" style="color: var(--primary-blue);">Terms of Service</a> and <a href="privacy.php" style="color: var(--primary-blue);">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                Create Account
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 2rem;">
            <p style="color: var(--medium-gray);">มีบัญชีอยู่แล้วใช่ไหม? <a href="login.php" style="color: var(--primary-blue); font-weight: 600; text-decoration: none;">เข้าสู่ระบบ</a></p>
        </div>
    </div>
</div>

<?php include 'includes/new-footer.php'; ?>