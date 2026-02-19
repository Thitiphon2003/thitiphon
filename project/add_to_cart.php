<?php
session_start();
require_once 'db_connect.php';

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
            query("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?", 
                    [$new_quantity, $user_id, $product_id]);
            $message = 'อัปเดตจำนวนสินค้าในตะกร้าเรียบร้อย';
        } else {
            // เพิ่มใหม่
            query("INSERT INTO cart_items (user_id, product_id, quantity, selected) VALUES (?, ?, ?, 1)", 
                    [$user_id, $product_id, $quantity]);
            $message = 'เพิ่มสินค้าลงตะกร้าเรียบร้อย';
        }
        
        // นับจำนวนสินค้าในตะกร้า
        $count = fetchOne("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?", [$user_id])['count'];
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => $count
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>