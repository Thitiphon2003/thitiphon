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
            กรอกคะแนน <input type="number" min="0" max="100" name="s" autofocus required>
            <button type="submit" name="Submit">OK</button>
        </form>
<hr>
<?php
    if(isset($_POST["Submit"])) {
    $score=$_POST['s'];
        if($score>= 80) {
            $grade= 'A';
        } 
        else if($score== 70) {
            $grade= 'B';
        }
        else if($score== 60) {
            $grade= 'C';
        }
        else if($score== 50) {
            $grade= 'D';
        }
        else {
            $grade= 'F';
        }
    echo "คะแนน $score เท่ากับ $grade";
    }
?>
</body>
</html>