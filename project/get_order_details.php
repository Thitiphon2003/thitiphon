<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ']);
    exit();
}

try {
    // ดึงข้อมูลคำสั่งซื้อ (ตรวจสอบว่าเป็นของ user นี้)
    $order = fetchOne("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $user_id]);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ']);
        exit();
    }
    
    // ดึงรายการสินค้า
    $items = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order_id]);
    
    // ดึงข้อมูลที่อยู่
    $address = null;
    if ($order['address_id']) {
        $address = fetchOne("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?", [$order['address_id'], $user_id]);
    }
    
    // สร้าง HTML สำหรับแสดงรายละเอียด
    $html = '<div class="order-detail">';
    
    // ข้อมูลคำสั่งซื้อ
    $html .= '<div class="order-detail-section mb-4">';
    $html .= '<h6 class="mb-3">ข้อมูลคำสั่งซื้อ</h6>';
    $html .= '<div class="row">';
    $html .= '<div class="col-md-6 mb-2">';
    $html .= '<div class="order-detail-label text-muted small">เลขที่คำสั่งซื้อ</div>';
    $html .= '<div class="order-detail-value fw-bold">' . $order['order_number'] . '</div>';
    $html .= '</div>';
    $html .= '<div class="col-md-6 mb-2">';
    $html .= '<div class="order-detail-label text-muted small">วันที่สั่งซื้อ</div>';
    $html .= '<div class="order-detail-value">' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</div>';
    $html .= '</div>';
    $html .= '<div class="col-md-6 mb-2">';
    $html .= '<div class="order-detail-label text-muted small">สถานะ</div>';
    $html .= '<div class="order-detail-value">';
    $status_map = [
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังดำเนินการ',
        'shipping' => 'กำลังจัดส่ง',
        'delivered' => 'จัดส่งแล้ว',
        'cancelled' => 'ยกเลิก'
    ];
    $status_class = $order['order_status'];
    $html .= '<span class="badge bg-' . ($status_class == 'delivered' ? 'success' : ($status_class == 'cancelled' ? 'danger' : ($status_class == 'shipping' ? 'info' : 'warning'))) . '">' . ($status_map[$order['order_status']] ?? $order['order_status']) . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="col-md-6 mb-2">';
    $html .= '<div class="order-detail-label text-muted small">วิธีการชำระเงิน</div>';
    $html .= '<div class="order-detail-value">';
    $payment_map = [
        'bank_transfer' => 'โอนผ่านธนาคาร',
        'credit_card' => 'บัตรเครดิต',
        'promptpay' => 'พร้อมเพย์',
        'cod' => 'เก็บเงินปลายทาง'
    ];
    $html .= $payment_map[$order['payment_method']] ?? $order['payment_method'];
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // ข้อมูลที่อยู่จัดส่ง
    if ($address) {
        $html .= '<div class="order-detail-section mb-4">';
        $html .= '<h6 class="mb-3">ที่อยู่จัดส่ง</h6>';
        $html .= '<div class="p-3 bg-light rounded">';
        $html .= '<div class="mb-2"><i class="fas fa-user text-primary me-2"></i><strong>' . htmlspecialchars($address['recipient']) . '</strong></div>';
        $html .= '<div class="mb-2"><i class="fas fa-phone text-primary me-2"></i>' . htmlspecialchars($address['phone']) . '</div>';
        $html .= '<div class="mb-2"><i class="fas fa-map-marker-alt text-primary me-2"></i>';
        $html .= htmlspecialchars($address['address']) . '<br>';
        $html .= htmlspecialchars($address['district'] . ' ' . $address['city'] . ' ' . $address['province'] . ' ' . $address['postcode']);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // รายการสินค้า
    $html .= '<div class="order-detail-section mb-4">';
    $html .= '<h6 class="mb-3">รายการสินค้า</h6>';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-bordered table-hover">';
    $html .= '<thead class="table-light">';
    $html .= '<tr><th>สินค้า</th><th class="text-center">ราคา/หน่วย</th><th class="text-center">จำนวน</th><th class="text-end">รวม</th></tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($items as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['product_name']) . '</td>';
        $html .= '<td class="text-center">฿' . number_format($item['price']) . '</td>';
        $html .= '<td class="text-center">' . $item['quantity'] . '</td>';
        $html .= '<td class="text-end">฿' . number_format($item['subtotal']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '<tfoot class="table-light">';
    $html .= '<tr><td colspan="3" class="text-end"><strong>ยอดรวมสินค้า</strong></td><td class="text-end"><strong>฿' . number_format($order['subtotal']) . '</strong></td></tr>';
    $html .= '<tr><td colspan="3" class="text-end">ค่าจัดส่ง</td><td class="text-end">฿' . number_format($order['shipping_fee']) . '</td></tr>';
    $html .= '<tr><td colspan="3" class="text-end"><strong>ยอดสุทธิ</strong></td><td class="text-end"><strong class="text-primary">฿' . number_format($order['total']) . '</strong></td></tr>';
    $html .= '</tfoot>';
    $html .= '</table>';
    $html .= '</div>';
    $html .= '</div>';
    
    // หมายเหตุ
    if (!empty($order['notes'])) {
        $html .= '<div class="order-detail-section">';
        $html .= '<h6 class="mb-3">หมายเหตุ</h6>';
        $html .= '<div class="p-3 bg-light rounded">';
        $html .= nl2br(htmlspecialchars($order['notes']));
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>