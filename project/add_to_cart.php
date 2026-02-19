<?php
session_start();
require_once 'db_connect.php';

// ปิด error reporting ชั่วคราวเพื่อไม่ให้มี output แทรก
error_reporting(0);
ini_set('display_errors', 0);

// ล้าง output buffer
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้า']);
        exit();
    }
    
    try {
        // ตรวจสอบสินค้า
        $product = fetchOne("SELECT * FROM products WHERE id = ? AND status = 'active'", [$product_id]);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้าในระบบ']);
            exit();
        }
        
        // ตรวจสอบสต็อก
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'สินค้ามีจำนวนไม่เพียงพอ (เหลือ ' . $product['stock'] . ' ชิ้น)']);
            exit();
        }
        
        // ตรวจสอบว่ามีสินค้านี้ในตะกร้าแล้วหรือยัง
        $existing = fetchOne("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
        
        if ($existing) {
            // อัปเดตจำนวน
            $new_quantity = $existing['quantity'] + $quantity;
            if ($new_quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'จำนวนสินค้าในตะกร้ารวมกันเกินสต็อก (เหลือ ' . $product['stock'] . ' ชิ้น)']);
                exit();
            }
            query("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?", 
                  [$new_quantity, $user_id, $product_id]);
            $message = 'อัปเดตจำนวนสินค้าในตะกร้าเรียบร้อย';
        } else {
            // เพิ่มใหม่
            query("INSERT INTO cart_items (user_id, product_id, quantity, selected, created_at, updated_at) 
                    VALUES (?, ?, ?, 1, NOW(), NOW())", 
                    [$user_id, $product_id, $quantity]);
            $message = 'เพิ่มสินค้าลงตะกร้าเรียบร้อย';
        }
        
        // นับจำนวนสินค้าในตะกร้า
        $count = fetchOne("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?", [$user_id])['count'];
        
        // ส่ง Response สำเร็จ
        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => (int)$count
        ]);
        exit();
        
    } catch (Exception $e) {
        // ส่ง Response ข้อผิดพลาด
        echo json_encode([
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}
?>