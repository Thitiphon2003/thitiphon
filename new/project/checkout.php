<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'checkout.php';
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get cart items
$cart_query = "SELECT c.*, p.product_name, p.price, p.stock 
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

if (empty($cart_data)) {
    redirect('cart.php');
}

// Process order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $payment_method = sanitize($_POST['payment_method']);
    
    // Check stock
    $stock_ok = true;
    foreach ($cart_data as $item) {
        if ($item['quantity'] > $item['stock']) {
            $stock_ok = false;
            $error = "สินค้า {$item['product_name']} มีจำนวนในสต็อกไม่เพียงพอ";
            break;
        }
    }
    
    if ($stock_ok) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, phone, payment_method) 
                           VALUES ($user_id, $total, '$address', '$phone', '$payment_method')";
            $conn->query($order_query);
            $order_id = $conn->insert_id;
            
            // Create order items and update stock
            foreach ($cart_data as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                    VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['price']})";
                $conn->query($order_item_query);
                
                // Update stock
                $new_stock = $item['stock'] - $item['quantity'];
                $update_stock = "UPDATE products SET stock = $new_stock WHERE id = {$item['product_id']}";
                $conn->query($update_stock);
            }
            
            // Clear cart
            $clear_cart = "DELETE FROM cart WHERE user_id = $user_id";
            $conn->query($clear_cart);
            
            // Create notification
            $notify_query = "INSERT INTO notifications (user_id, title, message, type) 
                            VALUES ($user_id, 'สั่งซื้อสำเร็จ', 'คำสั่งซื้อ #$order_id อยู่ในระหว่างดำเนินการ', 'order')";
            $conn->query($notify_query);
            
            $conn->commit();
            
            $_SESSION['order_success'] = $order_id;
            redirect('order-success.php');
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<h1>สั่งซื้อสินค้า</h1>

<?php if (isset($error)): ?>
    <div style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div>
<?php endif; ?>

<div class="cart-container">
    <div class="cart-items">
        <h3>รายการสินค้า</h3>
        <?php foreach ($cart_data as $item): ?>
            <div class="cart-item">
                <div>
                    <h4><?php echo $item['product_name']; ?></h4>
                    <p>จำนวน: <?php echo $item['quantity']; ?> ชิ้น</p>
                    <p>ราคา: ฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="cart-summary">
        <h3>ข้อมูลการจัดส่ง</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="address">ที่อยู่จัดส่ง</label>
                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $user['address']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="payment_method">วิธีการชำระเงิน</label>
                <select class="form-control" id="payment_method" name="payment_method" required>
                    <option value="bank_transfer">โอนเงินผ่านธนาคาร</option>
                    <option value="credit_card">บัตรเครดิต</option>
                    <option value="cod">เก็บเงินปลายทาง</option>
                </select>
            </div>
            
            <div style="margin: 1rem 0;">
                <strong>ยอดชำระทั้งหมด: ฿<?php echo number_format($total, 2); ?></strong>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">ยืนยันการสั่งซื้อ</button>
            <a href="cart.php" class="btn btn-red" style="width: 100%; text-align: center; margin-top: 0.5rem;">กลับไปตะกร้า</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>