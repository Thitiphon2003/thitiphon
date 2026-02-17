<?php
require_once 'db_connect.php';

$message = '';
$error = '';

// อัปเกรดจากฟอร์ม
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    
    if($username) {
        $user = fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username]);
        
        if($user) {
            query("UPDATE users SET level = 'admin' WHERE id = ?", [$user['id']]);
            $message = "อัปเกรดผู้ใช้ " . $user['username'] . " เป็นแอดมินเรียบร้อยแล้ว";
        } else {
            $error = "ไม่พบผู้ใช้: " . $username;
        }
    }
}

// อัปเกรดจาก URL
if(isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
    
    if($user) {
        query("UPDATE users SET level = 'admin' WHERE id = ?", [$user_id]);
        $message = "อัปเกรดผู้ใช้ " . $user['username'] . " เป็นแอดมินเรียบร้อยแล้ว";
    } else {
        $error = "ไม่พบผู้ใช้ ID: " . $user_id;
    }
}

// อัปเกรดทั้งหมดที่มี username ขึ้นต้นด้วย admin
if(isset($_GET['all_admin'])) {
    query("UPDATE users SET level = 'admin' WHERE username LIKE 'admin%'");
    $message = "อัปเกรดผู้ใช้ที่ขึ้นต้นด้วย admin ทั้งหมดเรียบร้อยแล้ว";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>อัปเกรดเป็นแอดมิน</title>
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
            max-width: 500px;
            width: 100%;
        }
        h1 { color: #333; }
        .success {
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
        .form-group {
            margin-bottom: 1rem;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        button {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👑 อัปเกรดเป็นแอดมิน</h1>
        
        <?php if($message): ?>
            <div class="success">✅ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>ป้อนชื่อผู้ใช้หรืออีเมลที่ต้องการอัปเกรด:</label>
                <input type="text" name="username" placeholder="เช่น admin" required>
            </div>
            <button type="submit">อัปเกรดเป็นแอดมิน</button>
        </form>
        
        <hr style="margin: 20px 0;">
        
        <div style="text-align: center;">
            <a href="?all_admin=1" class="btn" style="background: #ffc107; color: #333;">อัปเกรด admin ทุกคน</a>
            <a href="check_admin.php" class="btn">ตรวจสอบแอดมิน</a>
            <a href="admin_login.php" class="btn">ไปหน้า Login</a>
        </div>
    </div>
</body>
</html>