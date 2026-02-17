<?php
session_start();

// ตรวจสอบการเข้าสู่ระบบ (ถ้าต้องการให้เข้าสู่ระบบก่อนสั่งซื้อ)
$is_logged_in = isset($_SESSION['user_id']);

// ข้อมูลสินค้าในตะกร้า (ตัวอย่าง)
$cart_items = [
    [
        'id' => 1,
        'name' => 'เสื้อยืดคอปก ผู้ชาย',
        'price' => 299,
        'original_price' => 459,
        'quantity' => 2,
        'image' => 'https://via.placeholder.com/100x100',
        'category' => 'เสื้อผ้า',
        'stock' => 50,
        'max_per_order' => 5,
        'seller' => 'ร้านชายสี่บะหมี่เกี๊ยว',
        'shipping_fee' => 50,
        'selected' => true
    ],
    [
        'id' => 2,
        'name' => 'หูฟังไร้สาย Bluetooth 5.0',
        'price' => 1290,
        'original_price' => 1890,
        'quantity' => 1,
        'image' => 'https://via.placeholder.com/100x100',
        'category' => 'อิเล็กทรอนิกส์',
        'stock' => 20,
        'max_per_order' => 3,
        'seller' => 'ร้านไอทีออนไลน์',
        'shipping_fee' => 30,
        'selected' => true
    ],
    [
        'id' => 3,
        'name' => 'กระเป๋าสะพายข้างหนังแท้',
        'price' => 890,
        'original_price' => 1290,
        'quantity' => 1,
        'image' => 'https://via.placeholder.com/100x100',
        'category' => 'แฟชั่น',
        'stock' => 15,
        'max_per_order' => 2,
        'seller' => 'ร้านแฟชั่นช้อป',
        'shipping_fee' => 40,
        'selected' => false
    ],
    [
        'id' => 4,
        'name' => 'นาฬิกาข้อมือ智能',
        'price' => 590,
        'original_price' => 990,
        'quantity' => 3,
        'image' => 'https://via.placeholder.com/100x100',
        'category' => 'เครื่องประดับ',
        'stock' => 30,
        'max_per_order' => 5,
        'seller' => 'ร้านนาฬิกาดีดี',
        'shipping_fee' => 30,
        'selected' => true
    ]
];

// คำนวณราคารวม
$subtotal = 0;
$total_shipping = 0;
$selected_count = 0;

foreach($cart_items as $item) {
    if($item['selected']) {
        $subtotal += $item['price'] * $item['quantity'];
        $total_shipping += $item['shipping_fee'];
        $selected_count++;
    }
}

$discount = 0; // ส่วนลด (ตัวอย่าง)
$total = $subtotal + $total_shipping - $discount;

// สินค้าแนะนำ
$recommended_products = [
    [
        'id' => 101,
        'name' => 'แก้วน้ำเก็บความเย็น',
        'price' => 199,
        'original_price' => 299,
        'image' => 'https://via.placeholder.com/150x150',
        'sold' => 1234
    ],
    [
        'id' => 102,
        'name' => 'พาวเวอร์แบงค์ 20000mAh',
        'price' => 590,
        'original_price' => 890,
        'image' => 'https://via.placeholder.com/150x150',
        'sold' => 856
    ],
    [
        'id' => 103,
        'name' => 'หมอนข้าง memory foam',
        'price' => 350,
        'original_price' => 590,
        'image' => 'https://via.placeholder.com/150x150',
        'sold' => 2341
    ],
    [
        'id' => 104,
        'name' => 'เสื้อกันหนาวแฟชั่น',
        'price' => 490,
        'original_price' => 790,
        'image' => 'https://via.placeholder.com/150x150',
        'sold' => 567
    ]
];

// จัดการ AJAX requests
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => true, 'message' => ''];
    
    switch($_POST['action']) {
        case 'update_quantity':
            $item_id = $_POST['item_id'];
            $quantity = $_POST['quantity'];
            // อัปเดตจำนวนในตะกร้า
            $response['message'] = 'อัปเดตจำนวนเรียบร้อย';
            break;
            
        case 'remove_item':
            $item_id = $_POST['item_id'];
            // ลบสินค้าออกจากตะกร้า
            $response['message'] = 'ลบสินค้าเรียบร้อย';
            break;
            
        case 'select_item':
            $item_id = $_POST['item_id'];
            $selected = $_POST['selected'];
            // เลือก/ไม่เลือกสินค้า
            $response['message'] = 'อัปเดตเรียบร้อย';
            break;
            
        case 'apply_coupon':
            $coupon = $_POST['coupon'];
            // ตรวจสอบคูปอง
            $response['message'] = 'ใช้คูปองสำเร็จ';
            $response['discount'] = 100;
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - SHOP.COM</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Cart Page Styles */
        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }
        
        /* Cart Header */
        .cart-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-header h1 {
            font-size: 1.5rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-header h1 i {
            color: #667eea;
        }
        
        .cart-count {
            background: #667eea;
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .select-all input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        /* Seller Section */
        .seller-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .seller-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .seller-header input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .seller-name {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .seller-name i {
            color: #667eea;
        }
        
        .seller-chat {
            margin-left: auto;
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        /* Cart Items */
        .cart-items {
            margin-bottom: 1rem;
        }
        
        .cart-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f3ff;
            position: relative;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-select {
            display: flex;
            align-items: center;
        }
        
        .item-select input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            overflow: hidden;
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .item-name {
            font-size: 1rem;
            font-weight: 500;
            color: #333;
            text-decoration: none;
        }
        
        .item-name:hover {
            color: #667eea;
        }
        
        .item-category {
            font-size: 0.8rem;
            color: #999;
        }
        
        .item-shipping {
            font-size: 0.8rem;
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .item-price-section {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
            min-width: 200px;
        }
        
        .item-prices {
            text-align: right;
        }
        
        .current-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .original-price {
            font-size: 0.9rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 0.5rem;
        }
        
        .discount-badge {
            background: #ff4444;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }
        
        .item-quantity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #e1e5e9;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover:not(:disabled) {
            background: #667eea;
            color: white;
        }
        
        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .quantity-input {
            width: 50px;
            height: 32px;
            border: none;
            text-align: center;
            font-size: 0.95rem;
            font-family: 'Kanit', sans-serif;
            border-left: 1px solid #e1e5e9;
            border-right: 1px solid #e1e5e9;
        }
        
        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .stock-info {
            font-size: 0.7rem;
            color: #28a745;
            text-align: right;
        }
        
        .item-actions {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .item-actions button {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 0.8rem;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .item-actions button:hover {
            color: #667eea;
        }
        
        .item-actions .delete-btn:hover {
            color: #dc3545;
        }
        
        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .summary-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.95rem;
        }
        
        .summary-row.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #f0f3ff;
        }
        
        .summary-row.total .value {
            color: #667eea;
        }
        
        .summary-row.saving {
            color: #28a745;
        }
        
        .coupon-section {
            margin: 1.5rem 0;
        }
        
        .coupon-input {
            display: flex;
            gap: 0.5rem;
        }
        
        .coupon-input input {
            flex: 1;
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-family: 'Kanit', sans-serif;
        }
        
        .coupon-input input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .coupon-input button {
            padding: 0.8rem 1.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .coupon-input button:hover {
            background: #5a67d8;
        }
        
        .applied-coupon {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            padding: 0.8rem;
            margin-top: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .applied-coupon span {
            color: #2e7d32;
            font-size: 0.9rem;
        }
        
        .applied-coupon i {
            color: #dc3545;
            cursor: pointer;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Kanit', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            margin: 1.5rem 0 1rem;
        }
        
        .checkout-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.3);
        }
        
        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .payment-methods {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            color: #999;
            font-size: 1.5rem;
        }
        
        /* Recommended Products */
        .recommended-section {
            grid-column: 1 / -1;
            margin-top: 2rem;
        }
        
        .recommended-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .recommended-header h2 {
            font-size: 1.3rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .recommended-header h2 i {
            color: #ffd700;
        }
        
        .recommended-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }
        
        .recommended-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .recommended-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .recommended-image {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0.8rem;
        }
        
        .recommended-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .recommended-name {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            color: #333;
        }
        
        .recommended-price {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.3rem;
        }
        
        .recommended-old-price {
            font-size: 0.8rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 0.3rem;
        }
        
        .recommended-sold {
            font-size: 0.7rem;
            color: #999;
            margin-bottom: 0.5rem;
        }
        
        .recommended-add {
            width: 100%;
            padding: 0.5rem;
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 5px;
            color: #667eea;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.8rem;
        }
        
        .recommended-add:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        /* Empty Cart */
        .empty-cart {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 15px;
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: #667eea;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        .empty-cart h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .empty-cart .btn-shop {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .empty-cart .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        
        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .cart-container {
                grid-template-columns: 1fr;
            }
            
            .recommended-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .cart-item {
                flex-wrap: wrap;
            }
            
            .item-image {
                width: 80px;
                height: 80px;
            }
            
            .item-price-section {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                min-width: auto;
            }
            
            .item-prices {
                text-align: left;
            }
            
            .recommended-grid {
                grid-template-columns: 1fr;
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
                        <span class="cart-count">4</span>
                    </a>
                    <?php if($is_logged_in): ?>
                        <div class="user-dropdown">
                            <a href="#" class="user-icon"><i class="fas fa-user"></i> Test User</a>
                            <div class="dropdown-content">
                                <a href="profile.php"><i class="fas fa-user-circle"></i> โปรไฟล์ของฉัน</a>
                                <a href="orders.php"><i class="fas fa-shopping-bag"></i> คำสั่งซื้อของฉัน</a>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="login-btn">เข้าสู่ระบบ</a>
                        <a href="register.php" class="register-btn">สมัครสมาชิก</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="search-bar" id="searchBar">
            <input type="text" id="searchInput" placeholder="ค้นหาสินค้า...">
            <button onclick="searchProducts()"><i class="fas fa-search"></i> ค้นหา</button>
        </div>
    </nav>

    <!-- Cart Container -->
    <?php if(empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="cart-container">
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>ตะกร้าสินค้าของคุณว่างเปล่า</h2>
                <p>เริ่มช้อปปิ้งและเพิ่มสินค้าลงในตะกร้ากันเลย!</p>
                <a href="index.php" class="btn-shop">
                    <i class="fas fa-shopping-bag"></i> เริ่มช้อปปิ้ง
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Cart with Items -->
        <div class="cart-container">
            <!-- Left Column -->
            <div class="cart-left">
                <!-- Cart Header -->
                <div class="cart-header">
                    <h1>
                        <i class="fas fa-shopping-cart"></i>
                        ตะกร้าสินค้า
                    </h1>
                    <label class="select-all">
                        <input type="checkbox" id="selectAll" checked onchange="toggleSelectAll()">
                        <span>เลือกทั้งหมด</span>
                        <span class="cart-count"><?php echo $selected_count; ?>/<?php echo count($cart_items); ?> รายการ</span>
                    </label>
                </div>
                
                <!-- Group by Seller (ตัวอย่าง) -->
                <?php 
                $sellers = array_unique(array_column($cart_items, 'seller'));
                foreach($sellers as $seller): 
                ?>
                <div class="seller-section" data-seller="<?php echo $seller; ?>">
                    <div class="seller-header">
                        <input type="checkbox" class="seller-select" checked onchange="toggleSeller(this)">
                        <div class="seller-name">
                            <i class="fas fa-store"></i>
                            <?php echo $seller; ?>
                        </div>
                        <a href="#" class="seller-chat">
                            <i class="fas fa-comment"></i> แชทกับร้าน
                        </a>
                    </div>
                    
                    <div class="cart-items">
                        <?php foreach($cart_items as $index => $item): ?>
                            <?php if($item['seller'] == $seller): ?>
                            <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                                <div class="item-select">
                                    <input 
                                        type="checkbox" 
                                        class="item-checkbox" 
                                        data-seller="<?php echo $seller; ?>"
                                        <?php echo $item['selected'] ? 'checked' : ''; ?>
                                        onchange="toggleItem(this)"
                                    >
                                </div>
                                
                                <div class="item-image">
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                </div>
                                
                                <div class="item-details">
                                    <a href="#" class="item-name"><?php echo $item['name']; ?></a>
                                    <div class="item-category">หมวดหมู่: <?php echo $item['category']; ?></div>
                                    <div class="item-shipping">
                                        <i class="fas fa-truck"></i>
                                        ค่าจัดส่ง ฿<?php echo number_format($item['shipping_fee']); ?>
                                    </div>
                                    
                                    <div class="item-actions">
                                        <button class="wishlist-btn" onclick="addToWishlist(<?php echo $item['id']; ?>)">
                                            <i class="far fa-heart"></i> เก็บไว้ภายหลัง
                                        </button>
                                        <button class="delete-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i> ลบ
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="item-price-section">
                                    <div class="item-prices">
                                        <span class="current-price">฿<?php echo number_format($item['price']); ?></span>
                                        <?php if($item['original_price'] > $item['price']): ?>
                                            <span class="original-price">฿<?php echo number_format($item['original_price']); ?></span>
                                            <span class="discount-badge">
                                                -<?php echo round((($item['original_price'] - $item['price']) / $item['original_price']) * 100); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-quantity">
                                        <button 
                                            class="quantity-btn" 
                                            onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')"
                                            <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>
                                        >
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input 
                                            type="text" 
                                            class="quantity-input" 
                                            value="<?php echo $item['quantity']; ?>"
                                            onchange="updateQuantityInput(<?php echo $item['id']; ?>, this.value)"
                                        >
                                        <button 
                                            class="quantity-btn" 
                                            onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')"
                                            <?php echo $item['quantity'] >= $item['max_per_order'] ? 'disabled' : ''; ?>
                                        >
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="stock-info">
                                        คงเหลือ <?php echo $item['stock']; ?> ชิ้น
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Recommended Products -->
                <div class="recommended-section">
                    <div class="recommended-header">
                        <h2>
                            <i class="fas fa-fire"></i>
                            สินค้าแนะนำสำหรับคุณ
                        </h2>
                        <a href="#" class="view-more">ดูทั้งหมด <i class="fas fa-chevron-right"></i></a>
                    </div>
                    
                    <div class="recommended-grid">
                        <?php foreach($recommended_products as $product): ?>
                            <div class="recommended-card">
                                <div class="recommended-image">
                                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                </div>
                                <div class="recommended-name"><?php echo $product['name']; ?></div>
                                <div class="recommended-price">
                                    ฿<?php echo number_format($product['price']); ?>
                                    <?php if($product['original_price'] > $product['price']): ?>
                                        <span class="recommended-old-price">฿<?php echo number_format($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="recommended-sold">ขายแล้ว <?php echo number_format($product['sold']); ?> ชิ้น</div>
                                <button class="recommended-add" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i> หยิบใส่ตะกร้า
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Summary -->
            <div class="cart-right">
                <div class="cart-summary">
                    <div class="summary-header">สรุปคำสั่งซื้อ</div>
                    
                    <div class="summary-row">
                        <span>ราคาสินค้า (<?php echo $selected_count; ?> รายการ)</span>
                        <span class="value">฿<?php echo number_format($subtotal); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>ค่าจัดส่ง</span>
                        <span class="value">฿<?php echo number_format($total_shipping); ?></span>
                    </div>
                    
                    <?php if($discount > 0): ?>
                        <div class="summary-row saving">
                            <span>ส่วนลด</span>
                            <span class="value">-฿<?php echo number_format($discount); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="coupon-section">
                        <div class="coupon-input">
                            <input type="text" id="couponCode" placeholder="ใส่โค้ดส่วนลด">
                            <button onclick="applyCoupon()">ใช้</button>
                        </div>
                        
                        <div class="applied-coupon" id="appliedCoupon" style="display: none;">
                            <span><i class="fas fa-tag"></i> <span id="couponDisplay"></span></span>
                            <i class="fas fa-times" onclick="removeCoupon()"></i>
                        </div>
                    </div>
                    
                    <div class="summary-row total">
                        <span>ยอดสุทธิ</span>
                        <span class="value" id="totalAmount">฿<?php echo number_format($total); ?></span>
                    </div>
                    
                    <?php if(!$is_logged_in): ?>
                        <div style="background: #fff3cd; color: #856404; padding: 0.8rem; border-radius: 5px; margin: 1rem 0; font-size: 0.9rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            กรุณา <a href="login.php?redirect=cart.php" style="color: #856404; font-weight: 600;">เข้าสู่ระบบ</a> เพื่อดำเนินการสั่งซื้อ
                        </div>
                    <?php endif; ?>
                    
                    <button 
                        class="checkout-btn" 
                        onclick="checkout()"
                        <?php echo $selected_count == 0 || !$is_logged_in ? 'disabled' : ''; ?>
                    >
                        <i class="fas fa-credit-card"></i> สั่งซื้อสินค้า
                    </button>
                    
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa" title="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fab fa-cc-paypal" title="PayPal"></i>
                        <img src="https://via.placeholder.com/30x20?text=PromptPay" alt="PromptPay" style="height: 20px;">
                    </div>
                    
                    <div style="margin-top: 1rem; font-size: 0.8rem; color: #999; text-align: center;">
                        <i class="fas fa-shield-alt"></i> ซื้อสินค้าอย่างปลอดภัย มั่นใจได้ทุกคำสั่งซื้อ
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Remove Confirmation Modal -->
    <div id="removeModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" onclick="closeRemoveModal()">&times;</span>
            <div style="text-align: center; padding: 1rem;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ffc107; margin-bottom: 1rem;"></i>
                <h3>ยืนยันการลบสินค้า</h3>
                <p style="color: #666; margin: 1rem 0;">คุณแน่ใจหรือไม่ที่จะลบสินค้านี้ออกจากตะกร้า?</p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button class="btn-outline" onclick="closeRemoveModal()">ยกเลิก</button>
                    <button class="btn-edit" style="background: #dc3545;" onclick="confirmRemove()">ลบสินค้า</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentItemId = null;
        
        // Toggle all items
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const sellerCheckboxes = document.querySelectorAll('.seller-select');
            
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            sellerCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            
            updateSummary();
        }
        
        // Toggle seller items
        function toggleSeller(checkbox) {
            const seller = checkbox.closest('.seller-section').dataset.seller;
            const itemCheckboxes = document.querySelectorAll(`.item-checkbox[data-seller="${seller}"]`);
            
            itemCheckboxes.forEach(cb => cb.checked = checkbox.checked);
            
            updateSelectAll();
            updateSummary();
        }
        
        // Toggle single item
        function toggleItem(checkbox) {
            const seller = checkbox.dataset.seller;
            const sellerSection = checkbox.closest('.seller-section');
            const sellerCheckbox = sellerSection.querySelector('.seller-select');
            const sellerItems = sellerSection.querySelectorAll('.item-checkbox');
            
            // Check if all items in seller are checked
            const allChecked = Array.from(sellerItems).every(cb => cb.checked);
            sellerCheckbox.checked = allChecked;
            
            updateSelectAll();
            updateSummary();
        }
        
        // Update select all checkbox
        function updateSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            selectAll.checked = allChecked;
            selectAll.indeterminate = !allChecked && anyChecked;
        }
        
        // Update summary
        function updateSummary() {
            // This would normally fetch new totals from server
            // For demo, we'll just show loading
            document.getElementById('totalAmount').innerHTML = '<span class="spinner"></span>';
            
            setTimeout(() => {
                document.getElementById('totalAmount').innerHTML = '฿1,247';
            }, 500);
        }
        
        // Update quantity
        function updateQuantity(itemId, action) {
            const item = document.querySelector(`[data-item-id="${itemId}"]`);
            const input = item.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if(action === 'increase') {
                quantity++;
            } else if(action === 'decrease') {
                quantity--;
            }
            
            if(quantity < 1) quantity = 1;
            
            input.value = quantity;
            
            // Show loading
            item.style.opacity = '0.5';
            
            // Send to server
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&item_id=${itemId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                item.style.opacity = '1';
                if(data.success) {
                    updateSummary();
                    showNotification(data.message, 'success');
                }
            });
        }
        
        // Update quantity input
        function updateQuantityInput(itemId, value) {
            let quantity = parseInt(value);
            
            if(isNaN(quantity) || quantity < 1) {
                quantity = 1;
            }
            
            const item = document.querySelector(`[data-item-id="${itemId}"]`);
            const input = item.querySelector('.quantity-input');
            input.value = quantity;
            
            updateQuantity(itemId, 'set');
        }
        
        // Remove item
        function removeItem(itemId) {
            currentItemId = itemId;
            document.getElementById('removeModal').style.display = 'block';
        }
        
        function closeRemoveModal() {
            document.getElementById('removeModal').style.display = 'none';
            currentItemId = null;
        }
        
        function confirmRemove() {
            if(!currentItemId) return;
            
            const item = document.querySelector(`[data-item-id="${currentItemId}"]`);
            
            // Send to server
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_item&item_id=${currentItemId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    item.remove();
                    showNotification(data.message, 'success');
                    closeRemoveModal();
                    
                    // Check if cart is empty
                    if(document.querySelectorAll('.cart-item').length === 0) {
                        location.reload();
                    }
                }
            });
        }
        
        // Add to wishlist
        function addToWishlist(itemId) {
            showNotification('เพิ่มสินค้าในรายการที่ชอบแล้ว', 'success');
        }
        
        // Apply coupon
        function applyCoupon() {
            const coupon = document.getElementById('couponCode').value;
            
            if(!coupon) {
                showNotification('กรุณาใส่โค้ดส่วนลด', 'error');
                return;
            }
            
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=apply_coupon&coupon=${coupon}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('couponCode').value = '';
                    document.getElementById('couponDisplay').textContent = coupon;
                    document.getElementById('appliedCoupon').style.display = 'flex';
                    showNotification(data.message, 'success');
                    updateSummary();
                }
            });
        }
        
        function removeCoupon() {
            document.getElementById('appliedCoupon').style.display = 'none';
            showNotification('ยกเลิกการใช้คูปองแล้ว', 'success');
            updateSummary();
        }
        
        // Add to cart from recommended
        function addToCart(productId) {
            showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อย', 'success');
            
            // Update cart count
            const cartCount = document.querySelector('.cart-count');
            cartCount.textContent = parseInt(cartCount.textContent) + 1;
        }
        
        // Checkout
        function checkout() {
            <?php if(!$is_logged_in): ?>
                window.location.href = 'login.php?redirect=cart.php';
            <?php else: ?>
                if(document.querySelectorAll('.item-checkbox:checked').length === 0) {
                    showNotification('กรุณาเลือกสินค้าที่ต้องการสั่งซื้อ', 'error');
                    return;
                }
                
                // Proceed to checkout
                showNotification('กำลังไปยังหน้าชำระเงิน...', 'success');
                setTimeout(() => {
                    window.location.href = 'checkout.php';
                }, 1000);
            <?php endif; ?>
        }
        
        // Notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
        
        // Search functions
        function toggleSearch() {
            const searchBar = document.getElementById('searchBar');
            searchBar.classList.toggle('active');
        }
        
        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value;
            if(searchTerm) {
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const removeModal = document.getElementById('removeModal');
            if(event.target == removeModal) {
                closeRemoveModal();
            }
        };
        
        // Add CSS for notifications
        const style = document.createElement('style');
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                color: #333;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 0.5rem;
                z-index: 9999;
                transform: translateX(400px);
                transition: transform 0.3s;
            }
            
            .notification.show {
                transform: translateX(0);
            }
            
            .notification.success {
                border-left: 4px solid #28a745;
            }
            
            .notification.success i {
                color: #28a745;
            }
            
            .notification.error {
                border-left: 4px solid #dc3545;
            }
            
            .notification.error i {
                color: #dc3545;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>