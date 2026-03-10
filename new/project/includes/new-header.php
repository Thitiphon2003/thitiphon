<?php
if (!isset($conn)) {
    require_once 'config.php';
}

// Get cart count for logged in user
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
    $cart_result = $conn->query($cart_query);
    if ($cart_result && $cart_result->num_rows > 0) {
        $cart_count = $cart_result->fetch_assoc()['total'] ?? 0;
    }
}

// Get user info for menu
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_query = "SELECT * FROM users WHERE id = {$_SESSION['user_id']}";
    $user_result = $conn->query($user_query);
    $user = $user_result->fetch_assoc();
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopHub - ช้อปปิ้งออนไลน์ที่ทันสมัย</title>
    <link rel="stylesheet" href="assets/css/new-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <button class="close-mobile-menu" id="closeMobileMenu">
                <i class="fas fa-times"></i>
            </button>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-greeting">สวัสดี, <?php echo $user['fullname'] ?: $user['username']; ?></div>
                <div class="user-email"><?php echo $user['email']; ?></div>
            <?php else: ?>
                <div class="user-greeting">ยินดีต้อนรับ</div>
                <div class="user-email">เข้าสู่ระบบเพื่อเริ่มช้อปปิ้ง</div>
            <?php endif; ?>
        </div>
        
        <ul class="mobile-menu-items">
            <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> หน้าแรก
            </a></li>
            <li><a href="category.php" class="<?php echo $current_page == 'category.php' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i> สินค้าทั้งหมด
            </a></li>
            <li><a href="notifications.php" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <i class="far fa-bell"></i> การแจ้งเตือน
            </a></li>
            <li><a href="cart.php" class="<?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> ตะกร้าสินค้า
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="account.php" class="<?php echo $current_page == 'account.php' ? 'active' : ''; ?>">
                    <i class="far fa-user"></i> บัญชีของฉัน
                </a></li>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <li><a href="admin/">
                        <i class="fas fa-cog"></i> จัดการระบบ
                    </a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        
        <div class="mobile-menu-footer">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="btn btn-red">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </a>
                <a href="register.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> สมัครสมาชิก
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <a href="index.php" class="logo">Shop<span>Hub</span></a>
            
            <!-- Desktop Hamburger Menu -->
            <button class="hamburger" id="desktopHamburger">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Desktop Dropdown Menu -->
            <div class="desktop-menu" id="desktopMenu">
                <div class="menu-header">
                    <div class="user-avatar">
                        <?php 
                        if (isset($_SESSION['user_id'])) {
                            echo strtoupper(substr($user['username'] ?? 'U', 0, 1));
                        } else {
                            echo '<i class="fas fa-user"></i>';
                        }
                        ?>
                    </div>
                    <div class="user-info">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <h4><?php echo $user['fullname'] ?: $user['username']; ?></h4>
                            <p><?php echo $user['email']; ?></p>
                        <?php else: ?>
                            <h4>แขกผู้มาเยือน</h4>
                            <p>กรุณาเข้าสู่ระบบ</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <ul class="menu-items">
                    <li><a href="index.php">
                        <i class="fas fa-home"></i> หน้าแรก
                    </a></li>
                    <li><a href="category.php">
                        <i class="fas fa-store"></i> สินค้าทั้งหมด
                    </a></li>
                    <li><a href="notifications.php">
                        <i class="far fa-bell"></i> การแจ้งเตือน
                    </a></li>
                    <li><a href="cart.php">
                        <i class="fas fa-shopping-cart"></i> ตะกร้าสินค้า
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="account.php">
                            <i class="far fa-user"></i> บัญชีของฉัน
                        </a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                            <li><a href="admin/">
                                <i class="fas fa-cog"></i> จัดการระบบ
                            </a></li>
                        <?php endif; ?>
                        <li class="logout"><a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                        </a></li>
                    <?php else: ?>
                        <li><a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                        </a></li>
                        <li><a href="register.php">
                            <i class="fas fa-user-plus"></i> สมัครสมาชิก
                        </a></li>
                    <?php endif; ?>
                </ul>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="menu-footer">
                        <p style="color: var(--medium-gray); font-size: 0.875rem;">เข้าสู่ระบบเพื่อรับสิทธิพิเศษ</p>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    
    <main>