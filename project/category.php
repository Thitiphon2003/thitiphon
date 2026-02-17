<?php
session_start();
require_once 'db_connect.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$page_title = 'สินค้าทั้งหมด';
$where = "WHERE status = 'active'";
$params = [];

if(!empty($search)) {
    $where .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $page_title = "ค้นหา: $search";
} elseif($category_id > 0) {
    $category = fetchOne("SELECT * FROM categories WHERE id = ? AND status = 'active'", [$category_id]);
    if($category) {
        $where .= " AND category_id = ?";
        $params[] = $category_id;
        $page_title = $category['name'];
    }
}

$products = fetchAll("SELECT * FROM products $where ORDER BY created_at DESC", $params);
$all_categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

include 'includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="index.php">หน้าแรก</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($page_title); ?></span>
    </div>

    <div class="category-container" style="display: grid; grid-template-columns: 280px 1fr; gap: 2rem; margin: 2rem 0;">
        <!-- Sidebar -->
        <div class="category-sidebar" style="background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.5rem; height: fit-content;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                <i class="fas fa-th-large"></i> หมวดหมู่ทั้งหมด
            </h3>
            <ul style="list-style: none;">
                <?php
                $total_products = fetchOne("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'] ?? 0;
                $is_all_active = ($category_id == 0 && empty($search)) ? 'active' : '';
                ?>
                <li style="margin-bottom: 0.5rem;">
                    <a href="category.php" style="display: flex; justify-content: space-between; padding: 0.75rem; color: <?php echo $is_all_active ? '#0f172a' : '#475569'; ?>; background: <?php echo $is_all_active ? '#f8fafc' : 'transparent'; ?>; border-radius: 0.375rem; text-decoration: none; font-weight: <?php echo $is_all_active ? '600' : '400'; ?>;">
                        <span><i class="fas fa-box"></i> สินค้าทั้งหมด</span>
                        <span style="background: #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem;"><?php echo $total_products; ?></span>
                    </a>
                </li>
                <?php foreach($all_categories as $cat): 
                    $count = fetchOne("SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 'active'", [$cat['id']])['COUNT(*)'] ?? 0;
                    $active = ($category_id == $cat['id']) ? 'active' : '';
                ?>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="category.php?id=<?php echo $cat['id']; ?>" style="display: flex; justify-content: space-between; padding: 0.75rem; color: <?php echo $active ? '#0f172a' : '#475569'; ?>; background: <?php echo $active ? '#f8fafc' : 'transparent'; ?>; border-radius: 0.375rem; text-decoration: none; font-weight: <?php echo $active ? '600' : '400'; ?>;">
                            <span><i class="<?php echo $cat['icon'] ?? 'fas fa-tag'; ?>"></i> <?php echo htmlspecialchars($cat['name']); ?></span>
                            <span style="background: #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem;"><?php echo $count; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Main Content -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1 style="font-size: 2rem; color: #0f172a;"><?php echo htmlspecialchars($page_title); ?></h1>
                <span style="color: #64748b;">พบ <?php echo count($products); ?> รายการ</span>
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

            <?php if(count($products) > 0): ?>
                <div class="product-grid">
                    <?php foreach($products as $product): 
                        $cat_name = '';
                        if($product['category_id']) {
                            $cat = fetchOne("SELECT name FROM categories WHERE id = ?", [$product['category_id']]);
                            $cat_name = $cat ? $cat['name'] : '';
                        }
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
                                    <?php if($cat_name): ?>
                                        <div class="product-category"><?php echo htmlspecialchars($cat_name); ?></div>
                                    <?php endif; ?>
                                    <div class="product-price">
                                        <span class="current-price">฿<?php echo number_format($product['price']); ?></span>
                                        <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                            <span class="old-price">฿<?php echo number_format($product['original_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                            <div style="padding: 0 1.5rem 1.5rem;">
                                <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-bag"></i> หยิบใส่ตะกร้า
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="pagination">
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">4</button>
                    <button class="page-btn">5</button>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: #f8fafc; border-radius: 0.75rem;">
                    <i class="fas fa-box-open" style="font-size: 4rem; color: #94a3b8; margin-bottom: 1rem;"></i>
                    <h3 style="color: #0f172a; margin-bottom: 0.5rem;">ไม่พบสินค้า</h3>
                    <p style="color: #64748b; margin-bottom: 1.5rem;">กรุณาลองค้นหาหรือเลือกหมวดหมู่อื่น</p>
                    <a href="category.php" class="btn btn-primary">ดูสินค้าทั้งหมด</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>