<?php
session_start();
require_once 'db_connect.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ตัวอย่างข้อมูลคำสั่งซื้อ
$orders = [
    [
        'order_number' => 'ORD-20250115-001',
        'date' => '2025-01-15 14:30:00',
        'status' => 'delivered',
        'total' => 1247,
        'items' => 2
    ],
    [
        'order_number' => 'ORD-20250110-002',
        'date' => '2025-01-10 09:15:00',
        'status' => 'shipping',
        'total' => 1925,
        'items' => 2
    ],
    [
        'order_number' => 'ORD-20250105-003',
        'date' => '2025-01-05 16:45:00',
        'status' => 'pending',
        'total' => 2390,
        'items' => 3
    ]
];

$page_title = 'คำสั่งซื้อของฉัน';
include 'includes/header.php';
?>

<div class="container" style="margin: 2rem auto;">
    <h1 style="font-size: 2rem; color: #0f172a; margin-bottom: 2rem;">คำสั่งซื้อของฉัน</h1>

    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; overflow: hidden;">
        <table>
            <thead>
                <tr>
                    <th>เลขที่คำสั่งซื้อ</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>จำนวนสินค้า</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td><?php echo $order['order_number']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                    <td><?php echo $order['items']; ?> รายการ</td>
                    <td>฿<?php echo number_format($order['total']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php 
                                $status_map = [
                                    'pending' => 'รอดำเนินการ',
                                    'processing' => 'กำลังดำเนินการ',
                                    'shipping' => 'กำลังจัดส่ง',
                                    'delivered' => 'จัดส่งแล้ว',
                                    'cancelled' => 'ยกเลิก'
                                ];
                                echo $status_map[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-secondary" style="padding: 0.3rem 1rem;" onclick="viewOrder('<?php echo $order['order_number']; ?>')">
                            ดูรายละเอียด
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function viewOrder(orderNumber) {
    alert('ดูรายละเอียดคำสั่งซื้อ: ' + orderNumber);
}
</script>

<?php include 'includes/footer.php'; ?>