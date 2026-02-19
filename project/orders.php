<?php
session_start();
require_once 'db_connect.php';

// บังคับให้เข้าสู่ระบบก่อนเข้าใช้งาน
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'orders.php';
    header('Location: login.php?redirect=orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'คำสั่งซื้อของฉัน';
include 'includes/header.php';

// แสดงข้อความสำเร็จจากการสั่งซื้อ
if (isset($_GET['order_success']) && isset($_GET['order'])) {
    $success_message = 'สั่งซื้อสำเร็จ! หมายเลขคำสั่งซื้อ: ' . htmlspecialchars($_GET['order']);
}

// ============================================
// ดึงข้อมูลสรุปคำสั่งซื้อ
// ============================================
$summary = fetchOne("SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total), 0) as total_spent,
    SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN order_status = 'shipping' THEN 1 ELSE 0 END) as shipping_orders,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
FROM orders WHERE user_id = ?", [$user_id]);

// ============================================
// รับค่าพารามิเตอร์จาก URL
// ============================================
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ============================================
// สร้าง SQL Query ตามเงื่อนไข
// ============================================
$where = "WHERE user_id = ?";
$params = [$user_id];

if ($status_filter != 'all') {
    $where .= " AND order_status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where .= " AND (order_number LIKE ? OR id IN (
        SELECT order_id FROM order_items WHERE product_name LIKE ?
    ))";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// ============================================
// ดึงข้อมูลคำสั่งซื้อ
// ============================================
$orders = fetchAll("SELECT * FROM orders $where ORDER BY created_at DESC", $params);

// ============================================
// ดึงรายการสินค้าสำหรับแต่ละคำสั่งซื้อ
// ============================================
$order_items = [];
foreach ($orders as $order) {
    $items = fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
    $order_items[$order['id']] = $items;
}

// ============================================
// ดึงข้อมูลที่อยู่สำหรับแต่ละคำสั่งซื้อ
// ============================================
$order_addresses = [];
foreach ($orders as $order) {
    if ($order['address_id']) {
        $address = fetchOne("SELECT * FROM user_addresses WHERE id = ?", [$order['address_id']]);
        if ($address) {
            $order_addresses[$order['id']] = $address;
        }
    }
}
?>

<style>
.orders-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.orders-header {
    background: linear-gradient(135deg, #2563eb10, #10b98110);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    background: #dbeafe;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
    font-size: 1.5rem;
}

.stat-info h3 {
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 0.3rem;
}

.stat-info .value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.6rem 1.5rem;
    border-radius: 30px;
    background: white;
    color: #475569;
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.3s;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-tab:hover {
    border-color: #2563eb;
    color: #2563eb;
}

.filter-tab.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.filter-tab .badge {
    background: #e2e8f0;
    color: #475569;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
}

.filter-tab.active .badge {
    background: rgba(255,255,255,0.2);
    color: white;
}

/* Search Box */
.search-box {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.search-box input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-box button {
    padding: 0.75rem 2rem;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

.search-box button:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

/* Order Card */
.order-card {
    background: white;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: all 0.3s;
    animation: slideIn 0.5s ease;
    animation-fill-mode: both;
}

.order-card:nth-child(1) { animation-delay: 0.1s; }
.order-card:nth-child(2) { animation-delay: 0.2s; }
.order-card:nth-child(3) { animation-delay: 0.3s; }
.order-card:nth-child(4) { animation-delay: 0.4s; }
.order-card:nth-child(5) { animation-delay: 0.5s; }

.order-card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-color: #2563eb;
}

.order-header {
    background: #f8fafc;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 1rem;
}

.order-number {
    font-weight: 700;
    color: #2563eb;
    font-size: 1.1rem;
}

.order-status {
    padding: 0.4rem 1rem;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-processing {
    background: #dbeafe;
    color: #1e40af;
}

.status-shipping {
    background: #cffafe;
    color: #0891b2;
}

.status-delivered {
    background: #d1fae5;
    color: #065f46;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.order-body {
    padding: 1.5rem;
}

.order-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px dashed #e2e8f0;
}

.order-info-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.order-info-item i {
    width: 20px;
    color: #2563eb;
}

.order-info-item .label {
    font-size: 0.8rem;
    color: #64748b;
    display: block;
}

.order-info-item .value {
    font-size: 0.95rem;
    color: #0f172a;
    font-weight: 500;
}

/* Product Items */
.order-products {
    margin-bottom: 1.5rem;
}

.order-product {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.3s;
}

.order-product:hover {
    background: #f8fafc;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    border-radius: 8px;
}

.order-product:last-child {
    border-bottom: none;
}

.product-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-details {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.product-info h4 {
    font-size: 1rem;
    margin-bottom: 0.3rem;
    color: #0f172a;
}

.product-meta {
    font-size: 0.8rem;
    color: #64748b;
}

.product-price {
    text-align: right;
}

.product-price .price {
    font-size: 1rem;
    font-weight: 600;
    color: #2563eb;
}

.product-price .quantity {
    font-size: 0.8rem;
    color: #64748b;
}

/* Order Footer */
.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 2px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 1rem;
}

.order-total {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.order-total .label {
    color: #64748b;
}

.order-total .value {
    font-size: 1.3rem;
    font-weight: 700;
    color: #2563eb;
}

.order-actions {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.order-actions button,
.order-actions a {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-track {
    background: #10b981;
    color: white;
}

.btn-track:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-2px);
}

.btn-track:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-review {
    background: #f59e0b;
    color: white;
}

.btn-review:hover {
    background: #d97706;
    transform: translateY(-2px);
}

.btn-details {
    background: #2563eb;
    color: white;
}

.btn-details:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #64748b;
    color: white;
}

.btn-secondary:hover {
    background: #475569;
    transform: translateY(-2px);
}

/* Empty State */
.empty-orders {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
}

.empty-orders i {
    font-size: 5rem;
    color: #2563eb;
    opacity: 0.3;
    margin-bottom: 1rem;
}

.empty-orders h3 {
    color: #0f172a;
    margin-bottom: 0.5rem;
}

.empty-orders p {
    color: #64748b;
    margin-bottom: 2rem;
}

.empty-orders .btn-shop {
    display: inline-block;
    padding: 1rem 2rem;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
}

.empty-orders .btn-shop:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

/* Tracking Info */
.tracking-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.tracking-details {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.tracking-number {
    font-weight: 600;
    color: #2563eb;
}

.tracking-courier {
    color: #64748b;
}

.tracking-estimate {
    color: #10b981;
}

.tracking-link {
    color: #2563eb;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.tracking-link:hover {
    text-decoration: underline;
}

/* Address Info */
.address-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.address-info i {
    color: #2563eb;
    width: 20px;
}

/* Modal Styles */
.order-detail-item {
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 0;
}

.order-detail-item:last-child {
    border-bottom: none;
}

.order-detail-label {
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.order-detail-value {
    font-weight: 500;
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Toast Container */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    min-width: 300px;
    margin-bottom: 10px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    animation: slideInRight 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.toast-success { background: #10b981; }
.toast-danger { background: #ef4444; }
.toast-warning { background: #f59e0b; }
.toast-info { background: #3b82f6; }

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .order-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .orders-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .order-info-grid {
        grid-template-columns: 1fr;
    }
    
    .order-product {
        flex-direction: column;
    }
    
    .product-image {
        width: 100%;
        height: auto;
        aspect-ratio: 1;
    }
    
    .product-details {
        flex-direction: column;
        text-align: center;
    }
    
    .product-price {
        text-align: center;
    }
    
    .order-footer {
        flex-direction: column;
    }
    
    .order-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .order-actions button,
    .order-actions a {
        width: 100%;
        justify-content: center;
    }
    
    .tracking-info {
        flex-direction: column;
        text-align: center;
    }
    
    .tracking-details {
        flex-direction: column;
    }
}
</style>

<div class="container orders-container">
    <div class="orders-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-2">
                    <i class="fas fa-shopping-bag text-primary me-2"></i>
                    คำสั่งซื้อของฉัน
                </h1>
                <p class="text-muted mb-0">ติดตามสถานะคำสั่งซื้อและประวัติการสั่งซื้อ</p>
            </div>
            <a href="category.php" class="btn btn-outline-primary">
                <i class="fas fa-plus me-2"></i>สั่งซื้อเพิ่มเติม
            </a>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3>คำสั่งซื้อทั้งหมด</h3>
                <div class="value"><?php echo number_format($summary['total_orders']); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3>ยอดสั่งซื้อรวม</h3>
                <div class="value">฿<?php echo number_format($summary['total_spent']); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>รอดำเนินการ</h3>
                <div class="value"><?php echo number_format($summary['pending_orders'] + $summary['processing_orders']); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-info">
                <h3>กำลังจัดส่ง</h3>
                <div class="value"><?php echo number_format($summary['shipping_orders']); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            ทั้งหมด
            <span class="badge"><?php echo $summary['total_orders']; ?></span>
        </a>
        <a href="?status=pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i>
            รอดำเนินการ
            <span class="badge"><?php echo $summary['pending_orders']; ?></span>
        </a>
        <a href="?status=processing<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            กำลังดำเนินการ
            <span class="badge"><?php echo $summary['processing_orders']; ?></span>
        </a>
        <a href="?status=shipping<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'shipping' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i>
            กำลังจัดส่ง
            <span class="badge"><?php echo $summary['shipping_orders']; ?></span>
        </a>
        <a href="?status=delivered<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i>
            จัดส่งแล้ว
            <span class="badge"><?php echo $summary['delivered_orders']; ?></span>
        </a>
        <a href="?status=cancelled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
            <i class="fas fa-times-circle"></i>
            ยกเลิก
            <span class="badge"><?php echo $summary['cancelled_orders']; ?></span>
        </a>
    </div>
    
    <!-- Search Box -->
    <form method="GET" class="search-box">
        <?php if ($status_filter != 'all'): ?>
            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
        <?php endif; ?>
        <input type="text" name="search" placeholder="ค้นหาด้วยเลขที่คำสั่งซื้อ หรือชื่อสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">
            <i class="fas fa-search me-2"></i>ค้นหา
        </button>
    </form>
    
    <!-- Orders List -->
    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <i class="fas fa-shopping-bag"></i>
            <h3>ยังไม่มีคำสั่งซื้อ</h3>
            <p>คุณยังไม่มีคำสั่งซื้อในขณะนี้ เริ่มช้อปปิ้งได้เลย!</p>
            <a href="category.php" class="btn-shop">
                <i class="fas fa-store me-2"></i>เริ่มช้อปปิ้ง
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php 
            $items = $order_items[$order['id']] ?? [];
            $address = $order_addresses[$order['id']] ?? null;
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-number">
                        <i class="fas fa-receipt me-2"></i>
                        <?php echo $order['order_number']; ?>
                    </div>
                    <div class="order-status status-<?php echo $order['order_status']; ?>">
                        <?php 
                            $status_map = [
                                'pending' => 'รอดำเนินการ',
                                'processing' => 'กำลังดำเนินการ',
                                'shipping' => 'กำลังจัดส่ง',
                                'delivered' => 'จัดส่งแล้ว',
                                'cancelled' => 'ยกเลิก'
                            ];
                            echo $status_map[$order['order_status']] ?? $order['order_status'];
                        ?>
                    </div>
                </div>
                
                <div class="order-body">
                    <!-- Order Info -->
                    <div class="order-info-grid">
                        <div class="order-info-item">
                            <i class="fas fa-calendar"></i>
                            <div>
                                <span class="label">วันที่สั่งซื้อ</span>
                                <span class="value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="order-info-item">
                            <i class="fas fa-credit-card"></i>
                            <div>
                                <span class="label">ชำระเงิน</span>
                                <span class="value"><?php 
                                    $payment_map = [
                                        'bank_transfer' => 'โอนผ่านธนาคาร',
                                        'credit_card' => 'บัตรเครดิต',
                                        'promptpay' => 'พร้อมเพย์',
                                        'cod' => 'เก็บเงินปลายทาง'
                                    ];
                                    echo $payment_map[$order['payment_method']] ?? $order['payment_method'];
                                ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['tracking_number'])): ?>
                            <div class="order-info-item">
                                <i class="fas fa-truck"></i>
                                <div>
                                    <span class="label">เลขพัสดุ</span>
                                    <span class="value"><?php echo $order['tracking_number']; ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Address Info (ถ้ามี) -->
                    <?php if ($address): ?>
                    <div class="address-info">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <i class="fas fa-user me-2"></i>
                                    <span><?php echo htmlspecialchars($address['recipient']); ?></span>
                                </div>
                                <div class="mb-1">
                                    <i class="fas fa-phone me-2"></i>
                                    <span><?php echo htmlspecialchars($address['phone']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div>
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <span><?php echo htmlspecialchars($address['address']); ?><br>
                                    <?php echo htmlspecialchars($address['district'] . ' ' . $address['city'] . ' ' . $address['province'] . ' ' . $address['postcode']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Products -->
                    <?php if (!empty($items)): ?>
                        <div class="order-products">
                            <?php foreach ($items as $item): ?>
                                <div class="order-product" onclick="viewProductDetail(<?php echo $item['product_id']; ?>)">
                                    <div class="product-image">
                                        <?php 
                                        $image_path = "uploads/products/" . $item['product_id'] . ".jpg";
                                        if (file_exists($image_path)): ?>
                                            <img src="<?php echo $image_path . '?t=' . time(); ?>" alt="<?php echo $item['product_name']; ?>">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/80x80?text=Product" alt="Product">
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-details">
                                        <div class="product-info">
                                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                            <div class="product-meta">
                                                ฿<?php echo number_format($item['price']); ?> ต่อชิ้น
                                            </div>
                                        </div>
                                        <div class="product-price">
                                            <div class="price">฿<?php echo number_format($item['subtotal']); ?></div>
                                            <div class="quantity">x<?php echo $item['quantity']; ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tracking Info (ถ้ามี) -->
                    <?php if (!empty($order['tracking_number'])): ?>
                        <div class="tracking-info">
                            <div class="tracking-details">
                                <span class="tracking-number">
                                    <i class="fas fa-box"></i> <?php echo $order['tracking_number']; ?>
                                </span>
                                <span class="tracking-courier">
                                    <i class="fas fa-truck"></i> <?php echo $order['courier'] ?? 'Kerry Express'; ?>
                                </span>
                            </div>
                            <a href="#" class="tracking-link" onclick="trackOrder('<?php echo $order['tracking_number']; ?>')">
                                <i class="fas fa-external-link-alt"></i> ติดตามพัสดุ
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Order Footer -->
                    <div class="order-footer">
                        <div class="order-total">
                            <span class="label">ยอดรวมทั้งสิ้น:</span>
                            <span class="value">฿<?php echo number_format($order['total']); ?></span>
                        </div>
                        
                        <div class="order-actions">
                            <?php if ($order['order_status'] == 'delivered'): ?>
                                <button class="btn-review" onclick="reviewOrder('<?php echo $order['id']; ?>')">
                                    <i class="fas fa-star"></i> รีวิวสินค้า
                                </button>
                                <button class="btn-secondary" onclick="buyAgain('<?php echo $order['id']; ?>')">
                                    <i class="fas fa-redo-alt"></i> สั่งซื้ออีกครั้ง
                                </button>
                            <?php elseif ($order['order_status'] == 'shipping' && !empty($order['tracking_number'])): ?>
                                <button class="btn-track" onclick="trackOrder('<?php echo $order['tracking_number']; ?>')">
                                    <i class="fas fa-map-marker-alt"></i> ติดตามพัสดุ
                                </button>
                            <?php elseif ($order['order_status'] == 'pending'): ?>
                                <button class="btn-track" disabled>
                                    <i class="fas fa-clock"></i> รอยืนยันคำสั่งซื้อ
                                </button>
                                <button class="btn-secondary" onclick="cancelOrder('<?php echo $order['id']; ?>')">
                                    <i class="fas fa-times"></i> ขอยกเลิก
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn-details" onclick="viewOrderDetails('<?php echo $order['id']; ?>')">
                                <i class="fas fa-eye"></i> รายละเอียด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal รายละเอียดคำสั่งซื้อ -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดคำสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal รีวิวสินค้า -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รีวิวสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reviewForm">
                    <div class="mb-3">
                        <label class="form-label">คะแนน</label>
                        <div class="rating-stars">
                            <i class="far fa-star" onclick="setRating(1)"></i>
                            <i class="far fa-star" onclick="setRating(2)"></i>
                            <i class="far fa-star" onclick="setRating(3)"></i>
                            <i class="far fa-star" onclick="setRating(4)"></i>
                            <i class="far fa-star" onclick="setRating(5)"></i>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ความคิดเห็น</label>
                        <textarea class="form-control" rows="4" placeholder="เขียนรีวิวสินค้า..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">รูปภาพ (ถ้ามี)</label>
                        <input type="file" class="form-control" multiple accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="submitReview()">ส่งรีวิว</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
let currentOrderId = null;
let currentRating = 0;

// แสดง Toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}

// ดูรายละเอียดสินค้า
function viewProductDetail(productId) {
    window.location.href = 'product_detail.php?id=' + productId;
}

// ดูรายละเอียดคำสั่งซื้อ
function viewOrderDetails(orderId) {
    currentOrderId = orderId;
    
    // ดึงข้อมูลคำสั่งซื้อจาก server
    fetch('get_order_details.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
                document.getElementById('orderDetailContent').innerHTML = data.html;
                modal.show();
            } else {
                showToast(data.message || 'ไม่สามารถโหลดข้อมูลได้', 'danger');
            }
        })
        .catch(error => {
            showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
        });
}

// ติดตามพัสดุ
function trackOrder(trackingNumber) {
    if (!trackingNumber) {
        showToast('ไม่มีหมายเลขติดตามพัสดุ', 'warning');
        return;
    }
    
    window.open('https://track.thailandpost.co.th/?trackNumber=' + trackingNumber, '_blank');
}

// สั่งซื้ออีกครั้ง
function buyAgain(orderId) {
    if (!confirm('ต้องการสั่งซื้อสินค้าจากคำสั่งซื้อนี้อีกครั้ง?')) {
        return;
    }
    
    fetch('reorder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'order_id=' + orderId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 1500);
        } else {
            showToast(data.message || 'เกิดข้อผิดพลาด', 'danger');
        }
    })
    .catch(error => {
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
    });
}

// ขอยกเลิกคำสั่งซื้อ
function cancelOrder(orderId) {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกคำสั่งซื้อนี้?')) {
        return;
    }
    
    fetch('cancel_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'order_id=' + orderId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('ยกเลิกคำสั่งซื้อเรียบร้อย', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'เกิดข้อผิดพลาด', 'danger');
        }
    })
    .catch(error => {
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
    });
}

// รีวิวสินค้า
function reviewOrder(orderId) {
    currentOrderId = orderId;
    currentRating = 0;
    
    // Reset stars
    document.querySelectorAll('.rating-stars i').forEach(star => {
        star.className = 'far fa-star';
    });
    
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
}

// ตั้งค่าระดับคะแนน
function setRating(rating) {
    currentRating = rating;
    const stars = document.querySelectorAll('.rating-stars i');
    
    stars.forEach((star, index) => {
        if (index < rating) {
            star.className = 'fas fa-star';
        } else {
            star.className = 'far fa-star';
        }
    });
}

// ส่งรีวิว
function submitReview() {
    if (currentRating === 0) {
        showToast('กรุณาเลือกคะแนน', 'warning');
        return;
    }
    
    showToast('ขอบคุณสำหรับรีวิว!', 'success');
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
    modal.hide();
}
</script>

<?php include 'includes/footer.php'; ?>