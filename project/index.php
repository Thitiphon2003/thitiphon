<?php
session_start();
require_once 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOP.COM - หน้าแรก</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand a {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #ffd700;
        }
        
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .cart-icon {
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .user-icon {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            font-size: 0.95rem;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            z-index: 1;
        }
        
        .user-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
        }
        
        .dropdown-content a:hover {
            background: #f8f9fa;
        }
        
        .login-btn, .register-btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        .login-btn {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .register-btn {
            background: #ffd700;
            color: #333;
            font-weight: 500;
        }
        
        .search-bar {
            max-width: 1200px;
            margin: 1rem auto 0;
            padding: 0 20px;
            display: none;
        }
        
        .search-bar.active {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-bar input {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .search-bar button {
            padding: 0.8rem 1.5rem;
            background: #ffd700;
            border: none;
            border-radius: 5px;
            color: #333;
            font-weight: 500;
            cursor: pointer;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 0;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .hero-content .welcome-user {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: #ffd700;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 1rem 2rem;
            background: #ffd700;
            color: #333;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Categories Section */
        .categories-section {
            padding: 4rem 0;
            background: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
            position: relative;
        }
        
        .section-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 1rem auto;
            border-radius: 2px;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .category-card {
            background: white;
            border-radius: 15px;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #f0f3ff;
            display: block;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102,126,234,0.15);
            border-color: #667eea;
        }
        
        .category-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .category-card:hover:before {
            transform: scaleX(1);
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            transition: all 0.3s;
        }
        
        .category-card:hover .category-icon {
            transform: scale(1.1);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        .category-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }
        
        .category-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .category-count {
            display: inline-block;
            padding: 0.3rem 1rem;
            background: #f0f3ff;
            color: #667eea;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* Products Section */
        .products-section {
            padding: 4rem 0;
            background: #f8f9fa;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 1px solid #f0f3ff;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.2);
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
            position: relative;
            background: #f8f9fa;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #333;
            line-height: 1.4;
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .product-stock {
            font-size: 0.8rem;
            color: #28a745;
        }
        
        /* View All Button */
        .view-all-container {
            text-align: center;
            margin-top: 2rem;
        }
        
        .btn-view-all {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-view-all:hover {
            background: #667eea;
            color: white;
        }
        
        /* Footer */
        .footer {
            background: #2d3748;
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 3rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-col h4 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .footer-col h4:after {
            content: '';
            display: block;
            width: 50px;
            height: 2px;
            background: #ffd700;
            margin-top: 0.5rem;
        }
        
        .footer-col p {
            color: #cbd5e0;
            line-height: 1.8;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-col ul li a {
            color: #cbd5e0;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-col ul li a:hover {
            color: #ffd700;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            color: white;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: #ffd700;
            color: #333;
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #4a5568;
            color: #cbd5e0;
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .footer-col h4:after {
                margin: 0.5rem auto;
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
                    <li><a href="category.php">หมวดหมู่</a></li>
                    <!-- แก้ไข: ลิงก์สินค้าทั้งหมดไปหน้า category.php -->
                    <li><a href="category.php">สินค้าทั้งหมด</a></li>
                    <li><a href="#contact">ติดต่อเรา</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="#" class="search-icon" onclick="toggleSearch()"><i class="fas fa-search"></i></a>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="user-dropdown">
                            <a href="#" class="user-icon">
                                <i class="fas fa-user-circle"></i>
                                <?php 
                                    $display_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 
                                                    (isset($_SESSION['username']) ? $_SESSION['username'] : 'สมาชิก');
                                    echo htmlspecialchars($display_name);
                                ?>
                            </a>
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
        <!-- Search Bar -->
        <div class="search-bar" id="searchBar">
            <input type="text" id="searchInput" placeholder="ค้นหาสินค้า...">
            <button onclick="searchProducts()"><i class="fas fa-search"></i> ค้นหา</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>ยินดีต้อนรับสู่ SHOP.COM</h1>
            <p>สินค้าคุณภาพ ราคาถูก จัดส่งไว บริการประทับใจ</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="welcome-user">
                    <i class="fas fa-hand-peace"></i> สวัสดีคุณ <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>
                </div>
            <?php endif; ?>
            <!-- แก้ไข: ปุ่มเริ่มช้อปปิ้งไปหน้า category.php -->
            <a href="category.php" class="btn-primary">เริ่มช้อปปิ้ง</a>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="categories-section">
        <div class="container">
            <h2 class="section-title">หมวดหมู่สินค้า</h2>
            <div class="category-grid">
                <?php
                // ดึงหมวดหมู่จากฐานข้อมูล
                try {
                    $categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name LIMIT 8");
                    
                    if(count($categories) > 0) {
                        foreach($categories as $category) {
                            // นับจำนวนสินค้าในหมวดหมู่
                            $product_count = fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 'active'", [$category['id']])['count'] ?? 0;
                            ?>
                            <!-- แก้ไข: ลิงก์ไปหน้า category.php พร้อม ID -->
                            <a href="category.php?id=<?php echo $category['id']; ?>" class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $category['icon'] ?? 'fas fa-tag'; ?>"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p><?php echo number_format($product_count); ?> สินค้า</p>
                                <span class="category-count">ดูสินค้า</span>
                            </a>
                            <?php
                        }
                    } else {
                        // ถ้าไม่มีข้อมูลในฐานข้อมูล ให้แสดงหมวดหมู่ตัวอย่าง
                        $sample_categories = [
                            ['id' => 1, 'name' => 'เสื้อผ้า', 'icon' => 'fas fa-tshirt', 'count' => 15],
                            ['id' => 2, 'name' => 'แฟชั่น', 'icon' => 'fas fa-hat-cowboy', 'count' => 23],
                            ['id' => 3, 'name' => 'อิเล็กทรอนิกส์', 'icon' => 'fas fa-laptop', 'count' => 42],
                            ['id' => 4, 'name' => 'เครื่องประดับ', 'icon' => 'fas fa-gem', 'count' => 18],
                            ['id' => 5, 'name' => 'ของใช้ในบ้าน', 'icon' => 'fas fa-home', 'count' => 31],
                            ['id' => 6, 'name' => 'สุขภาพและความงาม', 'icon' => 'fas fa-heart', 'count' => 27],
                            ['id' => 7, 'name' => 'อาหารและเครื่องดื่ม', 'icon' => 'fas fa-utensils', 'count' => 56],
                            ['id' => 8, 'name' => 'กีฬาและท่องเที่ยว', 'icon' => 'fas fa-futbol', 'count' => 14]
                        ];
                        
                        foreach($sample_categories as $cat) {
                            ?>
                            <a href="category.php?name=<?php echo urlencode($cat['name']); ?>" class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $cat['icon']; ?>"></i>
                                </div>
                                <h3><?php echo $cat['name']; ?></h3>
                                <p><?php echo $cat['count']; ?> สินค้า</p>
                                <span class="category-count">ดูสินค้า</span>
                            </a>
                            <?php
                        }
                    }
                } catch(Exception $e) {
                    // ถ้าเกิดข้อผิดพลาด ให้แสดงหมวดหมู่ตัวอย่าง
                    ?>
                    <a href="category.php?name=เสื้อผ้า" class="category-card">
                        <div class="category-icon"><i class="fas fa-tshirt"></i></div>
                        <h3>เสื้อผ้า</h3>
                        <p>15 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <a href="category.php?name=อิเล็กทรอนิกส์" class="category-card">
                        <div class="category-icon"><i class="fas fa-laptop"></i></div>
                        <h3>อิเล็กทรอนิกส์</h3>
                        <p>42 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <a href="category.php?name=ของใช้ในบ้าน" class="category-card">
                        <div class="category-icon"><i class="fas fa-home"></i></div>
                        <h3>ของใช้ในบ้าน</h3>
                        <p>31 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <a href="category.php?name=สุขภาพ" class="category-card">
                        <div class="category-icon"><i class="fas fa-heart"></i></div>
                        <h3>สุขภาพ</h3>
                        <p>27 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <?php
                }
                ?>
            </div>
            
            <!-- ปุ่มดูหมวดหมู่ทั้งหมด -->
            <div class="view-all-container">
                <a href="category.php" class="btn-view-all">
                    <i class="fas fa-th-large"></i> ดูหมวดหมู่ทั้งหมด
                </a>
            </div>
        </div>
    </section>

    <!-- Products Section (แสดงสินค้ามาใหม่) -->
    <section id="products" class="products-section">
        <div class="container">
            <h2 class="section-title">สินค้ามาใหม่</h2>
            
            <div class="product-grid" id="productGrid">
                <?php
                // ดึงสินค้าจากฐานข้อมูล
                try {
                    $products = fetchAll("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 4");
                    
                    if(count($products) > 0) {
                        foreach($products as $product) {
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="https://via.placeholder.com/300x300" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-price">฿<?php echo number_format($product['price']); ?></div>
                                    <div class="product-stock">คงเหลือ <?php echo $product['stock']; ?> ชิ้น</div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        // แสดงสินค้าตัวอย่าง
                        $sample_products = [
                            ['name' => 'เสื้อยืดคอปก ผู้ชาย', 'price' => 299],
                            ['name' => 'หูฟังไร้สาย Bluetooth', 'price' => 1290],
                            ['name' => 'กระเป๋าสะพายหนังแท้', 'price' => 1890],
                            ['name' => 'นาฬิกาข้อมือ Smart Watch', 'price' => 890]
                        ];
                        
                        foreach($sample_products as $product) {
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="https://via.placeholder.com/300x300" alt="<?php echo $product['name']; ?>">
                                </div>
                                <div class="product-info">
                                    <h3><?php echo $product['name']; ?></h3>
                                    <div class="product-price">฿<?php echo number_format($product['price']); ?></div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                } catch(Exception $e) {
                    ?>
                    <div class="product-card">
                        <div class="product-image"><img src="https://via.placeholder.com/300x300" alt="สินค้า"></div>
                        <div class="product-info">
                            <h3>เสื้อยืดคอปก ผู้ชาย</h3>
                            <div class="product-price">฿299</div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- ปุ่มดูสินค้าทั้งหมด -->
            <div class="view-all-container">
                <a href="category.php" class="btn-view-all">
                    <i class="fas fa-box"></i> ดูสินค้าทั้งหมด
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>เกี่ยวกับเรา</h4>
                    <p>ร้านค้าออนไลน์ที่มอบประสบการณ์การช้อปปิ้งที่ดีที่สุด ด้วยสินค้าคุณภาพ ราคายุติธรรม และบริการที่ประทับใจ</p>
                </div>
                <div class="footer-col">
                    <h4>ลิงก์ที่เกี่ยวข้อง</h4>
                    <ul>
                        <li><a href="category.php">หมวดหมู่สินค้า</a></li>
                        <li><a href="#">วิธีการสั่งซื้อ</a></li>
                        <li><a href="#">นโยบายการจัดส่ง</a></li>
                        <li><a href="#">นโยบายการคืนสินค้า</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>ติดตามเรา</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-line"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>ช่องทางการชำระเงิน</h4>
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> SHOP.COM - ร้านค้าออนไลน์. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle search bar
        function toggleSearch() {
            const searchBar = document.getElementById('searchBar');
            searchBar.classList.toggle('active');
            if(searchBar.classList.contains('active')) {
                document.getElementById('searchInput').focus();
            }
        }
        
        // Search products
        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value;
            if(searchTerm.trim()) {
                window.location.href = 'category.php?search=' + encodeURIComponent(searchTerm);
            }
        }
        
        // Enter key for search
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                searchProducts();
            }
        });
    </script>
</body>
</html>