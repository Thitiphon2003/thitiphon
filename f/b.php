<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>ฐิติพล มหานาม (บอส) 66010914140</title>
<style>
		h1{
			text-color:#FFF
            font-size: 50px;
            margin-bottom: 0.5rem;
			text-align:center;
        }
</style>
</head>

<body>
<h1> ฐิติพล มหานาม (บอส) 66010914140 </h1>

        <form method="post" action="">
            กรอกตัวเลข <input type="nember" name="a" autofocus required>
            <button type="submit" name="Submit">OK</button>
        </form>
<hr>
<?php
    if(isset($_POST["Submit"])) {
        $gender=$_POST['a'];
        if($gender==1){
            echo "เพศชาย";
        }
        else if($gender== 2){
            echo "เพศหญิง";
        }
        else if($gender== 3){
            echo "เพศทางเลือก";
        
        }
        else {
            echo "อื่น";
        }
    }
?>
</body>
</html>