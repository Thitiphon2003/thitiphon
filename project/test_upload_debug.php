<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['admin_id'])) {
    die('กรุณาเข้าสู่ระบบ');
}

$message = '';
$error = '';

// ทดสอบอัปโหลด
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_image'])) {
    $product_id = (int)($_POST['product_id'] ?? 1);
    $is_primary = isset($_POST['is_primary']);
    
    echo "<h3>ข้อมูลที่ได้รับ:</h3>";
    echo "<pre>";
    print_r($_FILES['test_image']);
    echo "</pre>";
    
    $result = uploadProductImage($_FILES['test_image'], $product_id, $is_primary);
    
    if ($result['success']) {
        $message = "อัปโหลดสำเร็จ: " . $result['filename'];
    } else {
        $error = "อัปโหลดล้มเหลว: " . $result['message'];
    }
}

// ดึงรายการสินค้า
$products = fetchAll("SELECT id, name FROM products ORDER BY id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>ทดสอบอัปโหลดรูป</title>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', sans-serif; padding: 20px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 4px; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ทดสอบระบบอัปโหลดรูปภาพ</h1>
        
        <div class="card">
            <h3>📤 ทดสอบอัปโหลด</h3>
            
            <?php if ($message): ?>
                <div class="success">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 10px;">
                    <label>เลือกสินค้า:</label>
                    <select name="product_id" required>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>">#<?php echo $p['id']; ?> - <?php echo $p['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label>เลือกรูปภาพ:</label>
                    <input type="file" name="test_image" accept="image/*" required>
                </div>
                <div style="margin-bottom: 10px;">
                    <label>
                        <input type="checkbox" name="is_primary"> ตั้งเป็นรูปหลัก
                    </label>
                </div>
                <button type="submit">ทดสอบอัปโหลด</button>
            </form>
        </div>
        
        <div class="card">
            <h3>📸 รูปภาพในระบบ</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Product ID</th>
                    <th>รูป</th>
                    <th>ชื่อไฟล์</th>
                    <th>หลัก</th>
                    <th>ลำดับ</th>
                </tr>
                <?php
                $images = fetchAll("SELECT * FROM product_images ORDER BY product_id, sort_order");
                foreach ($images as $img):
                ?>
                <tr>
                    <td><?php echo $img['id']; ?></td>
                    <td><?php echo $img['product_id']; ?></td>
                    <td><img src="uploads/products/<?php echo $img['image_path']; ?>" onerror="this.src='https://via.placeholder.com/60x60?text=Error'"></td>
                    <td><?php echo $img['image_path']; ?></td>
                    <td><?php echo $img['is_primary'] ? '✅' : '❌'; ?></td>
                    <td><?php echo $img['sort_order']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h3>📁 ตรวจสอบโฟลเดอร์</h3>
            <?php
            $upload_dir = "uploads/products/";
            echo "<p>โฟลเดอร์: " . realpath($upload_dir) . "</p>";
            echo "<p>สามารถเขียนได้: " . (is_writable($upload_dir) ? '✅' : '❌') . "</p>";
            
            $files = glob($upload_dir . "*");
            echo "<p>ไฟล์ทั้งหมด: " . count($files) . " ไฟล์</p>";
            ?>
        </div>
    </div>
</body>
</html>