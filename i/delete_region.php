<meta charset="utf-8">
<?php
include_once("connectdb.php");

$id = $_GET["id"];
$sql = "DELETE FROM regions WHERE r_id='$id'";
mysqli_query($conn,$sql) or die("ลบข้อมูลไม่ได้");

echo "<script>";
echo "window.location='a.php';";
echo "</script>";


?>
