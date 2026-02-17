<?php
session_start();
require_once 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOP.COM - Modern E-Commerce Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Navigation */
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand a {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            letter-spacing: -0.02em;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #0f172a;
        }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .cart-icon {
            color: #475569;
            text-decoration: none;
            font-size: 1.1rem;
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #0f172a;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-dropdown {
            position: relative;
        }

        .user-icon {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .dropdown-content {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 220px;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s;
            z-index: 100;
        }

        .user-dropdown:hover .dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #475569;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .dropdown-content a:hover {
            background-color: #f8fafc;
        }

        .login-btn, .register-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .login-btn {
            color: #475569;
        }

        .register-btn {
            background-color: #0f172a;
            color: white;
        }

        .register-btn:hover {
            background-color: #1e293b;
        }

        /* Hero Section */
        .hero {
            padding: 5rem 0;
            background: linear-gradient(to bottom, #f8fafc, #ffffff);
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            color: #0f172a;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.25rem;
            color: #475569;
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            background-color: #0f172a;
            color: white;
            text-decoration: none;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #1e293b;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            background-color: transparent;
            color: #0f172a;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }

        /* Sections */
        .section {
            padding: 5rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2rem;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: #475569;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .category-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 2rem 1.5rem;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }

        .category-card:hover {
            border-color: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .category-icon {
            width: 64px;
            height: 64px;
            background: #f8fafc;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
            font-size: 1.5rem;
            transition: all 0.2s;
        }

        .category-card:hover .category-icon {
            background: #0f172a;
            color: white;
        }

        .category-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .category-card p {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
            transition: all 0.2s;
        }

        .product-card:hover {
            border-color: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            aspect-ratio: 1;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 2rem;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-info h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }

        .product-category {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .current-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
        }

        .old-price {
            font-size: 0.9rem;
            color: #94a3b8;
            text-decoration: line-through;
        }

        .discount-badge {
            background: #ef4444;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .btn-add-cart {
            width: 100%;
            padding: 0.75rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            color: #0f172a;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-add-cart:hover {
            background: #0f172a;
            border-color: #0f172a;
            color: white;
        }

        /* Filters */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-group label {
            color: #64748b;
            font-size: 0.9rem;
        }

        .filter-group select {
            padding: 0.5rem 2rem 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: #0f172a;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.2rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            background: white;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }

        .page-btn:hover {
            border-color: #0f172a;
            color: #0f172a;
        }

        .page-btn.active {
            background: #0f172a;
            border-color: #0f172a;
            color: white;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 4rem 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr repeat(3, 1fr);
            gap: 4rem;
            margin-bottom: 3rem;
        }

        .footer-col h4 {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            color: #0f172a;
        }

        .footer-col p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 0.75rem;
        }

        .footer-col ul li a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .footer-col ul li a:hover {
            color: #0f172a;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 36px;
            height: 36px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s;
        }

        .social-links a:hover {
            background: #0f172a;
            border-color: #0f172a;
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-menu {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .filters-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php">SHOP.COM</a>
            </div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.php">หน้าแรก</a></li>
                    <li><a href="category.php">หมวดหมู่</a></li>
                    <li><a href="category.php">สินค้าทั้งหมด</a></li>
                    <li><a href="#contact">ติดต่อเรา</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="user-dropdown">
                            <a href="#" class="user-icon">
                                <i class="fas fa-user-circle"></i>
                                <?php echo $_SESSION['fullname'] ?? $_SESSION['username']; ?>
                            </a>
                            <div class="dropdown-content">
                                <a href="profile.php"><i class="fas fa-user"></i> โปรไฟล์ของฉัน</a>
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
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>ช้อปปิ้งออนไลน์ที่ง่ายและปลอดภัย</h1>
                <p>สินค้าคุณภาพจากร้านค้าชั้นนำ ราคาพิเศษสำหรับคุณ จัดส่งรวดเร็วทั่วประเทศ</p>
                <div class="hero-buttons">
                    <a href="category.php" class="btn-primary">
                        <i class="fas fa-store"></i>
                        เริ่มช้อปปิ้ง
                    </a>
                    <a href="#how-it-works" class="btn-secondary">
                        <i class="fas fa-play-circle"></i>
                        ดูวิธีการสั่งซื้อ
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>หมวดหมู่สินค้า</h2>
                <p>เลือกซื้อสินค้าจากหลากหลายหมวดหมู่ที่คุณชื่นชอบ</p>
            </div>
            <div class="category-grid">
                <?php
                try {
                    $categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name LIMIT 8");
                    
                    if(count($categories) > 0) {
                        foreach($categories as $category) {
                            $product_count = fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 'active'", [$category['id']])['count'] ?? 0;
                            ?>
                            <a href="category.php?id=<?php echo $category['id']; ?>" class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $category['icon'] ?? 'fas fa-tag'; ?>"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p><?php echo number_format($product_count); ?> สินค้า</p>
                            </a>
                            <?php
                        }
                    } else {
                        $sample_categories = [
                            ['name' => 'เสื้อผ้า', 'icon' => 'fas fa-tshirt', 'count' => 15],
                            ['name' => 'อิเล็กทรอนิกส์', 'icon' => 'fas fa-laptop', 'count' => 42],
                            ['name' => 'ของใช้ในบ้าน', 'icon' => 'fas fa-home', 'count' => 31],
                            ['name' => 'สุขภาพ', 'icon' => 'fas fa-heart', 'count' => 27],
                            ['name' => 'แฟชั่น', 'icon' => 'fas fa-gem', 'count' => 23],
                            ['name' => 'อาหาร', 'icon' => 'fas fa-utensils', 'count' => 56],
                            ['name' => 'กีฬา', 'icon' => 'fas fa-futbol', 'count' => 14],
                            ['name' => 'หนังสือ', 'icon' => 'fas fa-book', 'count' => 38]
                        ];
                        
                        foreach($sample_categories as $cat) {
                            ?>
                            <a href="category.php?name=<?php echo urlencode($cat['name']); ?>" class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $cat['icon']; ?>"></i>
                                </div>
                                <h3><?php echo $cat['name']; ?></h3>
                                <p><?php echo $cat['count']; ?> สินค้า</p>
                            </a>
                            <?php
                        }
                    }
                } catch(Exception $e) {
                    ?>
                    <a href="category.php" class="category-card">
                        <div class="category-icon"><i class="fas fa-tshirt"></i></div>
                        <h3>เสื้อผ้า</h3>
                        <p>15 สินค้า</p>
                    </a>
                    <a href="category.php" class="category-card">
                        <div class="category-icon"><i class="fas fa-laptop"></i></div>
                        <h3>อิเล็กทรอนิกส์</h3>
                        <p>42 สินค้า</p>
                    </a>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2>สินค้ามาใหม่</h2>
                <p>พบกับสินค้าใหม่ล่าสุดที่มาแรงตอนนี้</p>
            </div>

            <div class="filters-bar">
                <div class="filter-group">
                    <label>เรียงตาม:</label>
                    <select onchange="sortProducts(this.value)">
                        <option value="newest">มาใหม่ล่าสุด</option>
                        <option value="price-low">ราคาต่ำไปสูง</option>
                        <option value="price-high">ราคาสูงไปต่ำ</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>แสดง:</label>
                    <select onchange="showPerPage(this.value)">
                        <option value="12">12 ชิ้น</option>
                        <option value="24">24 ชิ้น</option>
                        <option value="36">36 ชิ้น</option>
                    </select>
                </div>
            </div>

            <div class="product-grid">
                <?php
                try {
                    $products = fetchAll("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
                    
                    if(count($products) > 0) {
                        foreach($products as $product) {
                            ?>
                            <div class="product-card">
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <div class="product-image">
                                        <?php if($product['image']): ?>
                                            <img src="<?php echo showImage($product['image'], 'products', 'default-product.jpg'); ?>" alt="<?php echo $product['name']; ?>">
                                        <?php else: ?>
                                            <i class="fas fa-box"></i>
                                        <?php endif; ?>
                                        <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                            <span class="discount-badge">-<?php echo round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="product-price">
                                            <span class="current-price">฿<?php echo number_format($product['price']); ?></span>
                                            <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                                <span class="old-price">฿<?php echo number_format($product['original_price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-shopping-bag"></i> หยิบใส่ตะกร้า
                                        </button>
                                    </div>
                                </a>
                            </div>
                            <?php
                        }
                    } else {
                        for($i = 1; $i <= 4; $i++) {
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="product-info">
                                    <h3>สินค้าตัวอย่างที่ <?php echo $i; ?></h3>
                                    <div class="product-price">
                                        <span class="current-price">฿<?php echo number_format(299 * $i); ?></span>
                                    </div>
                                    <button class="btn-add-cart" onclick="addToCart(0)">
                                        <i class="fas fa-shopping-bag"></i> หยิบใส่ตะกร้า
                                    </button>
                                </div>
                            </div>
                            <?php
                        }
                    }
                } catch(Exception $e) {
                    ?>
                    <div class="product-card">
                        <div class="product-image"><i class="fas fa-box"></i></div>
                        <div class="product-info">
                            <h3>สินค้าตัวอย่าง</h3>
                            <div class="product-price">
                                <span class="current-price">฿299</span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <div class="pagination">
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn">4</button>
                <button class="page-btn">5</button>
                <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>SHOP.COM</h4>
                    <p>แพลตฟอร์มอีคอมเมิร์ซที่เชื่อถือได้ มอบประสบการณ์การช้อปปิ้งที่ดีที่สุดให้กับคุณ</p>
                </div>
                <div class="footer-col">
                    <h4>บริการของเรา</h4>
                    <ul>
                        <li><a href="category.php">สินค้าทั้งหมด</a></li>
                        <li><a href="#">วิธีการสั่งซื้อ</a></li>
                        <li><a href="#">นโยบายการจัดส่ง</a></li>
                        <li><a href="#">การรับประกันสินค้า</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>ช่วยเหลือ</h4>
                    <ul>
                        <li><a href="#">คำถามที่พบบ่อย</a></li>
                        <li><a href="#">ติดต่อเรา</a></li>
                        <li><a href="#">นโยบายความเป็นส่วนตัว</a></li>
                        <li><a href="#">เงื่อนไขการใช้บริการ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>ติดตามเรา</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-line"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> SHOP.COM. สงวนลิขสิทธิ์</p>
            </div>
        </div>
    </footer>

    <script>
        function sortProducts(value) {
            console.log('Sort by:', value);
        }

        function showPerPage(value) {
            console.log('Show per page:', value);
        }

        function addToCart(productId) {
            alert('เพิ่มสินค้าลงตะกร้าเรียบร้อย');
        }
    </script>
</body>
</html>