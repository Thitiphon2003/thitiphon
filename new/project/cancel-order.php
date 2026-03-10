<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['order_id'])) {
    redirect('orders.php');
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];

// Check if order belongs to user and is pending
$check_query = "SELECT id, order_status FROM orders WHERE id = $order_id AND user_id = $user_id";
$check_result = $conn->query($check_query);

if ($check_result->num_rows == 0) {
    $_SESSION['error'] = "ไม่พบออเดอร์ที่ต้องการยกเลิก";
    redirect('orders.php');
}

$order = $check_result->fetch_assoc();
if ($order['order_status'] != 'pending') {
    $_SESSION['error'] = "ไม่สามารถยกเลิกออเดอร์ที่ดำเนินการไปแล้วได้";
    redirect('orders.php');
}

// Update order status to cancelled
$conn->begin_transaction();

try {
    // Update order
    $update_query = "UPDATE orders SET order_status = 'cancelled' WHERE id = $order_id";
    $conn->query($update_query);
    
    // Restore stock
    $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id";
    $items = $conn->query($items_query);
    
    while ($item = $items->fetch_assoc()) {
        $conn->query("UPDATE products SET stock = stock + {$item['quantity']} WHERE id = {$item['product_id']}");
    }
    
    // Create notification
    $notify_query = "INSERT INTO notifications (user_id, title, message, type) 
                     VALUES ($user_id, 'ยกเลิกออเดอร์', 'ออเดอร์ #$order_id ถูกยกเลิกเรียบร้อย', 'order')";
    $conn->query($notify_query);
    
    $conn->commit();
    $_SESSION['success'] = "ยกเลิกออเดอร์สำเร็จ";
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

redirect('orders.php');
?>