<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>ฐิติพล มหานาม (บอส) </title>


</head> 

<body>
<h1> งาน i ฐิติพล มหานาม (บอส) <br>66010914140 </h1>
<form method="post" actions="">
    ชื่อภาค <input type="text" name="rname" autofocus required>
    <button type="submit" name="Submit">บันทึก</button>
</form>

<?php 
if(isset($_POST["Submit"])) {
    include_once("connectdb.php");
    $rname = $_POST["rname"];
    $sql2 = "INSERT INTO `regions` (`r_id`, `r_name`) VALUES (NULL, '{$rname}');";
    mysqli_query($conn, $sql2) or die("เพิ่มข้อมูลไม่ได้");
}
?>

<?php
include_once("connectdb.php");
$sql = "SELECT * FROM `regions`";
$rs = mysqli_query($conn,$sql);
while ($data = mysqli_fetch_array($rs))
?>

<table border="1">
    <tr>
        <th>รหัสภาค</th>
        <th>ชื่อภาค</th>
    </tr>
<?php 

while ($data = mysqli_fetch_array($rs)){
    ?>
    <tr>
        <td><?php echo $data['r_id'];?></td>
        <td><?php echo $data['r_name'];?></td>
        <td width="80" align="center"><a href="delete_region.php?id=<?php echo $data['r_id'];?>" onClick="return confirm('ยืนยันการลบไหม?');"><img src="image/delete.png" width="20"></a></td>
    </tr>
    <?php } ?>
</table>
<?php
mysqli_close($conn);
?>
</body>
</html>