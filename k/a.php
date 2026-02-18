<!DOCTYPE html>
<html>
<head>
    <title>ปุ่มแสดงรูป</title>
</head>
<body>

<button onclick="showImage('1.jpg', this)" 
        style="background-color:green; color:white; padding:10px; border:none;">
    เปิดรูปที่ 1
</button>

<button onclick="showImage('2.jpg', this)" 
        style="background-color:orange; color:white; padding:10px; border:none;">
    เปิดรูปที่ 2
</button>

<script>
function showImage(imgSrc, btn){
    btn.innerHTML = "<img src='" + imgSrc + "' width='150'>";
}
</script>

</body>
</html>
