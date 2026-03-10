<?php
require_once 'connectdb.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'cart.php';
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle add to cart
if (isset($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    
    // Check if product already in cart
    $check_query = "SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id";
    $check_result = $conn->query($check_query);
    
    if ($check_result && $check_result->num_rows > 0) {
        // Update quantity
        $cart_item = $check_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + 1;
        $update_query = "UPDATE cart SET quantity = $new_quantity WHERE id = {$cart_item['id']}";
        $conn->query($update_query);
    } else {
        // Insert new cart item
        $insert_query = "INSERT INTO cart (user_id, product_id) VALUES ($user_id, $product_id)";
        $conn->query($insert_query);
    }
    
    $_SESSION['cart_message'] = "เพิ่มสินค้าลงตะกร้าเรียบร้อย";
    redirect('cart.php');
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $delete_query = "DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id";
    $conn->query($delete_query);
    redirect('cart.php');
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $quantity) {
        $quantity = (int)$quantity;
        if ($quantity > 0) {
            $update_query = "UPDATE cart SET quantity = $quantity WHERE id = $cart_id AND user_id = $user_id";
            $conn->query($update_query);
        }
    }
    redirect('cart.php');
}

// Get cart items
$cart_query = "SELECT c.*, p.product_name, p.price, p.image, p.stock, p.product_description
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = $user_id";
$cart_items = $conn->query($cart_query);

// Calculate total
$total = 0;
$cart_data = [];
if ($cart_items && $cart_items->num_rows > 0) {
    while ($item = $cart_items->fetch_assoc()) {
        $total += $item['price'] * $item['quantity'];
        $cart_data[] = $item;
    }
}

include 'includes/new-header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>ตะกร้าสินค้า</h1>
        <p>ตรวจสอบและจัดการสินค้าในตะกร้าของคุณ</p>
    </div>
</div>

<div class="container">
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
            <?php 
            echo $_SESSION['cart_message']; 
            unset($_SESSION['cart_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_data)): ?>
        <div style="background: white; padding: 4rem; text-align: center; border-radius: 20px; box-shadow: var(--shadow-md);">
            <i class="fas fa-shopping-cart" style="font-size: 5rem; color: var(--medium-gray); margin-bottom: 1.5rem;"></i>
            <h2 style="margin-bottom: 1rem;">ตะกร้าสินค้าของคุณว่างเปล่า</h2>
            <p style="color: var(--medium-gray); margin-bottom: 2rem;">เริ่มช้อปปิ้งเพื่อเพิ่มสินค้าในตะกร้า</p>
            <a href="category.php" class="btn btn-primary" style="padding: 1rem 3rem;">
                <i class="fas fa-shopping-bag"></i> เริ่มช้อปปิ้ง
            </a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <!-- Cart Items -->
            <div class="cart-items">
                <h2 style="margin-bottom: 1.5rem;">สินค้าในตะกร้า (<?php echo count($cart_data); ?> รายการ)</h2>
                
                <form method="POST" id="cart-form">
                    <?php foreach ($cart_data as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo $item['image'] && file_exists("assets/images/".$item['image']) ? 'assets/images/'.$item['image'] : 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=100'; ?>" 
                                 alt="<?php echo $item['product_name']; ?>">
                            <div style="flex: 1;">
                                <h3 style="margin-bottom: 0.5rem;"><?php echo $item['product_name']; ?></h3>
                                <p style="color: var(--medium-gray); font-size: 0.9rem; margin-bottom: 1rem;">
                                    <?php echo substr($item['product_description'], 0, 100); ?>...
                                </p>
                                <div style="display: flex; align-items: center; gap: 2rem;">
                                    <div>
                                        <label style="font-size: 0.9rem; color: var(--medium-gray);">จำนวน:</label>
                                        <input type="number" 
                                               name="quantity[<?php echo $item['id']; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['stock']; ?>"
                                               style="width: 80px; padding: 0.5rem; border: 2px solid var(--light-gray); border-radius: 8px; margin-left: 0.5rem;">
                                        <span style="font-size: 0.8rem; color: var(--medium-gray); margin-left: 0.5rem;">/ สต็อก <?php echo $item['stock']; ?></span>
                                    </div>
                                    <div>
                                        <strong style="font-size: 1.25rem; color: var(--primary-red);">฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                        <span style="font-size: 0.9rem; color: var(--medium-gray);"> (฿<?php echo number_format($item['price'], 2); ?> / ชิ้น)</span>
                                    </div>
                                </div>
                            </div>
                            <a href="?remove=<?php echo $item['id']; ?>" 
                               class="btn btn-red" 
                               style="padding: 0.75rem;"
                               onclick="return confirm('ลบสินค้านี้ออกจากตะกร้า?')"
                               title="ลบ">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 2rem; display: flex; justify-content: space-between;">
                        <button type="submit" name="update_cart" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> อัปเดตตะกร้า
                        </button>
                        <a href="category.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> เลือกซื้อเพิ่มเติม
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary">
                <h2 style="margin-bottom: 1.5rem;">สรุปคำสั่งซื้อ</h2>
                
                <div style="margin-bottom: 2rem;">
                    <?php 
                    $subtotal = $total;
                    $shipping = $total >= 500 ? 0 : 50;
                    $tax = $total * 0.07; // 7% VAT
                    $grand_total = $subtotal + $shipping + $tax;
                    ?>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="color: var(--medium-gray);">ราคาสินค้ารวม:</span>
                        <span>฿<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="color: var(--medium-gray);">ค่าจัดส่ง:</span>
                        <span>
                            <?php if ($shipping == 0): ?>
                                <span style="color: #28a745;">ฟรี</span>
                            <?php else: ?>
                                ฿<?php echo number_format($shipping, 2); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="color: var(--medium-gray);">ภาษีมูลค่าเพิ่ม (7%):</span>
                        <span>฿<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div style="border-top: 2px dashed var(--light-gray); margin: 1rem 0; padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700;">
                            <span>ยอดรวมทั้งสิ้น:</span>
                            <span style="color: var(--primary-red);">฿<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($shipping > 0): ?>
                        <div style="background: #e3f2fd; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                            <p style="color: var(--primary-blue); font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> ซื้อเพิ่มอีก ฿<?php echo number_format(500 - $subtotal, 2); ?> เพื่อรับสิทธิ์จัดส่งฟรี!
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <a href="checkout.php" class="btn btn-primary" style="width: 100%; padding: 1rem; text-align: center; font-size: 1.1rem; margin-bottom: 1rem;">
                    <i class="fas fa-credit-card"></i> ดำเนินการสั่งซื้อ
                </a>
                
                <!-- Payment Methods -->
                <div style="text-align: center;">
                    <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">รับชำระผ่าน</p>
                    <div style="display: flex; justify-content: center; gap: 1rem; font-size: 2rem; color: var(--medium-gray);">
                        <i class="fab fa-cc-visa" title="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/new-footer.php'; ?>