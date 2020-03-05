<!DOCTYPE html>

<?php

function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

$id = 0;
if (isset($_GET['id']))
{
	$id = in_range(intval($_GET['id']), 1, 8, 0);
}

// days
$d = 21;
if (isset($_GET['d']))
{
	$d = in_range(intval($_GET['d']), 1, 28, 3);
}

// Connect DB
$db = new SQLite3('/home/pi/DB/Growbox.db', SQLITE3_OPEN_READONLY);
$pumps = array();
$name = "";
// get distinct pumps
$query = $db->query('SELECT DISTINCT Water.id,Sockets.name FROM Water LEFT JOIN Sockets ON Water.id = Sockets.rowid ORDER BY name ASC');
while ($pmp = $query->fetchArray(SQLITE3_NUM)) {
	if ($id==0) {
		$id = $pmp[0];
	}
	if ($id == $pmp[0]) {
		$name = $pmp[1];
	}
	$pumps[] = array($pmp[0], $pmp[1]);
}

$nav = array(
		array("Home", "growbox.php"),
		array("Water", "water.php?id=".$id),
		);
$title = $name." - Water (ml) - " . date('Y-m-d H:i:s');
$label = 'Water (ml)';

$totalml = 0;



// last entry
//$waters = $db->query('SELECT dt,ml FROM Water WHERE datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY rowid ASC');
$waters = $db->query('SELECT dt,ml FROM Water WHERE id='. $id . ' AND datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY dt ASC');

$s_dates = "";
$s_ml = "";
while ($water = $waters->fetchArray(SQLITE3_NUM)) {
	$s_dates = $s_dates . "\"$water[0]\",";
	$s_ml = $s_ml . $water[1] . ",";
	
	$totalml += $water[1];
}

?>

<html lang="en">
<head>

<script src="script/chart.js"></script>

<title>Growbox - Water</title>
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

<table>

<tr>
<td>
<form action="<?php $_PHP_SELF ?>" method="get">
	<input name="id" type="hidden" value="<?php echo $id; ?>">
	<select name="d" onchange="this.form.submit()">
		<?php 
			$days = array(7,14,21,28);
			foreach ($days as &$num) {
				if ($num == $d) { echo "<option value=\"$num\" selected>"; }
				else { echo "<option value=\"$num\">"; }
				echo "$num days</option>";
			} 
		?>
	</select>
</form>
</td>
<td>
<form action="<?php $_PHP_SELF ?>" method="get">
	<select name="id" onchange="this.form.submit()">
		<?php
			for ($i = 0; $i < count($pumps); $i++) {
				if ($pumps[$i][0] == $id) { echo "<option value=\"".$id."\" selected>"; }
				else { echo "<option value=\"".$pumps[$i][0]."\">"; }
				echo $pumps[$i][1]."</option>";
			} 
		?>
	</select>
</form>
</td>
</tr>
</table>

<br>

<canvas id="chart1" class="chartjs" width="200" height="120" style="display: block; width: 200px; height: 120px;"></canvas>

<script>
new Chart(document.getElementById('chart1').getContext('2d'), {
	type: 'bar',
	data: {
		labels: [
			<?php
				echo $s_dates;
				//while ($water = $waters->fetchArray(SQLITE3_NUM)) {
				//	echo "\"$water[0]\",";
				//}
			?>
		],
		datasets: [
			{
			label: 'Water (ml)',
			steppedLine: true,
			backgroundColor: 'rgb(0, 96, 255)',
			borderColor: 'rgb(0, 96, 255)',
			fill:false,
			lineTension:0.1,			
			data: [
			<?php
				echo $s_ml;
				//while ($water = $waters->fetchArray(SQLITE3_NUM)) {
				//	echo "$water[1],";
				//}
			?>
			],},			
			
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
?>
</script>

<hr>

<table>
	<tr>
		<th colspan="2">Water Consumption</th>
	</tr>
	<tr>
		<td><?php echo number_format(($totalml/$d/1000), 2, ",", "."); ?> l</td>
		<td>(1 day average)</td>
	</tr>
	<tr>
		<td><?php echo number_format($totalml/1000,2,",","."); ?> l</td>
		<td>(<?php echo $d; ?> days)</td>
	</tr>
</table>

<hr>

<?php include 'footer.php'; ?>

</body>
</html>
