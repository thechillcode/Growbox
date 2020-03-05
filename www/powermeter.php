<!DOCTYPE html>

<!-- database query -->
<?php

function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

$nav = array(
		array("Home", "growbox.php"),
		array("PowerMeter", "powermeter.php"),
		);
$title = "Power (W) - " . date('Y-m-d H:i:s');
$label = 'Power (W)';

// days
$d = 3;
if (isset($_GET['d']))
{
	$d = in_range(intval($_GET['d']), 1, 28, 3);
}

$db = new SQLite3('/home/pi/DB/Growbox.db', SQLITE3_OPEN_READONLY);

// get socket names
$num_sockets = 8;
$sockets = $db->query('SELECT rowid,active,name FROM Sockets');

// last entry
$loads = $db->query('SELECT * FROM PowerMeter WHERE datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY dt ASC');

$totalp = 0;

// since beginning
$totalpower = $db->querySingle('Select  SUM(l1)+SUM(l2)+SUM(l3)+SUM(l4)+SUM(l5)+SUM(l6)+SUM(l7)+SUM(l8) total from PowerMeter');
// in kWh
$totalpower /= 6000;
$day1 = $db->querySingle('Select dt from PowerMeter Limit 1');
/*
$colors = array("rgb(255, 99, 132)", "rgb(54, 162, 235)", "rgb(255, 205, 86)", "rgb(201, 203, 207)",);
$colors = array("rgb(231, 76, 60)", "rgb(69, 179, 157)", "rgb(241, 196, 15)", "rgb(41, 128, 185)"
	,"rgb(186, 74, 0)","rgb(142, 68, 173)","rgb(149, 165, 166)","rgb(23, 32, 42)");
*/

$colors = array(
	"#FF6666",
	"#FFB266",
	"#B2FF66",
	"#66B2FF",
	"#B266FF",
	"#C0C0C0",
	"#FFFF66",
	"#66FF66",
	"#66FFFF",
	"#6666FF",
	"#FF66FF",

	"#66FFB2",
	"#FF66B2");
?>

<html lang="en">
<head>

<script src="script/chart.js"></script>

<title>Growbox - Powermeter</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="greenstyle.css">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<style>
</style>
</head>
<body>

<?php
?>

<?php include 'header.php';?>

<hr>

<form action="<?php $_PHP_SELF ?>" method="get">
	<select name="d" onchange="this.form.submit()">
		<?php 
			$days = array(1,3,7,21,28);
			foreach ($days as &$num) {
				if ($num == $d) { echo "<option value=\"$num\" selected>"; }
				else { echo "<option value=\"$num\">"; }
				echo "$num days</option>";
			} 
		?>
	</select>
</form>
<br>

<canvas id="chart1" class="chartjs" width="200" height="120" style="display: block; width: 200px; height: 120px;"></canvas>

<script>
new Chart(document.getElementById('chart1').getContext('2d'), {
	type: 'line',
	data: {
		labels: [
			<?php
				while ($l = $loads->fetchArray(SQLITE3_NUM))
				{
					echo "\"$l[0]\",";
				}
			?>
		],
		datasets: [

			<?php for ($i=0; $i<$num_sockets; $i++) {
				$s = $sockets->fetchArray(SQLITE3_NUM);
				$rowid = $s[0];
				$active = $s[1];
				$name = $s[2];
				if ($active == 1) {
				?>
			{
			label: '<?php echo $name; ?>',
			steppedLine: true,
			backgroundColor: '<?php echo $colors[$i]; ?>',
			borderColor: '<?php echo $colors[$i]; ?>',
			fill:false,
			lineTension:0.1,			
			data: [
			<?php
				while ($l = $loads->fetchArray(SQLITE3_NUM))
				{
					if ($l[$rowid] > 0) { $totalp += $l[$rowid]/6; }
					echo "$l[$rowid],";
				}
			?>
			],},
			
			<?php }} ?>			
			
			]},
	options: {
			
		tooltips: {
			mode: 'index',
			intersect: false
		},
		responsive: true,
		scales: {
			xAxes: [{
				stacked: true,
			}],
			yAxes: [{
				stacked: true
			}]
		}
			
		}});
			
			
<?php
	$db->close();
	$totalp/=1000;
?>
</script>

<hr>

<table>
	<tr>
		<th colspan="2">Power Consumption</th>
	</tr>
	<tr>
		<td><?php echo number_format(($totalp/$d), 2, ",", ".")?> kWh</td>
		<td>(1 day average)</td>
	</tr>
	<tr>
		<td><?php echo number_format($totalp,2,",","."); ?> kWh</td>
		<td>(<?php echo $d; ?> days)</td>
	</tr>
	<tr>
		<td><?php echo number_format($totalpower,2,",","."); ?> kWh</td>
		<td>(since <?php echo substr($day1,0,-6); ?>)</td>
	</tr>

</table>

<?php include 'footer.php';?>

</body>
</html>
