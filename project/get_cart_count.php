<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$count = 0;
if (isset($_SESSION['user_id'])) {
    // ในระบบจริงจะ query จากตาราง cart_items
    // $count = fetchOne("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?", [$_SESSION['user_id']])['count'] ?? 0;
    
    // ตัวอย่าง
    $count = 3;
}

echo json_encode(['count' => $count]);
?>