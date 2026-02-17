<?php
session_start();
require_once 'db_connect.php';

// รับค่าจาก URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_name = isset($_GET['name']) ? trim($_GET['name']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$page_title = 'สินค้าทั้งหมด';
$where = "WHERE status = 'active'";
$params = [];

// ถ้ามีการค้นหา
if(!empty($search)) {
    $where .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $page_title = "ค้นหา: $search";
}
// ถ้ามีการระบุหมวดหมู่
elseif($category_id > 0) {
    $category = fetchOne("SELECT * FROM categories WHERE id = ? AND status = 'active'", [$category_id]);
    if($category) {
        $where .= " AND category_id = ?";
        $params[] = $category_id;
        $page_title = $category['name'];
    }
} elseif(!empty($category_name)) {
    $category = fetchOne("SELECT * FROM categories WHERE name = ? AND status = 'active'", [$category_name]);
    if($category) {
        $where .= " AND category_id = ?";
        $params[] = $category['id'];
        $page_title = $category['name'];
    }
}

// ดึงสินค้า
$sql = "SELECT * FROM products $where ORDER BY created_at DESC";
$products = fetchAll($sql, $params);

// ดึงหมวดหมู่ทั้งหมดสำหรับ Sidebar
$all_categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - SHOP.COM</title>
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
        }
        
        .user-dropdown {
            position: relative;
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
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .user-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .login-btn, .register-btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .login-btn {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .register-btn {
            background: #ffd700;
            color: #333;
        }
        
        /* Category Page Layout */
        .category-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }
        
        /* Sidebar */
        .category-sidebar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .sidebar-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f3ff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-title i {
            color: #667eea;
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-item {
            margin-bottom: 0.5rem;
        }
        
        .category-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 1rem;
            color: #555;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .category-link:hover {
            background: #f0f3ff;
            color: #667eea;
        }
        
        .category-link.active {
            background: #667eea;
            color: white;
        }
        
        .category-count {
            background: #e1e5e9;
            color: #666;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .category-link.active .category-count {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Main Content */
        .category-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        /* Breadcrumb */
        .breadcrumb {
            margin-bottom: 1.5rem;
            color: #999;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb i {
            margin: 0 0.5rem;
            font-size: 0.8rem;
        }
        
        /* Header */
        .category-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f3ff;
        }
        
        .category-header h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .product-count {
            color: #666;
            font-size: 1rem;
        }
        
        /* Filters */
        .category-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-group label {
            color: #666;
            font-weight: 500;
        }
        
        .filter-group select {
            padding: 0.5rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-family: inherit;
            cursor: pointer;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: 1px solid #f0f3ff;
            text-decoration: none;
            color: inherit;
            display: block;
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
        
        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff4444;
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1;
        }
        
        .product-info {
            padding: 1.2rem;
        }
        
        .product-info h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: #333;
            line-height: 1.4;
            height: 2.8rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-category {
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            margin-bottom: 0.5rem;
        }
        
        .current-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .old-price {
            font-size: 0.85rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 0.5rem;
        }
        
        .product-stock {
            font-size: 0.8rem;
            color: #28a745;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #667eea;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .btn-shop {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e1e5e9;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn:hover {
            background: #f8f9fa;
        }
        
        .page-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        /* Footer */
        .footer {
            background: #2d3748;
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #4a5568;
            color: #cbd5e0;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .category-container {
                grid-template-columns: 1fr;
            }
            
            .category-sidebar {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .category-filters {
                flex-direction: column;
                gap: 1rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
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
                    <li><a href="category.php">หมวดหมู่</a></li>
                    <li><a href="category.php">สินค้าทั้งหมด</a></li>
                    <li><a href="#contact">ติดต่อเรา</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="user-dropdown">
                            <a href="#" class="user-icon">
                                <i class="fas fa-user-circle"></i>
                                <?php echo $_SESSION['fullname'] ?? $_SESSION['username']; ?>
                            </a>
                            <div class="dropdown-content">
                                <a href="profile.php"><i class="fas fa-user-circle"></i> โปรไฟล์</a>
                                <a href="orders.php"><i class="fas fa-shopping-bag"></i> คำสั่งซื้อ</a>
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

    <!-- Category Page -->
    <div class="category-container">
        <!-- Sidebar -->
        <div class="category-sidebar">
            <h3 class="sidebar-title">
                <i class="fas fa-th-large"></i> หมวดหมู่ทั้งหมด
            </h3>
            <ul class="category-list">
                <?php
                $total_products = fetchOne("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'] ?? 0;
                $is_all_active = ($category_id == 0 && empty($category_name) && empty($search)) ? 'active' : '';
                ?>
                <li class="category-item">
                    <a href="category.php" class="category-link <?php echo $is_all_active; ?>">
                        <span><i class="fas fa-box"></i> สินค้าทั้งหมด</span>
                        <span class="category-count"><?php echo $total_products; ?></span>
                    </a>
                </li>
                <?php
                foreach($all_categories as $cat) {
                    $count = fetchOne("SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 'active'", [$cat['id']])['COUNT(*)'] ?? 0;
                    $active = ($category_id == $cat['id']) ? 'active' : '';
                    ?>
                    <li class="category-item">
                        <a href="category.php?id=<?php echo $cat['id']; ?>" class="category-link <?php echo $active; ?>">
                            <span><i class="<?php echo $cat['icon'] ?? 'fas fa-tag'; ?>" style="width: 20px;"></i> <?php echo htmlspecialchars($cat['name']); ?></span>
                            <span class="category-count"><?php echo $count; ?></span>
                        </a>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="category-content">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">หน้าแรก</a>
                <i class="fas fa-chevron-right"></i>
                <?php if(!empty($search)): ?>
                    <a href="category.php">สินค้า</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>ค้นหา "<?php echo htmlspecialchars($search); ?>"</span>
                <?php else: ?>
                    <a href="category.php">สินค้า</a>
                    <?php if($category_id > 0 || !empty($category_name)): ?>
                        <i class="fas fa-chevron-right"></i>
                        <span><?php echo htmlspecialchars($page_title); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Header -->
            <div class="category-header">
                <h1>
                    <?php if(!empty($search)): ?>
                        <i class="fas fa-search"></i> 
                    <?php elseif($category_id > 0 || !empty($category_name)): ?>
                        <i class="fas fa-folder"></i> 
                    <?php else: ?>
                        <i class="fas fa-box"></i> 
                    <?php endif; ?>
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>
                <span class="product-count">พบ <?php echo count($products); ?> รายการ</span>
            </div>
            
            <!-- Filters -->
            <div class="category-filters">
                <div class="filter-group">
                    <label><i class="fas fa-sort"></i> เรียงตาม:</label>
                    <select onchange="sortProducts(this.value)">
                        <option value="newest">มาใหม่ล่าสุด</option>
                        <option value="price-low">ราคาต่ำไปสูง</option>
                        <option value="price-high">ราคาสูงไปต่ำ</option>
                        <option value="popular">ยอดนิยม</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-eye"></i> แสดง:</label>
                    <select onchange="showPerPage(this.value)">
                        <option value="12">12 ชิ้น</option>
                        <option value="24">24 ชิ้น</option>
                        <option value="36">36 ชิ้น</option>
                    </select>
                </div>
            </div>
            
            <!-- Products Grid -->
            <?php if(count($products) > 0): ?>
                <div class="products-grid" id="productsGrid">
                    <?php foreach($products as $product): 
                        // ดึงชื่อหมวดหมู่
                        $cat_name = '';
                        if($product['category_id']) {
                            $cat = fetchOne("SELECT name FROM categories WHERE id = ?", [$product['category_id']]);
                            $cat_name = $cat ? $cat['name'] : '';
                        }
                    ?>
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-card">
                            <div class="product-image">
                                <img src="<?php echo showImage($product['image'], 'products', 'default-product.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                    <?php $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>
                                    <span class="discount-badge">-<?php echo $discount; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <?php if($cat_name): ?>
                                    <div class="product-category">
                                        <i class="fas fa-folder"></i> <?php echo htmlspecialchars($cat_name); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="product-price">
                                    <span class="current-price">฿<?php echo number_format($product['price']); ?></span>
                                    <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                        <span class="old-price">฿<?php echo number_format($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-stock">
                                    <i class="fas fa-box"></i> คงเหลือ <?php echo $product['stock']; ?> ชิ้น
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="pagination">
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">4</button>
                    <button class="page-btn">5</button>
                    <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>ไม่พบสินค้า</h3>
                    <p>กรุณาลองค้นหาหรือเลือกหมวดหมู่อื่น</p>
                    <a href="category.php" class="btn-shop">
                        <i class="fas fa-store"></i> ดูสินค้าทั้งหมด
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> SHOP.COM - ร้านค้าออนไลน์. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <script>
        function sortProducts(value) {
            console.log('Sort by:', value);
            // สามารถเพิ่มฟังก์ชันเรียงลำดับได้
        }
        
        function showPerPage(value) {
            console.log('Show per page:', value);
            // สามารถเพิ่มฟังก์ชันแสดงจำนวนต่อหน้าได้
        }
    </script>
</body>
</html>