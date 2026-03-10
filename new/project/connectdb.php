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

?>