<?php
require_once '../config/database.php';
session_start();

class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function login($email, $password, $remember = false) {
        try {
            // ตรวจสอบผู้ใช้
            $sql = "SELECT * FROM users WHERE email = :email AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if(!$user) {
                return ['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้นี้'];
            }
            
            // ตรวจสอบรหัสผ่าน
            if(!password_verify($password, $user['password'])) {
                // บันทึกประวัติการ login ล้มเหลว
                $this->logLoginAttempt($email, false);
                return ['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง'];
            }
            
            // อัปเดต last login
            $sql = "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $user['id']]);
            
            // ตั้งค่า session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_level'] = $user['level'];
            $_SESSION['login_time'] = time();
            
            // ตั้งค่า remember me
            if($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $sql = "INSERT INTO user_tokens (user_id, token, expiry) VALUES (:user_id, :token, :expiry)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':token' => $token,
                    ':expiry' => $expiry
                ]);
                
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
            }
            
            // บันทึกประวัติการ login
            $this->logLoginAttempt($email, true, $user['id']);
            
            return ['success' => true, 'user' => $user];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    public function register($data) {
        try {
            // ตรวจสอบอีเมลซ้ำ
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $data['email']]);
            
            if($stmt->fetch()) {
                return ['success' => false, 'message' => 'อีเมลนี้已被ใช้แล้ว'];
            }
            
            // เข้ารหัสรหัสผ่าน
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['level'] = 'Silver';
            $data['points'] = 100; // คะแนนต้อนรับ
            
            // บันทึกผู้ใช้ใหม่
            $sql = "INSERT INTO users (firstname, lastname, email, phone, password, birthdate, gender, level, points, created_at) 
                    VALUES (:firstname, :lastname, :email, :phone, :password, :birthdate, :gender, :level, :points, :created_at)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':firstname' => $data['firstname'],
                ':lastname' => $data['lastname'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':password' => $data['password'],
                ':birthdate' => $data['birthdate'] ?? null,
                ':gender' => $data['gender'] ?? null,
                ':level' => $data['level'],
                ':points' => $data['points'],
                ':created_at' => $data['created_at']
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // ส่งอีเมลยืนยัน
            $this->sendVerificationEmail($data['email'], $userId);
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    public function checkRememberMe() {
        if(isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            $sql = "SELECT u.* FROM users u 
                    JOIN user_tokens t ON u.id = t.user_id 
                    WHERE t.token = :token AND t.expiry > NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();
            
            if($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['firstname'] . ' ' . $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_level'] = $user['level'];
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        // ลบ remember token
        if(isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $sql = "DELETE FROM user_tokens WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // ลบ session
        session_unset();
        session_destroy();
    }
    
    private function logLoginAttempt($email, $success, $userId = null) {
        $sql = "INSERT INTO login_logs (email, user_id, success, ip_address, user_agent, login_time) 
                VALUES (:email, :user_id, :success, :ip, :agent, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $userId,
            ':success' => $success ? 1 : 0,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    private function sendVerificationEmail($email, $userId) {
        $token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO email_verifications (user_id, token, expiry) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':token' => $token]);
        
        $verificationLink = "http://localhost/shop/verify-email.php?token=$token";
        
        // ส่งอีเมล (ใช้ PHP mail หรือ SMTP)
        $subject = "ยืนยันอีเมลของคุณ - SHOP.COM";
        $message = "กรุณาคลิกลิงก์เพื่อยืนยันอีเมล: $verificationLink";
        $headers = "From: noreply@shop.com";
        
        mail($email, $subject, $message, $headers);
    }
}

// ตรวจสอบ session อัตโนมัติ
$auth = new Auth($db);

// ตรวจสอบ remember me
if(!isset($_SESSION['user_id'])) {
    $auth->checkRememberMe();
}

// ฟังก์ชัน helper
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    global $db;
    if(isset($_SESSION['user_id'])) {
        $sql = "SELECT * FROM users WHERE id = :id";
        return fetchOne($sql, [':id' => $_SESSION['user_id']]);
    }
    return null;
}
?>