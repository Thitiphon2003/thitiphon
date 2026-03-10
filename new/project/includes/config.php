<?php
// เริ่มต้น session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เปิด error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'r660109');
define('DB_NAME', 'ecommerce_db');

// แสดงข้อมูลการเชื่อมต่อ (สำหรับ debug)
echo "<!-- Debug: Connecting to " . DB_HOST . " as " . DB_USER . " -->\n";

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // ถ้าเชื่อมต่อไม่ได้ ลองใช้ localhost แทน
    echo "<!-- Failed to connect to " . DB_HOST . ": " . $conn->connect_error . " -->\n";
    
    // ลองเชื่อมต่อกับ localhost
    $conn = new mysqli('localhost', 'root', '', DB_NAME);
    
    if ($conn->connect_error) {
        // ถ้ายังไม่ได้ ให้แสดง error ชัดเจน
        die("<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px; border-left: 4px solid #dc3545;'>
                <h3>❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h3>
                <p><strong>Host:</strong> " . DB_HOST . "</p>
                <p><strong>User:</strong> " . DB_USER . "</p>
                <p><strong>Database:</strong> " . DB_NAME . "</p>
                <p><strong>Error:</strong> " . $conn->connect_error . "</p>
                <p><strong>แนะนำ:</strong> ตรวจสอบรหัสผ่าน หรือสร้างฐานข้อมูล '4140db'</p>
             </div>");
    } else {
        echo "<!-- Connected to localhost successfully -->\n";
    }
} else {
    echo "<!-- Connected to " . DB_HOST . " successfully -->\n";
}

// Set charset
if (!$conn->set_charset("utf8")) {
    $conn->query("SET NAMES utf8");
}

// Site configuration
define('SITE_NAME', 'ShopHub');
define('SITE_URL', 'http://103.114.201.254/thitiphon/new/project/');

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to redirect
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'));
}
?>