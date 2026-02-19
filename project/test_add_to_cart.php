<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    die('กรุณาเข้าสู่ระบบก่อนทดสอบ');
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>ทดสอบเพิ่มสินค้าลงตะกร้า</title>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', sans-serif; padding: 20px; background: #f8fafc; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 8px; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 8px; }
        button { padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; }
        pre { background: #1e293b; color: white; padding: 10px; border-radius: 8px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 ทดสอบระบบเพิ่มสินค้าลงตะกร้า</h1>
        
        <div class="card">
            <h3>ข้อมูลผู้ใช้</h3>
            <p>User ID: <strong><?php echo $user_id; ?></strong></p>
        </div>
        
        <div class="card">
            <h3>ทดสอบส่ง Request</h3>
            <form id="testForm">
                <div style="margin-bottom: 10px;">
                    <label>Product ID:</label>
                    <input type="number" name="product_id" value="1" required>
                </div>
                <div style="margin-bottom: 10px;">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" value="1" min="1" required>
                </div>
                <button type="submit">ทดสอบเพิ่มสินค้า</button>
            </form>
            <div id="result" style="margin-top: 20px;"></div>
        </div>
        
        <div class="card">
            <h3>ตรวจสอบตะกร้าสินค้า</h3>
            <button onclick="checkCart()">ดูตะกร้าสินค้า</button>
            <div id="cartResult" style="margin-top: 20px;"></div>
        </div>
    </div>
    
    <script>
    document.getElementById('testForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const productId = formData.get('product_id');
        const quantity = formData.get('quantity');
        
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>กำลังส่ง request...</p>';
        
        try {
            const response = await fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            });
            
            // ดู raw response
            const text = await response.text();
            
            resultDiv.innerHTML = `
                <h4>Response Headers:</h4>
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
    
    async function checkCart() {
        const response = await fetch('get_cart_count.php');
        const data = await response.json();
        
        document.getElementById('cartResult').innerHTML = `
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    }
    </script>
</body>
</html>