<!DOCTYPE html>

<!-- database query -->
<?php

$db = new SQLite3('/home/pi/DB/Growbox.db', SQLITE3_OPEN_READONLY);

function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

// days
$d = 7;
if (isset($_GET['d']))
{
	$d = in_range(intval($_GET['d']), 1, 28, 7);
}

$sensor = "air";
$lines = 2;
$id = 1;
$min_1 = 100000;
$max_1 = -100000;
$min_2 = 100000;
$max_2 = -100000;

$nav = array(
		array("Home", "growbox.php"),
		array("AirSensor", "sensor.php?sensor=air&id=1"),
		);

$title = date('Y-m-d H:i:s');
$label = 'Humidity (%)';
$label2 = 'Temperature (°C)';
//$color1 = 'rgb(75, 192, 192)';
//$color1 = 'rgb(0, 0, 255)'; //blue
//$color1 = 'rgb(30,144,255)'; //dodger blue
$color1 = '#66B2FF';
//$color2 = 'rgb(139, 0, 0)';
//$color2 = 'rgb(200, 0, 0)';
//$color2 = 'rgb(255, 0, 0)'; //red
//$color2 = 'rgb(255,69,0)'; //orange red
$color2 = '#FFB266';
$page_title = "Growbox - Air Sensor";

$db_table = "";
$query = "";
#$db_table = "AirSensorData";
#$query = 'SELECT dt,temperature,humidity FROM ' . $db_table . ' WHERE id='.$id.' AND datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY dt ASC';


if (isset($_GET['sensor']) && isset($_GET['id']))
{
	switch ($_GET['sensor']) {
		case "air":
			$sensor = "air";
			$lines = 2;
			$id = in_range(intval($_GET['id']), 1, 4, 1);
			$nav = array(
				array("Home", "growbox.php"),
				array("AirSensor", "sensor.php?sensor=air&id=" . $id),
				);
			// get name
			$name = $db->querySingle('SELECT name FROM AirSensors WHERE rowid='. $id);
			$title = $name . ": " . date('Y-m-d H:i:s');
			$label = "Humidity (%)";
			$label2 = "Temperature (°C)";
			$page_title = "Growbox - Air Sensor - " . $name;

			$db_table = "AirSensorData";
			#$query = 'SELECT dt,humidity,temperature FROM ' . $db_table . ' WHERE id='.$id.' AND datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY dt ASC';
			#$query = "SELECT humidity, temperature, dt,".
			$query = "SELECT humidity, temperature, strftime('%Y-%m-%d', datetime(dt, 'localtime')), strftime('%H:%M', datetime(dt, 'localtime')),".
				"case cast(strftime('%w',datetime(dt, 'localtime')) as integer)".
					" when 0 then 'Sun'".
					" when 1 then 'Mon'".
					" when 2 then 'Tue'".
					" when 3 then 'Wed'".
					" when 4 then 'Thu'".
					" when 5 then 'Fri'".
					" when 6 then 'Sat' end".
					" FROM " . $db_table . " WHERE id=".$id." AND datetime(dt, 'localtime') > datetime('now','-" . ($d) . " days') ORDER BY dt ASC";					
			break;
			
		case "weight":
			$sensor = "weight";
			$lines = 1;
			$id = in_range(intval($_GET['id']), 1, 4, 1);
			$nav = array(
				array("Home", "growbox.php"),
				array("WeightSensor", "sensor.php?sensor=weight&id=" . $id),
				);
			$name = $db->querySingle('SELECT name FROM WeightSensors WHERE rowid='. $id);
			$title = $name . ": " . date('Y-m-d H:i:s');
			$label = "Weight (g)";
			$page_title = "Growbox - Weight Sensor - " . $name;
			$db_table = "WeightSensorData";
			#$query = 'SELECT dt,weight FROM ' . $db_table . ' WHERE id='.$id.' AND datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY dt ASC';
			$query = "SELECT weight, NULL, strftime('%Y-%m-%d', datetime(dt, 'localtime')), strftime('%H:%M', datetime(dt, 'localtime')),".
				"case cast(strftime('%w',datetime(dt, 'localtime')) as integer)".
					" when 0 then 'Sun'".
					" when 1 then 'Mon'".
					" when 2 then 'Tue'".
					" when 3 then 'Wed'".
					" when 4 then 'Thu'".
					" when 5 then 'Fri'".
					" when 6 then 'Sat' end".
					" FROM " . $db_table . " WHERE id=".$id." AND datetime(dt, 'localtime') > datetime('now','-" . ($d) . " days') ORDER BY dt ASC";					
			break;
	}
}

$results = $db->query($query);

?>

<html lang="en">
<head>

<script src="script/chart.js"></script>

<title><?php echo $page_title; ?></title>
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
	<input name="sensor" type="hidden" value="<?php echo $sensor; ?>">
	<input name="id" type="hidden" value="<?php echo $id; ?>">
	<select name="d" onchange="this.form.submit()">
		<?php 
			$days = array(1,3,7,14,28);
			$dayslabel = array('1 day','3 days','1 week','2 weeks','4 weeks');
			echo $days[0], $dayslabel[0];
			for ($i=0; $i<count($days); $i++) {
				$num = $days[$i];
				$dlabel = $dayslabel[$i];
				?>
				
				<option value="<?= $num ?>" <?php if ($num == $d) { echo "selected"; } ?>><?= $dlabel ?></option>
			<?php } ?>
	</select>
</form>
<br>

<div style="width:100%;">
	<canvas id="canvas"></canvas>
</div>

<script>
		var lineChartData = {
			labels: [
			<?php
				$i = 0;
				while ($result = $results->fetchArray(SQLITE3_NUM))
				{
					echo "[\"$result[4] $result[2]\",\"$result[3]\"],";
				}
			?>
			],
			datasets: [
				{
					label: '<?php echo $label; ?>',
					borderColor: '<?php echo $color1; ?>',
					backgroundColor: '<?php echo $color1; ?>',
					fill: false,
					data: [
					<?php
						$i = 0; //a counter to track which element we are at
						while ($result = $results->fetchArray(SQLITE3_NUM))
						{
							
							if ($result[0] < $min_1) { $min_1 = $result[0]; }
							if ($result[0] > $max_1) { $max_1 = $result[0]; }
							echo "$result[0],";
						}
					?>
					],
					yAxisID: 'y-axis-1',
				}
				<?php if ($lines==2) { ?>
				,{
					label: '<?php echo $label2; ?>',
					borderColor: '<?php echo $color2; ?>',
					backgroundColor: '<?php echo $color2; ?>',
					fill: false,
					data: [
					<?php
						$i = 0; //a counter to track which element we are at
						while ($result = $results->fetchArray(SQLITE3_NUM))
						{
							if ($result[1] < $min_2) { $min_2 = $result[1]; }
							if ($result[1] > $max_2) { $max_2 = $result[1]; }
							echo "$result[1],";
						}
					?>
					],
					yAxisID: 'y-axis-2'
				}
				<?php } ?>
			]
		};

		window.onload = function() {
			var ctx = document.getElementById('canvas').getContext('2d');
			window.myLine = Chart.Line(ctx, {
				data: lineChartData,
				options: {
					responsive: true,
					hoverMode: 'index',
					stacked: false,
					title: {
						display: false,
						text: '<?php echo $title; ?>'
					},
					scales: {
						yAxes: [
							{
								type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
								display: true,
								position: 'left',
								id: 'y-axis-1',
							}
							<?php if ($lines==2) { ?>
							,{
								type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
								display: true,
								position: 'right',
								id: 'y-axis-2',

								// grid line settings
								gridLines: {
									drawOnChartArea: false, // only want the grid lines for one axis to show up
								},
							}
							<?php } ?>
						],
					}
				}
			});
		};

</script>

<?php
	$db->close();
?>

<hr>

<div class="block">

<?php if ($sensor == 'air') { ?>
	<div class="tile">
		<table>
			<tr>
				<th colspan="2">Humidity</th>
			</tr>
			<tr>
				<td>Min:</td><td><?php echo $min_1; ?>&thinsp;%</td>
			</tr>
				<td>Max:</td><td><?php echo $max_1; ?>&thinsp;%</td>
			</tr>
		</table>
	</div>
	<div class="tile">
		<table>
			<tr>
				<th colspan="2">Temperature</th>
			</tr>
			<tr>
				<td>Min:</td><td><?php echo $min_2; ?>&thinsp;°C</td>
			</tr>
				<td>Max:</td><td><?php echo $max_2; ?>&thinsp;°C</td>
			</tr>
		</table>
	</div>
<?php } ?>
<?php if ($sensor == 'weight') { ?>
	<div class="tile">
		<table>
			<tr>
				<th colspan="2">Weight</th>
			</tr>
			<tr>
				<td>Min:</td><td><?php echo $min_1; ?>&thinsp;g</td>
			</tr>
				<td>Max:</td><td><?php echo $max_1; ?>&thinsp;g</td>
			</tr>
		</table>
	</div>
<?php } ?>

</div>

<hr>

<?php include 'footer.php';?>

</body>
</html>
