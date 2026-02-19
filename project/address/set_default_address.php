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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'set_default') {
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
        
        // เริ่ม transaction
        $pdo->beginTransaction();
        
        // ยกเลิกค่าเริ่มต้นทั้งหมด
        query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$user_id]);
        
        // ตั้งค่าที่อยู่ใหม่เป็นค่าเริ่มต้น
        query("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?", [$address_id, $user_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'ตั้งค่าที่อยู่เริ่มต้นเรียบร้อย'
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>