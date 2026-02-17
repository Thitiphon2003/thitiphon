<?php
session_start();

// ตรวจสอบการเข้าสู่ระบบ
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit();
}

// ข้อมูลคำสั่งซื้อตัวอย่าง
$orders = [
    [
        'id' => 'ORD-2025-001',
        'date' => '2025-01-15 14:30:00',
        'status' => 'delivered',
        'status_text' => 'จัดส่งแล้ว',
        'total' => 1250,
        'payment_method' => 'โอนผ่านธนาคาร',
        'items' => [
            ['name' => 'เสื้อยืดคอปก', 'price' => 299, 'quantity' => 2, 'image' => 'https://via.placeholder.com/80x80'],
            ['name' => 'กระเป๋าสะพาย', 'price' => 599, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80']
        ],
        'tracking_number' => 'TH123456789',
        'courier' => 'Kerry Express',
        'estimated_delivery' => '2025-01-18'
    ],
    [
        'id' => 'ORD-2025-002',
        'date' => '2025-01-10 09:15:00',
        'status' => 'shipping',
        'status_text' => 'กำลังจัดส่ง',
        'total' => 1880,
        'payment_method' => 'บัตรเครดิต',
        'items' => [
            ['name' => 'หูฟังไร้สาย', 'price' => 1290, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80'],
            ['name' => 'พาวเวอร์แบงค์', 'price' => 590, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80']
        ],
        'tracking_number' => 'TH987654321',
        'courier' => 'Flash Express',
        'estimated_delivery' => '2025-01-13'
    ],
    [
        'id' => 'ORD-2025-003',
        'date' => '2025-01-05 16:45:00',
        'status' => 'processing',
        'status_text' => 'กำลังดำเนินการ',
        'total' => 2350,
        'payment_method' => 'โอนผ่านธนาคาร',
        'items' => [
            ['name' => 'นาฬิกาข้อมือ', 'price' => 890, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80'],
            ['name' => 'รองเท้าผ้าใบ', 'price' => 890, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80'],
            ['name' => 'แก้วน้ำเก็บความเย็น', 'price' => 199, 'quantity' => 3, 'image' => 'https://via.placeholder.com/80x80']
        ],
        'tracking_number' => '',
        'courier' => '',
        'estimated_delivery' => '2025-01-08'
    ],
    [
        'id' => 'ORD-2024-089',
        'date' => '2024-12-28 11:20:00',
        'status' => 'cancelled',
        'status_text' => 'ยกเลิก',
        'total' => 890,
        'payment_method' => 'เก็บเงินปลายทาง',
        'items' => [
            ['name' => 'ชุดเครื่องสำอาง', 'price' => 790, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80']
        ],
        'tracking_number' => '',
        'courier' => '',
        'estimated_delivery' => ''
    ],
    [
        'id' => 'ORD-2024-078',
        'date' => '2024-12-20 10:00:00',
        'status' => 'delivered',
        'status_text' => 'จัดส่งแล้ว',
        'total' => 3450,
        'payment_method' => 'บัตรเครดิต',
        'items' => [
            ['name' => 'เสื้อกันหนาว', 'price' => 890, 'quantity' => 2, 'image' => 'https://via.placeholder.com/80x80'],
            ['name' => 'กางเกงยีนส์', 'price' => 1290, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80'],
            ['name' => 'หมวก', 'price' => 380, 'quantity' => 1, 'image' => 'https://via.placeholder.com/80x80']
        ],
        'tracking_number' => 'TH555666777',
        'courier' => 'J&T Express',
        'estimated_delivery' => '2024-12-23'
    ]
];

// สรุปยอด
$summary = [
    'total_orders' => count($orders),
    'total_spent' => array_sum(array_column($orders, 'total')),
    'pending_orders' => count(array_filter($orders, fn($o) => $o['status'] == 'processing')),
    'shipping_orders' => count(array_filter($orders, fn($o) => $o['status'] == 'shipping'))
];

// จัดการฟิลเตอร์
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

if($status_filter != 'all') {
    $orders = array_filter($orders, fn($o) => $o['status'] == $status_filter);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำสั่งซื้อของฉัน - SHOP.COM</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Orders Page Styles */
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .orders-header h1 {
            font-size: 2rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .orders-header h1 i {
            color: #667eea;
        }
        
        .orders-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.3s;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .summary-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
        }
        
        .summary-info h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .summary-info .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
        }
        
        .summary-info .unit {
            font-size: 0.9rem;
            color: #999;
            margin-left: 0.2rem;
        }
        
        /* Filters */
        .orders-filters {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            background: #f8f9fa;
            color: #666;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s;
            border: 1px solid transparent;
        }
        
        .filter-tab:hover {
            background: #e9ecef;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .filter-tab.active .badge {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .filter-tab .badge {
            background: #e1e5e9;
            color: #666;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }
        
        .search-box {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Kanit', sans-serif;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-box button {
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Kanit', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        
        /* Order Cards */
        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .order-id {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .order-id i {
            opacity: 0.8;
        }
        
        .order-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background: rgba(255,255,255,0.2);
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
            border-bottom: 1px dashed #e1e5e9;
        }
        
        .order-info-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .order-info-item i {
            width: 20px;
            color: #667eea;
        }
        
        .order-info-item .label {
            font-size: 0.8rem;
            color: #999;
            display: block;
        }
        
        .order-info-item .value {
            font-size: 0.95rem;
            color: #333;
            font-weight: 500;
        }
        
        /* Products List */
        .order-products {
            margin-bottom: 1.5rem;
        }
        
        .order-product {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f3ff;
        }
        
        .order-product:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            background: #f8f9fa;
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
            color: #333;
        }
        
        .product-meta {
            font-size: 0.8rem;
            color: #999;
        }
        
        .product-price {
            text-align: right;
        }
        
        .product-price .price {
            font-size: 1rem;
            font-weight: 600;
            color: #667eea;
        }
        
        .product-price .quantity {
            font-size: 0.8rem;
            color: #999;
        }
        
        /* Order Footer */
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 2px solid #f0f3ff;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .order-total {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }
        
        .order-total .label {
            color: #666;
        }
        
        .order-total .value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .order-actions {
            display: flex;
            gap: 0.8rem;
        }
        
        .order-actions button,
        .order-actions a {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-family: 'Kanit', sans-serif;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-track {
            background: #28a745;
            color: white;
        }
        
        .btn-track:hover:not(:disabled) {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-track:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-review {
            background: #ffc107;
            color: #333;
        }
        
        .btn-review:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        .btn-details {
            background: #667eea;
            color: white;
        }
        
        .btn-details:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn-buy-again {
            background: #17a2b8;
            color: white;
        }
        
        .btn-buy-again:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        /* Tracking Info */
        .tracking-info {
            background: #f0f3ff;
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
            color: #667eea;
        }
        
        .tracking-courier {
            color: #666;
        }
        
        .tracking-estimate {
            color: #28a745;
        }
        
        .tracking-link {
            color: #667eea;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .tracking-link:hover {
            text-decoration: underline;
        }
        
        /* Empty State */
        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .empty-orders i {
            font-size: 5rem;
            color: #667eea;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-orders h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .empty-orders p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .empty-orders .btn-shop {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .empty-orders .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .orders-summary {
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
            
            .orders-summary {
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
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php">SHOP.COM</a>
            </div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.php">หน้าแรก</a></li>
                    <li><a href="index.php#categories">หมวดหมู่</a></li>
                    <li><a href="index.php#products">สินค้าทั้งหมด</a></li>
                    <li><a href="#contact">ติดต่อเรา</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="#" class="search-icon" onclick="toggleSearch()"><i class="fas fa-search"></i></a>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <div class="user-dropdown">
                        <a href="#" class="user-icon"><i class="fas fa-user"></i> Test User</a>
                        <div class="dropdown-content">
                            <a href="profile.php"><i class="fas fa-user-circle"></i> โปรไฟล์ของฉัน</a>
                            <a href="orders.php"><i class="fas fa-shopping-bag"></i> คำสั่งซื้อของฉัน</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="search-bar" id="searchBar">
            <input type="text" id="searchInput" placeholder="ค้นหาสินค้า...">
            <button onclick="searchProducts()"><i class="fas fa-search"></i> ค้นหา</button>
        </div>
    </nav>

    <!-- Orders Container -->
    <div class="orders-container">
        <!-- Header -->
        <div class="orders-header">
            <h1>
                <i class="fas fa-shopping-bag"></i>
                คำสั่งซื้อของฉัน
            </h1>
            <a href="index.php" class="btn-outline">
                <i class="fas fa-shopping-cart"></i> สั่งซื้อเพิ่มเติม
            </a>
        </div>
        
        <!-- Summary Cards -->
        <div class="orders-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="summary-info">
                    <h3>คำสั่งซื้อทั้งหมด</h3>
                    <div class="value"><?php echo $summary['total_orders']; ?></div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-info">
                    <h3>ยอดสั่งซื้อรวม</h3>
                    <div class="value">฿<?php echo number_format($summary['total_spent']); ?></div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="summary-info">
                    <h3>รอดำเนินการ</h3>
                    <div class="value"><?php echo $summary['pending_orders']; ?></div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="summary-info">
                    <h3>กำลังจัดส่ง</h3>
                    <div class="value"><?php echo $summary['shipping_orders']; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="orders-filters">
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    ทั้งหมด <span class="badge"><?php echo count($orders); ?></span>
                </a>
                <a href="?status=processing" class="filter-tab <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> รอดำเนินการ
                </a>
                <a href="?status=shipping" class="filter-tab <?php echo $status_filter == 'shipping' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> กำลังจัดส่ง
                </a>
                <a href="?status=delivered" class="filter-tab <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> จัดส่งแล้ว
                </a>
                <a href="?status=cancelled" class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> ยกเลิก
                </a>
            </div>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="ค้นหาด้วยเลขที่คำสั่งซื้อ หรือชื่อสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
                <button onclick="searchOrders()"><i class="fas fa-search"></i> ค้นหา</button>
            </div>
        </div>
        
        <!-- Orders List -->
        <?php if(empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>ยังไม่มีคำสั่งซื้อ</h3>
                <p>คุณยังไม่มีคำสั่งซื้อในขณะนี้ เริ่มช้อปปิ้งได้เลย!</p>
                <a href="index.php" class="btn-shop">
                    <i class="fas fa-shopping-cart"></i> เริ่มช้อปปิ้ง
                </a>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">
                            <i class="fas fa-receipt"></i>
                            เลขที่คำสั่งซื้อ: <?php echo $order['id']; ?>
                        </div>
                        <div class="order-status">
                            <?php echo $order['status_text']; ?>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <!-- Order Info -->
                        <div class="order-info-grid">
                            <div class="order-info-item">
                                <i class="fas fa-calendar"></i>
                                <div>
                                    <span class="label">วันที่สั่งซื้อ</span>
                                    <span class="value"><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-info-item">
                                <i class="fas fa-credit-card"></i>
                                <div>
                                    <span class="label">ชำระเงิน</span>
                                    <span class="value"><?php echo $order['payment_method']; ?></span>
                                </div>
                            </div>
                            
                            <?php if($order['tracking_number']): ?>
                                <div class="order-info-item">
                                    <i class="fas fa-truck"></i>
                                    <div>
                                        <span class="label">เลขพัสดุ</span>
                                        <span class="value"><?php echo $order['tracking_number']; ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Products -->
                        <div class="order-products">
                            <?php foreach($order['items'] as $item): ?>
                                <div class="order-product">
                                    <div class="product-image">
                                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                    </div>
                                    <div class="product-details">
                                        <div class="product-info">
                                            <h4><?php echo $item['name']; ?></h4>
                                            <div class="product-meta">
                                                ฿<?php echo number_format($item['price']); ?> ต่อชิ้น
                                            </div>
                                        </div>
                                        <div class="product-price">
                                            <div class="price">฿<?php echo number_format($item['price'] * $item['quantity']); ?></div>
                                            <div class="quantity">x<?php echo $item['quantity']; ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Tracking Info -->
                        <?php if($order['tracking_number']): ?>
                            <div class="tracking-info">
                                <div class="tracking-details">
                                    <span class="tracking-number">
                                        <i class="fas fa-box"></i> <?php echo $order['tracking_number']; ?>
                                    </span>
                                    <span class="tracking-courier">
                                        <i class="fas fa-truck"></i> <?php echo $order['courier']; ?>
                                    </span>
                                    <?php if($order['estimated_delivery']): ?>
                                        <span class="tracking-estimate">
                                            <i class="fas fa-clock"></i> คาดว่าถึง <?php echo date('d/m/Y', strtotime($order['estimated_delivery'])); ?>
                                        </span>
                                    <?php endif; ?>
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
                                <?php if($order['status'] == 'delivered'): ?>
                                    <button class="btn-review" onclick="reviewOrder('<?php echo $order['id']; ?>')">
                                        <i class="fas fa-star"></i> รีวิวสินค้า
                                    </button>
                                    <button class="btn-buy-again" onclick="buyAgain('<?php echo $order['id']; ?>')">
                                        <i class="fas fa-shopping-cart"></i> สั่งซื้ออีกครั้ง
                                    </button>
                                <?php elseif($order['status'] == 'shipping'): ?>
                                    <button class="btn-track" onclick="trackOrder('<?php echo $order['tracking_number']; ?>')">
                                        <i class="fas fa-map-marker-alt"></i> ติดตามพัสดุ
                                    </button>
                                <?php elseif($order['status'] == 'processing'): ?>
                                    <button class="btn-track" disabled>
                                        <i class="fas fa-clock"></i> รอยืนยันคำสั่งซื้อ
                                    </button>
                                    <button class="btn-details" onclick="cancelOrder('<?php echo $order['id']; ?>')" style="background: #dc3545;">
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

    <!-- Order Detail Modal -->
    <div id="orderDetailModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close" onclick="closeOrderModal()">&times;</span>
            <h2>รายละเอียดคำสั่งซื้อ</h2>
            <div id="orderDetailContent">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReviewModal()">&times;</span>
            <h2>รีวิวสินค้า</h2>
            <form id="reviewForm">
                <div class="form-group">
                    <label>คะแนน</label>
                    <div class="rating-stars">
                        <i class="far fa-star" onclick="setRating(1)"></i>
                        <i class="far fa-star" onclick="setRating(2)"></i>
                        <i class="far fa-star" onclick="setRating(3)"></i>
                        <i class="far fa-star" onclick="setRating(4)"></i>
                        <i class="far fa-star" onclick="setRating(5)"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>ความคิดเห็น</label>
                    <textarea rows="4" placeholder="เขียนรีวิวสินค้า..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>รูปภาพ (ถ้ามี)</label>
                    <input type="file" multiple accept="image/*">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-edit">
                        <i class="fas fa-paper-plane"></i> ส่งรีวิว
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Search orders
        function searchOrders() {
            const searchTerm = document.getElementById('searchInput').value;
            window.location.href = 'orders.php?search=' + encodeURIComponent(searchTerm);
        }
        
        // View order details
        function viewOrderDetails(orderId) {
            const modal = document.getElementById('orderDetailModal');
            const content = document.getElementById('orderDetailContent');
            
            // Sample order details (in real app, fetch from server)
            content.innerHTML = `
                <div style="padding: 1rem 0;">
                    <h3>รายละเอียดคำสั่งซื้อ #${orderId}</h3>
                    
                    <div style="margin-top: 1rem;">
                        <h4>ข้อมูลการจัดส่ง</h4>
                        <p><strong>ผู้รับ:</strong> สมชาย ใจดี</p>
                        <p><strong>เบอร์โทร:</strong> 081-234-5678</p>
                        <p><strong>ที่อยู่:</strong> 123/4 ถ.สุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110</p>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <h4>รายการสินค้า</h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #f0f3ff;">
                                    <th style="padding: 0.5rem; text-align: left;">สินค้า</th>
                                    <th style="padding: 0.5rem; text-align: center;">จำนวน</th>
                                    <th style="padding: 0.5rem; text-align: right;">ราคา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid #f0f3ff;">
                                    <td style="padding: 0.5rem;">เสื้อยืดคอปก</td>
                                    <td style="padding: 0.5rem; text-align: center;">2</td>
                                    <td style="padding: 0.5rem; text-align: right;">฿598</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #f0f3ff;">
                                    <td style="padding: 0.5rem;">กระเป๋าสะพาย</td>
                                    <td style="padding: 0.5rem; text-align: center;">1</td>
                                    <td style="padding: 0.5rem; text-align: right;">฿599</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="padding: 0.5rem; text-align: right;"><strong>ค่าจัดส่ง</strong></td>
                                    <td style="padding: 0.5rem; text-align: right;">฿50</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding: 0.5rem; text-align: right;"><strong>รวมทั้งสิ้น</strong></td>
                                    <td style="padding: 0.5rem; text-align: right;"><strong>฿1,247</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
        }
        
        // Track order
        function trackOrder(trackingNumber) {
            alert('กำลังติดตามพัสดุหมายเลข: ' + trackingNumber);
            window.open('https://www.thailandpost.co.th/track?trackNumber=' + trackingNumber, '_blank');
        }
        
        // Buy again
        function buyAgain(orderId) {
            alert('เพิ่มสินค้าจากคำสั่งซื้อ #' + orderId + ' ลงตะกร้า');
            window.location.href = 'cart.php';
        }
        
        // Cancel order
        function cancelOrder(orderId) {
            if(confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกคำสั่งซื้อนี้?')) {
                alert('ดำเนินการยกเลิกคำสั่งซื้อ #' + orderId);
            }
        }
        
        // Review order
        function reviewOrder(orderId) {
            document.getElementById('reviewModal').style.display = 'block';
        }
        
        // Set rating
        let currentRating = 0;
        
        function setRating(rating) {
            currentRating = rating;
            const stars = document.querySelectorAll('.rating-stars i');
            
            stars.forEach((star, index) => {
                if(index < rating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                }
            });
        }
        
        // Close modals
        function closeOrderModal() {
            document.getElementById('orderDetailModal').style.display = 'none';
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
        
        // Handle review form submit
        document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('ขอบคุณสำหรับรีวิว! (คะแนน: ' + currentRating + ' ดาว)');
            closeReviewModal();
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const orderModal = document.getElementById('orderDetailModal');
            const reviewModal = document.getElementById('reviewModal');
            
            if(event.target == orderModal) {
                orderModal.style.display = 'none';
            }
            if(event.target == reviewModal) {
                reviewModal.style.display = 'none';
            }
        };
    </script>
</body>
</html>