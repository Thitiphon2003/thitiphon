<?php
		$host = "localhost";
		$user = "root";
		$pwd = "r660109";
		$db = "ecommerce_db";
		$conn = mysqli_connect($host, $user, $pwd, $db) or die ("เชื่อมต่อฐานข้อมูลไม่ได้");
		mysqli_query($conn, "SET NAMES utf8");

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