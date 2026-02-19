<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $addresses = fetchAll("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC", [$user_id]);
    
    echo json_encode([
        'success' => true,
        'data' => $addresses
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>