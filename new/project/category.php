<?php
require_once 'includes/config.php';
include 'includes/new-header.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT p.*, c.category_name, s.store_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN stores s ON p.store_id = s.id 
          WHERE 1=1";

if ($category_id > 0) {
    $query .= " AND p.category_id = $category_id";
}

if ($search) {
    $query .= " AND (p.product_name LIKE '%$search%' OR p.product_description LIKE '%$search%')";
}

$query .= " ORDER BY p.created_at DESC";
$products = $conn->query($query);

// Get all categories for sidebar
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

// Get current category name
$current_category = null;
if ($category_id > 0) {
    $cat_result = $conn->query("SELECT * FROM categories WHERE id = $category_id");
    $current_category = $cat_result->fetch_assoc();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>
            <?php 
            if ($current_category) {
                echo $current_category['category_name'];
            } elseif ($search) {
                echo "Search Results: " . htmlspecialchars($search);
            } else {
                echo "สินค้าทั้งหมด";
            }
            ?>
        </h1>
        <p>ค้นพบสินค้าคุณภาพเยี่ยมที่เราคัดสรรมาอย่างพิถีพิถัน</p>
    </div>
</div>

<div class="container">
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 2rem;">
        <!-- Sidebar -->
        <div>
            <div style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: var(--shadow-md);">
                <h3 style="margin-bottom: 1rem; color: var(--dark-gray);">หมวดหมู่</h3>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 0.75rem;">
                        <a href="category.php" 
                           style="text-decoration: none; color: <?php echo !$category_id ? 'var(--primary-blue)' : 'var(--dark-gray)'; ?>; font-weight: <?php echo !$category_id ? '600' : '400'; ?>">
                            สินค้าทั้งหมด
                        </a>
                    </li>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <li style="margin-bottom: 0.75rem;">
                            <a href="category.php?id=<?php echo $cat['id']; ?>" 
                               style="text-decoration: none; color: <?php echo $category_id == $cat['id'] ? 'var(--primary-blue)' : 'var(--dark-gray)'; ?>; font-weight: <?php echo $category_id == $cat['id'] ? '600' : '400'; ?>">
                                <?php echo $cat['category_name']; ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--light-gray);">
                
                <h3 style="margin-bottom: 1rem;">ค้นหาสินค้า</h3>
                <form method="GET">
                    <?php if ($category_id): ?>
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                    <div style="display: flex;">
                        <input type="text" name="search" class="form-control" placeholder="ค้าหาสินค้า..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary" style="margin-left: 0.5rem;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div>
            <?php if ($products->num_rows == 0): ?>
                <div style="background: white; padding: 3rem; text-align: center; border-radius: 20px;">
                    <i class="fas fa-box-open" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                    <h3>No products found</h3>
                    <p style="color: var(--medium-gray);">Try adjusting your search or filter</p>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 1rem; color: var(--medium-gray);">
                    Found <?php echo $products->num_rows; ?> products
                </div>
                <div class="product-grid">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <?php if ($product['stock'] < 5): ?>
                                <div class="product-badge">Low Stock</div>
                            <?php endif; ?>
                            
                            <?php if ($product['image'] && file_exists("assets/images/" . $product['image'])): ?>
                                <img src="assets/images/<?php echo $product['image']; ?>" 
                                    alt="<?php echo $product['product_name']; ?>" 
                                    class="product-image">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                                    alt="Product" 
                                    class="product-image">
                            <?php endif; ?>
                            
                            <div class="product-info">
                                <div class="product-category"><?php echo $product['category_name']; ?></div>
                                <h3 class="product-title"><?php echo $product['product_name']; ?></h3>
                                <div class="product-variant"><?php echo $product['store_name']; ?></div>
                                <div class="product-price">
                                    <span class="current-price">฿<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">ดูรายละเอียด</a>
                                    <?php if (isLoggedIn()): ?>
                                        <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn btn-red">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/new-footer.php'; ?>