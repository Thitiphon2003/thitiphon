<?php
require_once 'db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>ตรวจสอบรูปภาพ</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: 'Inter', sans-serif; padding: 20px; background: #f1f5f9; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #f8fafc; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 ตรวจสอบรูปภาพสินค้า</h1>";

// ดึงข้อมูลสินค้าทั้งหมด
$products = fetchAll("SELECT id, name, image FROM products ORDER BY id");

echo "<div class='card'>";
echo "<h2>📦 สินค้าทั้งหมด (" . count($products) . " รายการ)</h2>";
echo "<table>";
echo "<tr>
        <th>ID</th>
        <th>ชื่อสินค้า</th>
        <th>ชื่อไฟล์ใน DB</th>
        <th>รูปภาพ</th>
        <th>สถานะไฟล์</th>
      </tr>";

foreach ($products as $product) {
    echo "<tr>";
    echo "<td>" . $product['id'] . "</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . ($product['image'] ?: '-') . "</td>";
    echo "<td>";
    
    if (!empty($product['image'])) {
        $image_path = "uploads/products/" . $product['image'];
        if (file_exists($image_path)) {
            echo "<img src='$image_path' alt=''>";
            echo "<br><small class='success'>✅ มีไฟล์</small>";
        } else {
            echo "<div style='width:60px;height:60px;background:#fee2e2;display:flex;align-items:center;justify-content:center;'>❌</div>";
            echo "<br><small class='error'>❌ ไม่พบไฟล์</small>";
        }
    } else {
        echo "<div style='width:60px;height:60px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;'>-</div>";
    }
    
    echo "</td>";
    echo "<td>";
    
    if (!empty($product['image'])) {
        $image_path = "uploads/products/" . $product['image'];
        if (file_exists($image_path)) {
            echo "<span class='success'>✅ ไฟล์อยู่</span>";
        } else {
            echo "<span class='error'>❌ ไม่พบไฟล์</span>";
        }
    } else {
        echo "<span class='error'>❌ ไม่มีชื่อไฟล์</span>";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// ตรวจสอบไฟล์ในโฟลเดอร์
$files = glob("uploads/products/*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
echo "<div class='card'>";
echo "<h2>📁 ไฟล์ในโฟลเดอร์ uploads/products/ (" . count($files) . " ไฟล์)</h2>";
echo "<ul style='columns: 3;'>";
foreach ($files as $file) {
    $filename = basename($file);
    echo "<li>" . $filename . "</li>";
}
echo "</ul>";
echo "</div>";

echo "</div>
</body>
</html>";
?>