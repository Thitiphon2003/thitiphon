<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die('กรุณาเข้าสู่ระบบ');
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>ทดสอบการลบสินค้า</title>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', sans-serif; padding: 20px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 8px; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 8px; }
        button { padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; margin: 5px; }
        pre { background: #1e293b; color: white; padding: 10px; border-radius: 8px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 ทดสอบการลบสินค้าจากตะกร้า</h1>
        
        <div class="card">
            <h3>📋 สินค้าในตะกร้าของคุณ</h3>
            <?php
            $cart_items = fetchAll("SELECT ci.*, p.name, p.price 
                                   FROM cart_items ci 
                                   JOIN products p ON ci.product_id = p.id 
                                   WHERE ci.user_id = ?", [$user_id]);
            
            if (empty($cart_items)) {
                echo "<p>ไม่มีสินค้าในตะกร้า</p>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>ชื่อสินค้า</th><th>จำนวน</th><th>ราคา</th><th>ทดสอบ</th></tr>";
                foreach ($cart_items as $item) {
                    echo "<tr>";
                    echo "<td>{$item['product_id']}</td>";
                    echo "<td>{$item['name']}</td>";
                    echo "<td>{$item['quantity']}</td>";
                    echo "<td>฿" . number_format($item['price']) . "</td>";
                    echo "<td><button onclick='testDelete({$item['product_id']})'>ทดสอบลบ</button></td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            ?>
        </div>
        
        <div class="card">
            <h3>🔍 ทดสอบส่ง Request ลบ</h3>
            <form id="deleteForm">
                <div style="margin-bottom: 10px;">
                    <label>Product ID:</label>
                    <input type="number" name="product_id" required>
                </div>
                <button type="submit">ทดสอบลบ</button>
            </form>
            <div id="result" style="margin-top: 20px;"></div>
        </div>
        
        <div class="card">
            <h3>📊 ตรวจสอบ Database โดยตรง</h3>
            <button onclick="checkDatabase()">ดูข้อมูลในฐานข้อมูล</button>
            <div id="dbResult" style="margin-top: 20px;"></div>
        </div>
    </div>
    
    <script>
    document.getElementById('deleteForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const productId = document.querySelector('input[name="product_id"]').value;
        
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>กำลังส่ง request...</p>';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove_item&product_id=' + productId
            });
            
            // ดู raw response
            const text = await response.text();
            
            resultDiv.innerHTML = `
                <h4>Status: ${response.status}</h4>
                <h4>Headers:</h4>
                <pre>${JSON.stringify(Object.fromEntries(response.headers), null, 2)}</pre>
                <h4>Raw Response:</h4>
                <pre>${text}</pre>
            `;
            
            // ถ้าเป็น JSON ให้ parse แสดง
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML += `
                    <h4>Parsed JSON:</h4>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (e) {
                // ไม่ใช่ JSON
            }
            
        } catch (error) {
            resultDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
        }
    });
    
    async function testDelete(productId) {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=remove_item&product_id=' + productId
        });
        
        const data = await response.json();
        alert(JSON.stringify(data, null, 2));
        location.reload();
    }
    
    async function checkDatabase() {
        const response = await fetch('get_cart_count.php');
        const data = await response.json();
        
        document.getElementById('dbResult').innerHTML = `
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    }
    </script>
</body>
</html>