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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_address') {
    try {
        $address_id = (int)($_POST['address_id'] ?? 0);
        
        if ($address_id <= 0) {
            throw new Exception('ไม่พบที่อยู่');
        }
        
        // ตรวจสอบว่าเป็นที่อยู่ของผู้ใช้นี้หรือไม่
        $address = fetchOne("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?", [$address_id, $user_id]);
        
        if (!$address) {
            throw new Exception('ไม่พบที่อยู่');
        }
        
        // ตรวจสอบว่าเป็นที่อยู่ค่าเริ่มต้นหรือไม่
        if ($address['is_default']) {
            throw new Exception('ไม่สามารถลบที่อยู่ค่าเริ่มต้นได้');
        }
        
        query("DELETE FROM user_addresses WHERE id = ? AND user_id = ?", [$address_id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'ลบที่อยู่เรียบร้อย'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>