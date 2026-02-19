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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_address') {
    try {
        // รับข้อมูลจากฟอร์ม
        $address_name = trim($_POST['address_name'] ?? '');
        $recipient = trim($_POST['recipient'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $postcode = trim($_POST['postcode'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // ตรวจสอบข้อมูลที่จำเป็น
        $errors = [];
        if (empty($address_name)) $errors[] = 'กรุณากรอกชื่อที่อยู่';
        if (empty($recipient)) $errors[] = 'กรุณากรอกชื่อผู้รับ';
        if (empty($phone)) $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
        if (empty($address)) $errors[] = 'กรุณากรอกที่อยู่';
        if (empty($district)) $errors[] = 'กรุณากรอกแขวง/ตำบล';
        if (empty($city)) $errors[] = 'กรุณากรอกเขต/อำเภอ';
        if (empty($province)) $errors[] = 'กรุณากรอกจังหวัด';
        if (empty($postcode)) $errors[] = 'กรุณากรอกรหัสไปรษณีย์';
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
            exit();
        }
        
        // เริ่ม transaction
        $pdo->beginTransaction();
        
        // ถ้าตั้งเป็นค่าเริ่มต้น ให้ยกเลิกค่าเริ่มต้นของที่อยู่อื่น
        if ($is_default) {
            query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$user_id]);
        }
        
        // บันทึกที่อยู่ใหม่
        $sql = "INSERT INTO user_addresses (
            user_id, address_name, recipient, phone, address, 
            district, city, province, postcode, is_default, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        query($sql, [
            $user_id,
            $address_name,
            $recipient,
            $phone,
            $address,
            $district,
            $city,
            $province,
            $postcode,
            $is_default
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'เพิ่มที่อยู่เรียบร้อย'
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