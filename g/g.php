<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>ฐิติพล มหานาม (บอส)</title>
<!-- เพิ่ม Chart.js และสไตล์พื้นฐาน -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 20px;
        background-color: #f7f9fc;
        color: #333;
    }
    h1 {
        color: #2c3e50;
        border-bottom: 3px solid #3498db;
        padding-bottom: 10px;
        text-align: center;
    }
    .container {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        justify-content: center;
        margin-top: 30px;
    }
    .chart-container {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        flex: 1;
        min-width: 300px;
        max-width: 500px;
    }
    .table-container {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        flex: 1;
        min-width: 300px;
        max-width: 600px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    th {
        background-color: #3498db;
        color: white;
        padding: 12px;
        text-align: center;
    }
    td {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        text-align: center;
    }
    tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    tr:hover {
        background-color: #e8f4fc;
    }
    .chart-title {
        text-align: center;
        font-weight: bold;
        margin-bottom: 15px;
        color: #2c3e50;
        font-size: 1.2em;
    }
    .student-info {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.1em;
        color: #7f8c8d;
    }
    .total-summary {
        background-color: #2ecc71;
        color: white;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        text-align: center;
        font-size: 1.3em;
        font-weight: bold;
    }
</style>
</head>

<body>
<h1>ฐิติพล มหานาม (บอส)</h1>
<div class="student-info">รหัสนักศึกษา: 66010914140</div>

<div class="container">
    <!-- ตารางข้อมูล -->
    <div class="table-container">
        <div class="chart-title">ตารางยอดขายรายเดือน</div>
        <table border='1'>
            <tr>
                <th>เดือน</th>
                <th>ยอดขาย (บาท)</th>
            </tr>

            <?php
            include_once("connectdb.php");
            $sql = "SELECT 
                    MONTH(p_date) AS Month, 
                    SUM(p_amount) AS Total_Sales
                    FROM popsupermarket
                    GROUP BY MONTH(p_date)
                    ORDER BY Month;";
            $rs = mysqli_query($conn, $sql);
            
            $monthNames = [
                1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 
                4 => "เมษายน", 5 => "พฤษภาคม", 6 => "มิถุนายน", 
                7 => "กรกฎาคม", 8 => "สิงหาคม", 9 => "กันยายน", 
                10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
            ];
            
            $dataForChart = [];
            $totalAllSales = 0;
            
            while ($data = mysqli_fetch_array($rs)) {
                $monthNumber = $data['Month'];
                $monthName = isset($monthNames[$monthNumber]) ? $monthNames[$monthNumber] : "เดือนที่ " . $monthNumber;
                $salesAmount = $data['Total_Sales'];
                $totalAllSales += $salesAmount;
                
                // เก็บข้อมูลสำหรับกราฟ
                $dataForChart[] = [
                    'monthNumber' => $monthNumber,
                    'monthName' => $monthName,
                    'sales' => $salesAmount
                ];
                
                // แสดงข้อมูลในตาราง
                echo "<tr>";
                echo "<td>" . $monthName . "</td>";
                echo "<td align='right'>" . number_format($salesAmount, 0) . "</td>";
                echo "</tr>";
            }
            
            // แสดงผลรวมทั้งหมด
            echo "<tr style='background-color: #e8f6f3; font-weight: bold;'>";
            echo "<td>รวมทั้งปี</td>";
            echo "<td align='right'>" . number_format($totalAllSales, 0) . "</td>";
            echo "</tr>";
            ?>
        </table>
        
        <div class="total-summary">
            ยอดขายรวมทั้งปี: <?php echo number_format($totalAllSales, 0); ?> บาท
        </div>
    </div>

    <!-- กราฟแท่ง -->
    <div class="chart-container">
        <div class="chart-title">กราฟแท่งแสดงยอดขายรายเดือน</div>
        <canvas id="barChart"></canvas>
    </div>
    
    <!-- กราฟโดนัท -->
    <div class="chart-container">
        <div class="chart-title">กราฟโดนัทแสดงสัดส่วนยอดขายรายเดือน</div>
        <canvas id="donutChart"></canvas>
    </div>
</div>

<script>
// ข้อมูลจาก PHP สำหรับกราฟ
const chartData = <?php echo json_encode($dataForChart); ?>;
const totalAllSales = <?php echo $totalAllSales; ?>;

// เตรียมข้อมูลสำหรับกราฟ
const monthLabels = chartData.map(item => item.monthName);
const salesData = chartData.map(item => item.sales);

// สร้างสีสุ่มสำหรับแต่ละเดือน
function generateColors(count) {
    const colors = [];
    for (let i = 0; i < count; i++) {
        const hue = (i * 360 / count) % 360;
        colors.push(`hsl(${hue}, 70%, 60%)`);
    }
    return colors;
}

const chartColors = generateColors(chartData.length);

// กราฟแท่ง
const barCtx = document.getElementById('barChart').getContext('2d');
const barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: salesData,
            backgroundColor: chartColors,
            borderColor: chartColors.map(color => color.replace('60%)', '40%)')),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.raw;
                        const percentage = ((value / totalAllSales) * 100).toFixed(1);
                        return `ยอดขาย: ${value.toLocaleString()} บาท (${percentage}%)`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'ยอดขาย (บาท)'
                },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'เดือน'
                }
            }
        }
    }
});

// กราฟโดนัท
const donutCtx = document.getElementById('donutChart').getContext('2d');
const donutChart = new Chart(donutCtx, {
    type: 'doughnut',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: salesData,
            backgroundColor: chartColors,
            borderColor: chartColors.map(color => color.replace('60%)', '40%)')),
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12,
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const percentage = context.parsed || 0;
                        return `${label}: ${value.toLocaleString()} บาท (${percentage.toFixed(1)}%)`;
                    }
                }
            }
        },
        cutout: '50%'
    }
});

// ปรับขนาดกราฟเมื่อหน้าต่างถูกปรับขนาด
window.addEventListener('resize', function() {
    barChart.resize();
    donutChart.resize();
});
</script>

</body>
</html>