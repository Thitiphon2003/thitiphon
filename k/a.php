<!DOCTYPE html>
<html>
<head>
<title>ฐิติพล มหานาม (บอส) </title>
</head>
<body>
<h1> งาน k ฐิติพล มหานาม (บอส) <br>66010914140 </h1>
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
