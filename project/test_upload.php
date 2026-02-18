<?php
require_once 'db_connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_image'])) {
    $result = uploadImage($_FILES['test_image'], 'products');
    
    if ($result['success']) {
        $message = 'อัปโหลดสำเร็จ: ' . $result['filename'];
    } else {
        $error = $result['message'];
    }
}

// ดึงรายชื่อไฟล์ในโฟลเดอร์ products
$image_files = glob("uploads/products/*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
?>

<!DOCTYPE html>
<html>
<head>
    <title>ทดสอบอัปโหลดรูป</title>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', sans-serif; padding: 20px; background: #f1f5f9; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 4px; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 4px; }
        input, button { padding: 10px; margin: 5px 0; }
        .image-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 20px; }
        .image-item { text-align: center; }
        .image-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; }
        .filename { font-size: 0.8rem; color: #64748b; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 ทดสอบอัปโหลดรูปภาพ</h1>
        
        <div class="card">
            <h2>อัปโหลดรูปใหม่</h2>
            
            <?php if ($message): ?>
                <div class="success">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="test_image" accept="image/*" required>
                <br>
                <button type="submit">ทดสอบอัปโหลด</button>
            </form>
        </div>
        
        <div class="card">
            <h2>รูปภาพในโฟลเดอร์ products (<?php echo count($image_files); ?> ไฟล์)</h2>
            
            <?php if (count($image_files) > 0): ?>
                <div class="image-grid">
                    <?php foreach ($image_files as $file): 
                        $filename = basename($file);
                    ?>
                        <div class="image-item">
                            <img src="<?php echo $file; ?>" alt="<?php echo $filename; ?>">
                            <div class="filename"><?php echo $filename; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>ยังไม่มีรูปภาพในโฟลเดอร์</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>ตรวจสอบสิทธิ์โฟลเดอร์</h2>
            <?php
            $upload_dir = "uploads/products/";
            if (is_writable($upload_dir)) {
                echo "<p class='success'>✅ โฟลเดอร์สามารถเขียนได้</p>";
            } else {
                echo "<p class='error'>❌ โฟลเดอร์ไม่สามารถเขียนได้</p>";
            }
            
            $upload_dir_full = realpath($upload_dir);
            echo "<p>ที่อยู่โฟลเดอร์: " . $upload_dir_full . "</p>";
            ?>
        </div>
    </div>
</body>
</html>