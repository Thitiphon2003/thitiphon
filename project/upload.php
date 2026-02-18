<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $type = $_POST['type'] ?? 'product';
    $folder = 'products';
    
    switch ($type) {
        case 'product':
            $folder = 'products';
            break;
        case 'category':
            $folder = 'categories';
            break;
        case 'avatar':
            $folder = 'profiles';
            break;
        case 'slip':
            $folder = 'slips';
            break;
        default:
            $folder = 'products';
    }
    
    $result = uploadImage($_FILES['file'], $folder);
    
    echo json_encode($result);
    exit();
}

echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์ที่อัปโหลด']);
?>