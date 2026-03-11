<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<h2>🔧 แก้ไขชื่อไฟล์รูปภาพในฐานข้อมูล</h2>";

// ดึงรายการสินค้าที่ไม่มีรูป
$products = $conn->query("SELECT id, product_name, image FROM products WHERE image IS NULL OR image = ''");

if ($products->num_rows > 0) {
    echo "<h3>📦 สินค้าที่ยังไม่มีรูป:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>ชื่อสินค้า</th><th>ไฟล์รูปที่มี</th><th>เลือก</th></tr>";
    
    // ดึงรายชื่อไฟล์จากโฟลเดอร์
    $files = scandir('assets/images/');
    $image_files = array_diff($files, array('.', '..', 'stores'));
    
    while ($product = $products->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>";
        echo "<select name='file_{$product['id']}' id='file_{$product['id']}'>";
        echo "<option value=''>- เลือกไฟล์ -</option>";
        foreach ($image_files as $file) {
            echo "<option value='{$file}'>$file</option>";
        }
        echo "</select>";
        echo "</td>";
        echo "<td>";
        echo "<button onclick='updateImage({$product['id']})'>อัปเดต</button>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>✅ สินค้าทุกตัวมีรูปภาพแล้ว</p>";
}

// แสดงรูปทั้งหมดในโฟลเดอร์
echo "<h3>📸 รูปภาพในโฟลเดอร์ assets/images/</h3>";
echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";
foreach ($image_files as $file) {
    $file_path = "assets/images/$file";
    $file_size = filesize($file_path);
    echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 5px;'>";
    echo "<img src='$file_path' width='100' height='100' style='object-fit: cover;'><br>";
    echo "<small>$file</small><br>";
    echo "<small>ขนาด: " . round($file_size / 1024, 2) . " KB</small>";
    echo "</div>";
}
echo "</div>";

// แสดง SQL ที่ต้องรัน
echo "<h3>📝 SQL Command ที่ต้องรัน</h3>";
echo "<pre style='background: #f4f4f4; padding: 10px;'>";
foreach ($image_files as $index => $file) {
    $id = $index + 1; // สมมติ id เรียงตามลำดับ
    echo "UPDATE products SET image = '$file' WHERE id = $id; -- สินค้า id $id\n";
}
echo "</pre>";

?>

<script>
function updateImage(productId) {
    const select = document.getElementById('file_' + productId);
    const fileName = select.value;
    
    if (!fileName) {
        alert('กรุณาเลือกไฟล์รูป');
        return;
    }
    
    if (confirm('อัปเดตรูปสินค้า ID ' + productId + ' เป็น ' + fileName + '?')) {
        fetch('update_product_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&image=' + encodeURIComponent(fileName)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('อัปเดตสำเร็จ!');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.error);
            }
        });
    }
}
</script>
<?php $conn->close(); ?>