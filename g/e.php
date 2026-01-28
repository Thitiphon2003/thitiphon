<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>ฐิติพล มหานาม (บอส)</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .chart-container { display: flex; gap: 20px; width: 80%; margin: 20px auto; }
    .chart-box { flex: 1; min-width: 300px; }
    table { width: 50%; margin: 20px auto; border-collapse: collapse; }
    h1 { text-align: center; }
</style>
</head>

<body>
<h1> ฐิติพล มหานาม (บอส) <br>66010914140 </h1>


<table border='1' align="center">
    <tr>
        <th>ประเทศ</th>
        <th>ยอดขาย</th>
    </tr>



<?php
include_once("connectdb.php");
$sql = "SELECT `p_country`, SUM(`p_amount`) AS total FROM `popsupermarket` GROUP BY `p_country`";
$rs = mysqli_query($conn, $sql);

$countries = [];
$totals = [];

while ($data = mysqli_fetch_array($rs)) {
    // เก็บข้อมูลลง Array เพื่อใช้ใน JavaScript
    $countries[] = $data['p_country'];
    $totals[] = $data['total'];
?>
    <tr>
        <td><?php echo $data['p_country']; ?></td>
        <td align='right'><?php echo number_format($data['total'], 0); ?></td>
    </tr>
<?php } ?>
</table>
<div class="chart-container">
    <div class="chart-box"><canvas id="myBarChart"></canvas></div>
    <div class="chart-box"><canvas id="myPieChart"></canvas></div>
</div>

<script>
    const labels = <?php echo json_encode($countries); ?>;
    const dataValues = <?php echo json_encode($totals); ?>;

    const chartData = {
        labels: labels,
        datasets: [{
            label: 'ยอดขายรายประเทศ',
            data: dataValues,
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ],
            borderWidth: 1
        }]
    };

    // สร้างกราฟแท่ง (Bar Chart)
    new Chart(document.getElementById('myBarChart'), {
        type: 'bar',
        data: chartData,
        options: { plugins: { title: { display: true, text: 'ยอดขาย (Bar Chart)' } } }
    });

    // สร้างกราฟวงกลม (Pie Chart)
    new Chart(document.getElementById('myPieChart'), {
        type: 'pie',
        data: chartData,
        options: { plugins: { title: { display: true, text: 'สัดส่วนยอดขาย (Pie Chart)' } } }
    });
</script>

</body>
</html>