<?php
// Database configuration for server 103.114.201.254
$user = "root";              // Database username
$pwd = "r669109";           // Database password
$db = "ecommerce_db";             // Database name

// Create connection
$conn = new mysqli($host, $user, $pwd, $db);

// Check connection
if ($conn->connect_error) {
    die("<div style='background: #f8d7da; color: #721c24; padding: 1rem; margin: 1rem; border-radius: 5px; border-left: 4px solid #dc3545;'>
            <strong>❌ การเชื่อมต่อฐานข้อมูลล้มเหลว!</strong><br>
            Error: " . $conn->connect_error . "<br>
            Host: " . $host . "<br>
            User: " . $user . "<br>
            Database: " . $db . "
        </div>");
}

// Set charset to UTF-8
if (!$conn->set_charset("utf8")) {
    $conn->query("SET NAMES utf8");
}

// Success message (สามารถลบได้หลังจากทดสอบ)
echo "<div style='background: #d4edda; color: #155724; padding: 1rem; margin: 1rem; border-radius: 5px; border-left: 4px solid #28a745;'>
        <strong>✅ เชื่อมต่อฐานข้อมูลสำเร็จ!</strong><br>
        Host: " . $host . "<br>
        Database: " . $db . "<br>
        Time: " . date('Y-m-d H:i:s') . "
      </div>";

// Function to test connection
function testConnection($conn) {
    $test = $conn->query("SELECT 1");
    if ($test) {
        return true;
    }
    return false;
}

// Function to get database info
function getDatabaseInfo($conn) {
    $info = [];
    
    // Get MySQL version
    $version = $conn->query("SELECT VERSION() as version")->fetch_assoc();
    $info['version'] = $version['version'];
    
    // Get database size
    $size = $conn->query("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()")->fetch_assoc();
    $info['size_mb'] = $size['size_mb'] ?? 0;
    
    // Get table count
    $tables = $conn->query("SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()")->fetch_assoc();
    $info['table_count'] = $tables['count'];
    
    return $info;
}

// Optional: Display database info
$db_info = getDatabaseInfo($conn);
echo "<div style='background: #e2f3ff; color: #004085; padding: 1rem; margin: 1rem; border-radius: 5px; border-left: 4px solid #17a2b8;'>
        <strong>📊 ข้อมูลฐานข้อมูล</strong><br>
        MySQL Version: " . $db_info['version'] . "<br>
        ขนาดฐานข้อมูล: " . $db_info['size_mb'] . " MB<br>
        จำนวนตาราง: " . $db_info['table_count'] . " ตาราง
      </div>";
?>