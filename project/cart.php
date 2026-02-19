<?php
session_start();
require_once 'db_connect.php';

// บังคับให้เข้าสู่ระบบก่อนเข้าใช้งาน
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'cart.php';
    header('Location: login.php?redirect=cart.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'ตะกร้าสินค้า';
include 'includes/header.php';

// ============================================
// จัดการ POST Requests
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        $response = ['success' => false, 'message' => ''];
        
        // อัปเดตจำนวนสินค้า
        if ($action == 'update_quantity') {
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            if ($quantity < 1) $quantity = 1;
            
            // ตรวจสอบว่าสินค้าอยู่ในตะกร้าของผู้ใช้หรือไม่
            $cart_item = fetchOne("SELECT ci.*, p.stock FROM cart_items ci 
                                   JOIN products p ON ci.product_id = p.id 
                                   WHERE ci.user_id = ? AND ci.product_id = ?", 
                                   [$user_id, $product_id]);
            
            if (!$cart_item) {
                $response['message'] = 'ไม่พบสินค้าในตะกร้า';
            } elseif ($quantity > $cart_item['stock']) {
                $response['message'] = 'สินค้ามีจำนวนไม่เพียงพอ (เหลือ ' . $cart_item['stock'] . ' ชิ้น)';
            } else {
                query("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?", 
                      [$quantity, $user_id, $product_id]);
                
                // ดึงข้อมูลล่าสุดเพื่อคำนวณราคา
                $updated_item = fetchOne("SELECT ci.*, p.price, p.shipping_fee 
                                         FROM cart_items ci 
                                         JOIN products p ON ci.product_id = p.id 
                                         WHERE ci.user_id = ? AND ci.product_id = ?", 
                                         [$user_id, $product_id]);
                
                $response['success'] = true;
                $response['message'] = 'อัปเดตจำนวนเรียบร้อย';
                $response['item_price'] = $updated_item['price'] * $updated_item['quantity'];
                $response['shipping_fee'] = $updated_item['shipping_fee'] ?? 0;
            }
        }
        
        // ลบสินค้าออกจากตะกร้า
        elseif ($action == 'remove_item') {
            $product_id = (int)$_POST['product_id'];
            
            $result = query("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
            
            if ($result->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'ลบสินค้าเรียบร้อย';
            } else {
                $response['message'] = 'ไม่พบสินค้าที่ต้องการลบ';
            }
        }
        
        // เลือก/ไม่เลือกสินค้า
        elseif ($action == 'toggle_item') {
            $product_id = (int)$_POST['product_id'];
            $selected = $_POST['selected'] === 'true' ? 1 : 0;
            
            query("UPDATE cart_items SET selected = ? WHERE user_id = ? AND product_id = ?", 
                  [$selected, $user_id, $product_id]);
            
            $response['success'] = true;
        }
        
        // เลือกทั้งหมด/ยกเลิกทั้งหมด
        elseif ($action == 'toggle_all') {
            $selected = $_POST['selected'] === 'true' ? 1 : 0;
            
            query("UPDATE cart_items SET selected = ? WHERE user_id = ?", [$selected, $user_id]);
            
            $response['success'] = true;
        }
        
        // เพิ่มไปยัง wishlist
        elseif ($action == 'add_to_wishlist') {
            $product_id = (int)$_POST['product_id'];
            
            // เริ่ม transaction
            $pdo->beginTransaction();
            
            // ตรวจสอบว่ามีใน wishlist แล้วหรือยัง
            $exists = fetchOne("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
            if (!$exists) {
                query("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)", [$user_id, $product_id]);
            }
            
            // ลบออกจากตะกร้า
            query("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
            
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = 'ย้ายไปรายการที่ชอบเรียบร้อย';
        }
        
        // ดึงข้อมูลสรุปตะกร้า
        elseif ($action == 'get_summary') {
            $items = fetchAll("SELECT ci.*, p.price, p.shipping_fee 
                              FROM cart_items ci 
                              JOIN products p ON ci.product_id = p.id 
                              WHERE ci.user_id = ?", [$user_id]);
            
            $subtotal = 0;
            $shipping = 0;
            $selected_count = 0;
            $total_items = count($items);
            
            foreach ($items as $item) {
                if ($item['selected']) {
                    $subtotal += $item['price'] * $item['quantity'];
                    $shipping += $item['shipping_fee'] ?? 0;
                    $selected_count++;
                }
            }
            
            $response['success'] = true;
            $response['data'] = [
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $subtotal + $shipping,
                'selected_count' => $selected_count,
                'total_items' => $total_items
            ];
        }
        
        echo json_encode($response);
        exit();
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        exit();
    }
}

// ============================================
// ดึงข้อมูลตะกร้าสินค้าจากฐานข้อมูล (GET Request)
// ============================================
$cart_items = fetchAll("SELECT ci.*, p.name, p.price, p.original_price, p.stock, p.shipping_fee,
                               c.name as category_name, s.name as seller_name
                        FROM cart_items ci
                        JOIN products p ON ci.product_id = p.id
                        LEFT JOIN categories c ON p.category_id = c.id
                        LEFT JOIN sellers s ON p.seller_id = s.id
                        WHERE ci.user_id = ?
                        ORDER BY ci.created_at DESC", [$user_id]);

// คำนวณราคารวม
$subtotal = 0;
$total_shipping = 0;
$selected_count = 0;

foreach ($cart_items as $item) {
    if ($item['selected']) {
        $subtotal += $item['price'] * $item['quantity'];
        $total_shipping += $item['shipping_fee'] ?? 0;
        $selected_count++;
    }
}

$total = $subtotal + $total_shipping;
?>

<style>
/* Cart Page Styles */
.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.cart-header {
    background: linear-gradient(135deg, #2563eb10, #10b98110);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.cart-item {
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: white;
    animation: slideIn 0.5s ease;
    animation-fill-mode: both;
}

.cart-item:nth-child(1) { animation-delay: 0.1s; }
.cart-item:nth-child(2) { animation-delay: 0.2s; }
.cart-item:nth-child(3) { animation-delay: 0.3s; }
.cart-item:nth-child(4) { animation-delay: 0.4s; }
.cart-item:nth-child(5) { animation-delay: 0.5s; }

.cart-item:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-color: #2563eb;
    transform: translateY(-2px);
}

.cart-item-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.seller-badge {
    background: #f1f5f9;
    padding: 0.5rem 1.5rem;
    border-radius: 30px;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.quantity-selector {
    display: inline-flex;
    align-items: center;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.quantity-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: white;
    color: #2563eb;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.quantity-btn:hover:not(:disabled) {
    background: #2563eb;
    color: white;
}

.quantity-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quantity-input {
    width: 50px;
    height: 36px;
    border: none;
    border-left: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
    text-align: center;
    font-weight: 500;
    background: white;
}

.cart-summary {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    position: sticky;
    top: 100px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px dashed #e2e8f0;
}

.summary-row.total {
    border-bottom: none;
    font-size: 1.2rem;
    font-weight: 700;
    color: #2563eb;
    padding-top: 1rem;
    margin-top: 0.5rem;
}

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
}

.empty-cart i {
    font-size: 5rem;
    color: #2563eb;
    opacity: 0.3;
    margin-bottom: 1rem;
}

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

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(30px);
    }
}

.item-removing {
    animation: fadeOut 0.3s ease forwards;
}

.loading {
    position: relative;
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30px;
    height: 30px;
    margin-left: -15px;
    margin-top: -15px;
    border: 3px solid #e2e8f0;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    z-index: 10;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.product-thumb {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
}

.product-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.action-btn {
    transition: all 0.3s ease;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.checkout-btn {
    position: relative;
    overflow: hidden;
}

.checkout-btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.checkout-btn:hover::after {
    width: 300px;
    height: 300px;
}

.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    min-width: 250px;
    margin-bottom: 10px;
    animation: slideInRight 0.3s ease;
}

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

@media (max-width: 768px) {
    .cart-item-image {
        width: 80px;
        height: 80px;
    }
    
    .cart-summary {
        position: static;
        margin-top: 2rem;
    }
}
</style>

<div class="container cart-container">
    <div class="cart-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    ตะกร้าสินค้า
                </h1>
                <p class="text-muted mb-0">มีสินค้าในตะกร้า <span id="cart-total-items"><?php echo count($cart_items); ?></span> รายการ</p>
            </div>
            <?php if (!empty($cart_items)): ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAll" <?php echo $selected_count == count($cart_items) && $selected_count > 0 ? 'checked' : ''; ?> onchange="toggleAll(this)">
                <label class="form-check-label" for="selectAll">
                    เลือกทั้งหมด
                </label>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>ตะกร้าสินค้าว่างเปล่า</h3>
            <p class="text-muted mb-4">เริ่มช้อปปิ้งและเพิ่มสินค้าลงในตะกร้ากันเลย!</p>
            <a href="category.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store me-2"></i>เริ่มช้อปปิ้ง
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- รายการสินค้า -->
            <div class="col-lg-8">
                <?php 
                // จัดกลุ่มตามร้านค้า
                $grouped_items = [];
                foreach ($cart_items as $item) {
                    $seller = $item['seller_name'] ?? 'ร้านค้าทั่วไป';
                    if (!isset($grouped_items[$seller])) {
                        $grouped_items[$seller] = [];
                    }
                    $grouped_items[$seller][] = $item;
                }
                
                foreach ($grouped_items as $seller => $items): 
                ?>
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="seller-badge">
                            <i class="fas fa-store text-primary"></i>
                            <?php echo htmlspecialchars($seller); ?>
                        </span>
                        <span class="text-muted small">
                            <i class="fas fa-truck me-1"></i>
                            ค่าจัดส่งเริ่มต้น ฿<?php echo number_format($items[0]['shipping_fee'] ?? 0); ?>
                        </span>
                    </div>
                    
                    <?php foreach ($items as $item): ?>
                    <div class="cart-item" id="item-<?php echo $item['product_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input item-checkbox" type="checkbox" 
                                           data-id="<?php echo $item['product_id']; ?>"
                                           <?php echo $item['selected'] ? 'checked' : ''; ?> 
                                           onchange="toggleItem(<?php echo $item['product_id']; ?>, this.checked)">
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="product-thumb">
                                    <?php 
                                    $image_path = "uploads/products/" . $item['product_id'] . ".jpg";
                                    if (file_exists($image_path)): ?>
                                        <img src="<?php echo $image_path . '?t=' . time(); ?>" alt="<?php echo $item['name']; ?>">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/100x100?text=Product" alt="Product">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <div class="small text-muted mb-2">
                                    <span class="me-3"><i class="fas fa-tag me-1"></i><?php echo $item['category_name'] ?? 'ทั่วไป'; ?></span>
                                    <span><i class="fas fa-box me-1"></i>คงเหลือ <span class="stock-<?php echo $item['product_id']; ?>"><?php echo $item['stock']; ?></span> ชิ้น</span>
                                </div>
                                <div class="d-flex gap-2 action-btn">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="addToWishlist(<?php echo $item['product_id']; ?>)">
                                        <i class="far fa-heart me-1"></i>เก็บไว้ภายหลัง
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="removeItem(<?php echo $item['product_id']; ?>)">
                                        <i class="fas fa-trash me-1"></i>ลบ
                                    </button>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="mb-2">
                                        <span class="fw-bold text-primary price-<?php echo $item['product_id']; ?>">฿<?php echo number_format($item['price']); ?></span>
                                        <?php if (!empty($item['original_price']) && $item['original_price'] > $item['price']): ?>
                                            <small class="text-muted text-decoration-line-through ms-2">฿<?php echo number_format($item['original_price']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="quantity-selector">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                                        <input type="text" class="quantity-input" id="qty-<?php echo $item['product_id']; ?>" value="<?php echo $item['quantity']; ?>" readonly>
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)" <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>+</button>
                                    </div>
                                    <div class="small text-muted mt-2 item-total-<?php echo $item['product_id']; ?>">
                                        รวม: ฿<?php echo number_format($item['price'] * $item['quantity']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- สรุปคำสั่งซื้อ -->
            <div class="col-lg-4">
                <div class="cart-summary" id="cartSummary">
                    <h5 class="mb-4">สรุปคำสั่งซื้อ</h5>
                    
                    <div class="summary-row">
                        <span>ราคาสินค้า (<span id="selected-count"><?php echo $selected_count; ?></span> รายการ)</span>
                        <span class="fw-bold" id="subtotal">฿<?php echo number_format($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>ค่าจัดส่ง</span>
                        <span class="fw-bold" id="shipping">฿<?php echo number_format($total_shipping); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>ยอดสุทธิ</span>
                        <span id="total">฿<?php echo number_format($total); ?></span>
                    </div>
                    
                    <button class="btn btn-primary w-100 py-3 mt-4 checkout-btn" onclick="checkout()" id="checkoutBtn">
                        <i class="fas fa-credit-card me-2"></i>ดำเนินการสั่งซื้อ
                    </button>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1 text-success"></i>
                            ซื้ออย่างปลอดภัย มั่นใจได้ทุกคำสั่งซื้อ
                        </small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                คุณแน่ใจหรือไม่ที่จะลบสินค้านี้ออกจากตะกร้า?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">ลบสินค้า</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
let deleteProductId = null;
let deleteModal;

document.addEventListener('DOMContentLoaded', function() {
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
});

// แสดง Toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}

// อัปเดตจำนวนสินค้า
function updateQuantity(productId, delta) {
    const input = document.getElementById('qty-' + productId);
    let currentQty = parseInt(input.value);
    let newQty = currentQty + delta;
    
    if (newQty < 1) newQty = 1;
    
    // ตรวจสอบสต็อก
    const stockEl = document.querySelector('.stock-' + productId);
    const maxStock = parseInt(stockEl.textContent);
    if (newQty > maxStock) {
        showToast('สินค้ามีจำนวนไม่เพียงพอ (เหลือ ' + maxStock + ' ชิ้น)', 'warning');
        return;
    }
    
    // แสดง loading
    const item = document.getElementById('item-' + productId);
    item.classList.add('loading');
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_quantity&product_id=' + productId + '&quantity=' + newQty
    })
    .then(response => response.json())
    .then(data => {
        item.classList.remove('loading');
        if (data.success) {
            input.value = newQty;
            
            // อัปเดตราคารวมของสินค้านี้
            const itemTotal = document.querySelector('.item-total-' + productId);
            if (itemTotal) {
                itemTotal.textContent = 'รวม: ฿' + data.item_price.toLocaleString();
            }
            
            updateSummary();
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        item.classList.remove('loading');
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
        console.error('Error:', error);
    });
}

// ลบสินค้า
function removeItem(productId) {
    deleteProductId = productId;
    deleteModal.show();
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (!deleteProductId) return;
    
    const item = document.getElementById('item-' + deleteProductId);
    item.classList.add('loading');
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove_item&product_id=' + deleteProductId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // เพิ่ม animation ก่อนลบ
            item.style.animation = 'fadeOut 0.3s ease forwards';
            
            setTimeout(() => {
                if (item.parentElement) {
                    item.remove();
                    
                    // อัปเดตจำนวนสินค้าในตะกร้า
                    const remainingItems = document.querySelectorAll('.cart-item').length;
                    document.getElementById('cart-total-items').textContent = remainingItems;
                    
                    // ถ้าไม่มีสินค้าเหลือ ให้รีโหลดหน้า
                    if (remainingItems === 0) {
                        location.reload();
                    } else {
                        updateSummary();
                        updateCartCount();
                    }
                }
            }, 300);
            
            showToast(data.message, 'success');
        } else {
            item.classList.remove('loading');
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        item.classList.remove('loading');
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
        console.error('Error:', error);
    });
    
    deleteModal.hide();
});

// เลือก/ไม่เลือกสินค้า
function toggleItem(productId, selected) {
    const item = document.getElementById('item-' + productId);
    item.classList.add('loading');
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_item&product_id=' + productId + '&selected=' + selected
    })
    .then(response => response.json())
    .then(data => {
        item.classList.remove('loading');
        if (data.success) {
            updateSummary();
            updateSelectAll();
        }
    })
    .catch(error => {
        item.classList.remove('loading');
        console.error('Error:', error);
    });
}

// เลือกทั้งหมด/ยกเลิกทั้งหมด
function toggleAll(checkbox) {
    const selected = checkbox.checked;
    
    // แสดง loading ทุกรายการ
    document.querySelectorAll('.cart-item').forEach(item => {
        item.classList.add('loading');
    });
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_all&selected=' + selected
    })
    .then(response => response.json())
    .then(data => {
        document.querySelectorAll('.cart-item').forEach(item => {
            item.classList.remove('loading');
        });
        
        if (data.success) {
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                cb.checked = selected;
            });
            updateSummary();
        }
    })
    .catch(error => {
        document.querySelectorAll('.cart-item').forEach(item => {
            item.classList.remove('loading');
        });
        console.error('Error:', error);
    });
}

// อัปเดตสรุปคำสั่งซื้อ
function updateSummary() {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_summary'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('selected-count').textContent = data.data.selected_count;
            document.getElementById('subtotal').textContent = '฿' + data.data.subtotal.toLocaleString();
            document.getElementById('shipping').textContent = '฿' + data.data.shipping.toLocaleString();
            document.getElementById('total').textContent = '฿' + data.data.total.toLocaleString();
            document.getElementById('cart-total-items').textContent = data.data.total_items;
        }
    })
    .catch(error => console.error('Error:', error));
}

// อัปเดต select all
function updateSelectAll() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        selectAll.checked = allChecked;
    }
}

// เพิ่มไปยัง wishlist
function addToWishlist(productId) {
    const item = document.getElementById('item-' + productId);
    item.classList.add('loading');
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_to_wishlist&product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            item.style.animation = 'fadeOut 0.3s ease forwards';
            
            setTimeout(() => {
                if (item.parentElement) {
                    item.remove();
                    
                    const remainingItems = document.querySelectorAll('.cart-item').length;
                    document.getElementById('cart-total-items').textContent = remainingItems;
                    
                    if (remainingItems === 0) {
                        location.reload();
                    } else {
                        updateSummary();
                        updateCartCount();
                    }
                }
            }, 300);
            
            showToast(data.message, 'success');
        } else {
            item.classList.remove('loading');
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        item.classList.remove('loading');
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
        console.error('Error:', error);
    });
}

// ดำเนินการสั่งซื้อ
function checkout() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked').length;
    if (selectedItems === 0) {
        showToast('กรุณาเลือกสินค้าที่ต้องการสั่งซื้อ', 'warning');
        return;
    }
    
    window.location.href = 'checkout.php';
}
</script>

<?php include 'includes/footer.php'; ?>