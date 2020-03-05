<!DOCTYPE html>

<!-- database query -->
<?php

$db = new SQLite3('/home/pi/DB/Growbox.db', SQLITE3_OPEN_READONLY);

function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

// days
$d = 3;
if (isset($_GET['d']))
{
	$d = in_range(intval($_GET['d']), 1, 28, 3);
}

$sensor = "air";
$lines = 2;
$id = 1;

$nav = array(
		array("Home", "growbox.php"),
		array("AirSensor", "sensor.php?sensor=air&id=1"),
		);

$title = "Humidity (%), Temperature (&deg;C) - " . date('Y-m-d H:i:s');
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
			$title = $name . ": Humidity (%), Temperature (&deg;C) - " . date('Y-m-d H:i:s');
			$label = "Humidity (%)";
			$label2 = "Temperature (°C)";
			$page_title = "Growbox - Air Sensor - " . $name;

			$db_table = "AirSensorData";
			#$query = 'SELECT dt,humidity,temperature FROM ' . $db_table . ' WHERE id='.$id.' AND datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY dt ASC';
			$query = "SELECT dt,humidity,temperature,".
				"case cast(strftime('%w',dt) as integer)".
					" when 0 then 'Sun'".
					" when 1 then 'Mon'".
					" when 2 then 'Tue'".
					" when 3 then 'Wed'".
					" when 4 then 'Thu'".
					" when 5 then 'Fri'".
					" when 6 then 'Sat' end".
					" FROM " . $db_table . " WHERE id=".$id." AND datetime(dt) > datetime('now','-" . ($d) . " days') ORDER BY dt ASC";					
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
			$title = $name . ": Weight (g) - " . date('Y-m-d H:i:s');
			$label = "Weight (g)";
			$page_title = "Growbox - Weight Sensor - " . $name;
			$db_table = "WeightSensorData";
			#$query = 'SELECT dt,weight FROM ' . $db_table . ' WHERE id='.$id.' AND datetime(dt) > datetime(\'now\',\'-' . ($d) . ' days\') ORDER BY dt ASC';
			$query = "SELECT dt,weight,NULL,".
				"case cast(strftime('%w',dt) as integer)".
					" when 0 then 'Sun'".
					" when 1 then 'Mon'".
					" when 2 then 'Tue'".
					" when 3 then 'Wed'".
					" when 4 then 'Thu'".
					" when 5 then 'Fri'".
					" when 6 then 'Sat' end".
					" FROM " . $db_table . " WHERE id=".$id." AND datetime(dt) > datetime('now','-" . ($d) . " days') ORDER BY dt ASC";					
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
			for ($i=0; $i<count($days); $i++) {
				if ($days[$i] == $d) { echo "<option value=\"$days[$i]\" selected>"; }
				else { echo "<option value=\"$days[$i]\">"; }
				echo "$dayslabel[$i]</option>";
			} 
		?>
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
				while ($result = $results->fetchArray(SQLITE3_NUM))
				{
					echo "\"$result[3] $result[0]\",";
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
							echo "$result[1],";
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
							echo "$result[2],";
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

<?php include 'footer.php';?>

</body>
</html>
