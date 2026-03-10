<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'orders.php';
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o 
          WHERE o.user_id = $user_id";

if ($status_filter) {
    $query .= " AND o.order_status = '$status_filter'";
}
if ($date_from) {
    $query .= " AND DATE(o.order_date) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(o.order_date) <= '$date_to'";
}

$query .= " ORDER BY o.order_date DESC";
$orders = $conn->query($query);

// Get order statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(total_amount) as total_spent
                FROM orders 
                WHERE user_id = $user_id";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>ประวัติการสั่งซื้อ</h1>
        <p>ติดตามสถานะออเดอร์ทั้งหมดของคุณ</p>
    </div>
</div>

<div class="container">
    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: var(--primary-blue);"><?php echo $stats['total_orders'] ?? 0; ?></div>
            <div style="color: var(--medium-gray);">ออเดอร์ทั้งหมด</div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;"><?php echo $stats['pending_count'] ?? 0; ?></div>
            <div style="color: var(--medium-gray);">รอดำเนินการ</div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: #3b82f6;"><?php echo $stats['shipped_count'] ?? 0; ?></div>
            <div style="color: var(--medium-gray);">จัดส่งแล้ว</div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: #10b981;"><?php echo $stats['delivered_count'] ?? 0; ?></div>
            <div style="color: var(--medium-gray);">ได้รับแล้ว</div>
        </div>
        <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); text-align: center;">
            <div style="font-size: 2rem; font-weight: 700; color: var(--primary-red);">฿<?php echo number_format($stats['total_spent'] ?? 0, 0); ?></div>
            <div style="color: var(--medium-gray);">ยอดใช้จ่ายรวม</div>
        </div>
    </div>

    <!-- Filter Section -->
    <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
            <div class="form-group">
                <label>สถานะ</label>
                <select name="status" class="form-control">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>จัดส่งแล้ว</option>
                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>ได้รับสินค้า</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>วันที่เริ่มต้น</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="form-group">
                <label>วันที่สิ้นสุด</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            
            <div class="form-group" style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">กรอง</button>
                <a href="orders.php" class="btn btn-secondary">ล้าง</a>
            </div>
        </form>
    </div>

    <!-- Orders List -->
    <?php if ($orders->num_rows == 0): ?>
        <div style="background: white; padding: 3rem; text-align: center; border-radius: 12px; box-shadow: var(--shadow-sm);">
            <i class="fas fa-box-open" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem;">ไม่มีประวัติการสั่งซื้อ</h3>
            <p style="color: var(--medium-gray); margin-bottom: 2rem;">เริ่มช้อปปิ้งเพื่อสร้างออเดอร์แรกของคุณ</p>
            <a href="category.php" class="btn btn-primary">เริ่มช้อปปิ้ง</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php while ($order = $orders->fetch_assoc()): ?>
                <?php
                // Get order items for this order
                $items_query = $conn->query("SELECT oi.*, p.product_name, p.image 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = {$order['id']} 
                                            LIMIT 3");
                $items = [];
                while ($item = $items_query->fetch_assoc()) {
                    $items[] = $item;
                }
                $total_items = $order['item_count'];
                
                // Status badge
                $status_class = '';
                $status_text = '';
                switch($order['order_status']) {
                    case 'pending':
                        $status_class = 'badge-warning';
                        $status_text = 'รอดำเนินการ';
                        break;
                    case 'processing':
                        $status_class = 'badge-info';
                        $status_text = 'กำลังดำเนินการ';
                        break;
                    case 'shipped':
                        $status_class = 'badge-primary';
                        $status_text = 'จัดส่งแล้ว';
                        break;
                    case 'delivered':
                        $status_class = 'badge-success';
                        $status_text = 'ได้รับสินค้า';
                        break;
                    case 'cancelled':
                        $status_class = 'badge-danger';
                        $status_text = 'ยกเลิก';
                        break;
                }
                ?>
                
                <!-- Order Card -->
                <div style="background: white; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden;">
                    <!-- Order Header -->
                    <div style="background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark)); color: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                            <div>
                                <small style="opacity: 0.8;">ออเดอร์ #</small>
                                <strong style="font-size: 1.25rem;"><?php echo $order['id']; ?></strong>
                            </div>
                            <div>
                                <small style="opacity: 0.8;">วันที่สั่งซื้อ</small>
                                <div><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="badge <?php echo $status_class; ?>" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                                <?php echo $status_text; ?>
                            </span>
                            <span style="font-size: 1.25rem; font-weight: 700;">฿<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <!-- Order Body -->
                    <div style="padding: 1.5rem;">
                        <!-- Product List -->
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <?php foreach ($items as $item): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--light-gray); padding: 0.5rem; border-radius: 8px;">
                                    <?php if ($item['image'] && file_exists("assets/images/" . $item['image'])): ?>
                                        <img src="assets/images/<?php echo $item['image']; ?>" 
                                             alt="<?php echo $item['product_name']; ?>" 
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo $item['product_name']; ?></div>
                                        <small><?php echo $item['quantity']; ?> x ฿<?php echo number_format($item['price'], 2); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($total_items > 3): ?>
                                <div style="display: flex; align-items: center; padding: 0.5rem 1rem; background: var(--light-gray); border-radius: 8px;">
                                    +<?php echo $total_items - 3; ?> รายการ
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Order Details -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light-gray);">
                            <div>
                                <small style="color: var(--medium-gray);">การชำระเงิน</small>
                                <div><?php echo $order['payment_method']; ?></div>
                            </div>
                            <div>
                                <small style="color: var(--medium-gray);">เบอร์โทร</small>
                                <div><?php echo $order['phone']; ?></div>
                            </div>
                            <div>
                                <small style="color: var(--medium-gray);">ที่อยู่จัดส่ง</small>
                                <div><?php echo $order['shipping_address']; ?></div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light-gray);">
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> ดูรายละเอียด
                            </a>
                            <?php if ($order['order_status'] == 'pending'): ?>
                                <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn btn-danger">
                                    <i class="fas fa-times"></i> ยกเลิกออเดอร์
                                </button>
                            <?php endif; ?>
                            <?php if ($order['order_status'] == 'delivered'): ?>
                                <a href="review.php?order=<?php echo $order['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-star"></i> รีวิวสินค้า
                                </a>
                            <?php endif; ?>
                            <button onclick="trackOrder(<?php echo $order['id']; ?>)" class="btn btn-secondary">
                                <i class="fas fa-truck"></i> ติดตามพัสดุ
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination (if needed) -->
        <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
            <button class="btn btn-secondary" disabled><i class="fas fa-chevron-left"></i> ก่อนหน้า</button>
            <span style="padding: 0.5rem 1rem;">หน้า 1</span>
            <button class="btn btn-secondary" disabled>ถัดไป <i class="fas fa-chevron-right"></i></button>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Order Modal -->
<div class="modal" id="cancelModal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>ยืนยันการยกเลิกออเดอร์</h3>
            <button class="modal-close" onclick="closeCancelModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>คุณแน่ใจหรือไม่ที่จะยกเลิกออเดอร์นี้?</p>
            <p style="color: var(--danger); font-size: 0.9rem;">* การยกเลิกออเดอร์ไม่สามารถย้อนกลับได้</p>
        </div>
        <div class="modal-footer">
            <form method="POST" action="cancel-order.php" id="cancelForm">
                <input type="hidden" name="order_id" id="cancel_order_id">
                <button type="submit" class="btn btn-danger">ยืนยันการยกเลิก</button>
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">ปิด</button>
            </form>
        </div>
    </div>
</div>

<!-- Track Order Modal -->
<div class="modal" id="trackModal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>ติดตามพัสดุ</h3>
            <button class="modal-close" onclick="closeTrackModal()">&times;</button>
        </div>
        <div class="modal-body" id="trackContent">
            <div style="text-align: center; padding: 2rem;">
                <div class="spinner"></div>
                <p style="margin-top: 1rem;">กำลังโหลดข้อมูลการติดตาม...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTrackModal()">ปิด</button>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--secondary);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-primary { background: #dbeafe; color: #1e40af; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }

.spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
// Modal functions
function showCancelModal(orderId) {
    document.getElementById('cancel_order_id').value = orderId;
    document.getElementById('cancelModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

function showTrackModal(orderId) {
    document.getElementById('trackModal').style.display = 'flex';
    
    // Simulate loading tracking info
    setTimeout(function() {
        document.getElementById('trackContent').innerHTML = `
            <div style="text-align: center;">
                <i class="fas fa-box" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h4>หมายเลขพัสดุ: TH${orderId}${Math.floor(Math.random()*1000000)}</h4>
                <div style="margin-top: 2rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span>📦 ถึงศูนย์คัดแยก</span>
                        <span style="color: var(--success);">✅</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span>🚚 อยู่ระหว่างการจัดส่ง</span>
                        <span style="color: var(--primary);">⏳</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span>📍 ถึงปลายทาง</span>
                        <span style="color: var(--medium-gray);">⏳</span>
                    </div>
                </div>
                <p style="margin-top: 1rem; color: var(--medium-gray);">อัปเดตล่าสุด: ${new Date().toLocaleString('th-TH')}</p>
            </div>
        `;
    }, 1500);
}

function closeTrackModal() {
    document.getElementById('trackModal').style.display = 'none';
    document.getElementById('trackContent').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <div class="spinner"></div>
            <p style="margin-top: 1rem;">กำลังโหลดข้อมูลการติดตาม...</p>
        </div>
    `;
}

function cancelOrder(orderId) {
    showCancelModal(orderId);
}

function trackOrder(orderId) {
    showTrackModal(orderId);
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include 'includes/new-footer.php'; ?>