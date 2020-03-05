<!DOCTYPE html>

<!-- database query -->
<?php

$db_mode = SQLITE3_OPEN_READONLY;
if (isset($_REQUEST['id'])) {
	$db_mode = SQLITE3_OPEN_READWRITE;
}

$db = new SQLite3('/home/pi/DB/Growbox.db', $db_mode);

// Get Config
$config_db = $db->query('SELECT * FROM Config');
$grwconfig = array();
while ($conf = $config_db->fetchArray(SQLITE3_ASSOC))
{
	$grwconfig[$conf['name']] = $conf['val'];
}

// AirSensorData
$airsensors = array();
$airsensors_db = $db->query('SELECT rowid,* FROM AirSensors');
while ($result = $airsensors_db->fetchArray(SQLITE3_ASSOC))
{
	if ($result['gpio'] > 0) {
		$data = array("-", 0.0, 0.0);
		$query = $db->query('SELECT dt,temperature,humidity FROM AirSensorData WHERE id=' . $result['rowid'] . ' ORDER BY dt DESC LIMIT 1');
		$data = $query->fetchArray();
		$airsensors[] = array($result['rowid'], $result['name'], $data[0], $data[1], $data[2]);
	}
}

// WeightSensorData
$weightsensors = array();
$weightsensors_db = $db->query('SELECT rowid,name,data,clk FROM WeightSensors');
while ($result = $weightsensors_db->fetchArray(SQLITE3_ASSOC))
{
	if (($result['data'] > 0) && ($result['clk'] > 0)) {
		$data = array("-", 0);
		$query = $db->query('SELECT dt,weight FROM WeightSensorData WHERE id=' . $result['rowid'] . ' ORDER BY dt DESC LIMIT 1');
		$data = $query->fetchArray();
		$weightsensors[] = array($result['rowid'], $result['name'], $data[0], $data[1]);
	}
}

// Cameras
$cameras = array();
$cameras_t = $db->query('SELECT rowid,enabled,usb FROM Cameras');
while ($result = $cameras_t->fetchArray(SQLITE3_ASSOC))
{
	if ($result['enabled'] == 1) {
		$cameras[] = array($result['rowid'], $result['usb']);
	}
}

function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

// Lamp
if (isset($_REQUEST['id']))
{
	$rowid = in_range(intval($_REQUEST['id']), 1, 8, 1);
	$gpio = in_range(intval($_REQUEST['gpio']), 1, 30, 0);
	
	if (isset($_REQUEST['switch'])) {
		$state = in_range(intval($_REQUEST['switch']), 0, 1, 0);
		$db->exec('UPDATE Sockets SET State=' . $state . ' WHERE rowid='. $rowid);
		$vstate = ($state == 0) ? 1 : 0;
		exec('gpio -g write '. $gpio . ' ' . $vstate);
	}

	if (isset($_REQUEST['hon']) && isset($_REQUEST['hoff'])) {
		$hon = in_range(intval($_REQUEST['hon']), 0, 23, 0);
		$hoff = in_range(intval($_REQUEST['hoff']), 0, 23, 0);
		$db->exec('UPDATE Sockets SET HOn=' . $hon . ', HOff='. $hoff .' WHERE rowid='. $rowid);
		if ($rowid == $grwconfig['Light']) {
			$query = $db->exec('UPDATE Config SET val=' . $hon . ' WHERE name="LightOn"');
			$query = $db->exec("UPDATE Config SET val=" . $hoff . " WHERE name='LightOff'");
		}
	}
	
	if (isset($_REQUEST['power']) && isset($_REQUEST['pause'])) {
		$power = in_range(intval($_REQUEST['power']), 0, 300, 0);
		$pause = in_range(intval($_REQUEST['pause']), 0, 300, 0);
		$db->exec('UPDATE Sockets SET Power=' . $power . ', PowerCnt='. $power .', Pause='. $pause .' WHERE rowid='. $rowid);
	}
	
	if (isset($_REQUEST['tmax'])) {
		$tmax = in_range(intval($_REQUEST['tmax']), 0, 50, 0);
		$db->exec('UPDATE Sockets SET TMax=' . $tmax . ' WHERE rowid='. $rowid);
	}
	
	if (isset($_REQUEST['tmin'])) {
		$tmin = in_range(intval($_REQUEST['tmin']), 0, 50, 0);
		$db->exec('UPDATE Sockets SET TMin=' . $tmin . ' WHERE rowid='. $rowid);
	}

	if (isset($_REQUEST['hmax'])) {
		$hmax = in_range(intval($_REQUEST['hmax']), 0, 100, 0);
		$db->exec('UPDATE Sockets SET HMax=' . $hmax . ' WHERE rowid='. $rowid);
	}

	if (isset($_REQUEST['hmin'])) {
		$hmin = in_range(intval($_REQUEST['hmin']), 0, 100, 0);
		$db->exec('UPDATE Sockets SET HMin=' . $hmin . ' WHERE rowid='. $rowid);
	}
	
	if (isset($_REQUEST['thpower'])) {
		$thpower = in_range(intval($_REQUEST['thpower']), 0, 300, 0);
		$db->exec('UPDATE Sockets SET THPower=' . $thpower . ', THPowerCnt=0 WHERE rowid='. $rowid);
	}
	
	if (isset($_REQUEST['days']) && isset($_REQUEST['time']) && isset($_REQUEST['ml'])) {
		$days = in_range(intval($_REQUEST['days']), -1, 5, 0);
		$time = in_range(intval($_REQUEST['time']), 0, 23, 0);
		$ml = in_range(intval($_REQUEST['ml']), 0, 3000, 0);
		$dayscnt = $days;
		if ($days == -1) { $dayscnt = 0; }
		$db->exec('UPDATE Sockets SET DaysCnt=' . $dayscnt . ', Days=' . $days . ', Time='. $time .', MilliLiters='. $ml .' WHERE rowid='. $rowid);
	}
}

?>

<html lang="en">
<head>
<title>Growbox</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="greenstyle.css">
<link rel="stylesheet" type="text/css" href="slider.css">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<style>
</style>
</head>
<body>

<?php
	$nav = array(
		array("Home", "growbox.php"),
		);
		
	$title = "Growbox - Info - " . date('Y-m-d H:i:s');
?>

<?php include 'header.php';?>

<hr>

<!-- Info -->
<div class="block">
	<?php if (count($airsensors) > 0) { ?>
	<div class="tile">
		<table style="float: left">
			<tr>
				<th>Air:</th>
				<th>H(%):</th>
				<th>T(&deg;C):</th> 
				<th>Date:</th>
			</tr>
			<?php for ($i = 0; $i < count($airsensors); $i++) {
				$rowid = $airsensors[$i][0];
				$name = $airsensors[$i][1];
				$dt = $airsensors[$i][2];
				$t = $airsensors[$i][3];
				$h = $airsensors[$i][4];
			?>
			<tr>
				<td><a href='sensor.php?sensor=air&id=<?php echo $rowid; ?>'><?php echo $name; ?></a></td>
				<td><?php echo number_format($h, 1);?>%</td>
				<td><?php echo number_format($t, 1);?>&deg;C</td>
				<td><?php echo $dt;?></td>
			</tr>
			<?php } ?>
		</table>
	</div>
	<?php } ?>
	<?php if (count($weightsensors) > 0) { ?>
	<div class="tile">
		<table style="float: left">
			<tr>
				<th>Scale:</th>
				<th>Weight(g):</th> 
				<th>Date:</th>
			</tr>
			<?php for ($i = 0; $i < count($weightsensors); $i++) {
				$rowid = $weightsensors[$i][0];
				$name = $weightsensors[$i][1];
				$dt = $weightsensors[$i][2];
				$w = $weightsensors[$i][3];
			?>
			<tr>
				<td><a href='sensor.php?sensor=weight&id=<?php echo $rowid; ?>'><?php echo $name; ?></a></td>
				<td class='weightval'><?php echo $w;?>g</td> 
				<td><?php echo $dt;?></td>
			</tr>
			<?php } ?>
		</table>
	</div>
	<?php } ?>
	<div class="tile">
		<table style="float: left">
			<tr>
				<td colspan="3" class='seninf'><a href='powermeter.php'>Power Meter (W)</a></td>
			</tr>
			<tr>
				<td colspan="3" class='seninf'><a href='water.php'>Water Schedule (ml)</a></td>
			</tr>
		</table>
	</div>
</div>
<?php if (count($cameras) > 0) { ?>
<div class="block">
	<?php for ($i=0; $i<count($cameras); $i++) {
		$id = $cameras[$i][0];
		$usb = $cameras[$i][1];
	?>
	<div class="tile">
		<a href="cam.php?id=<?php echo $id?>"><img src="tmp/image-<?php echo $id.'-'.$usb?>.jpg" alt="Current" width="100%" height="auto"></a>
	</div>
	<?php } ?>
</div>
<?php } ?>
<hr>

<!-- Setup -->
<div class="block">

<?php
// Get Schedule
$sockets_db = $db->query('SELECT rowid,* FROM Sockets');

?>

<?php while ($socket = $sockets_db->fetchArray(SQLITE3_ASSOC)) { ?>

<?php	$rowid = $socket['rowid'];
		$name = $socket['Name'];
		$gpio = $socket["GPIO"];
		
		if (($socket['Active'] == 1) && ($gpio > 0)) { ?>
		
		<!-- Check GPIO Status, 0 = On, 1 = Off -->
		<?php	$status = 0;
				if (exec('gpio -g read '. $gpio) == 0) { $status = 1; } ?>
		
		<div class="tile min-height">
		<form action="<?php $_PHP_SELF ?>" method="post">
			<span class="dot" <?php if ($status == 1) { echo 'style="background-color:green"'; } ?>></span><font size="4"><b>&nbsp;<u><?php echo $name; ?></u></b></font>
			<?php if ($socket["IsPumping"] == 1) { echo "<img style=\"position:relative; left:10px; top:5px;\" src=\"water_can.png\" />"; } ?>
			
			<!-------------------------------------------->
			<!-- ID Value Hidden -->
			<!-------------------------------------------->

			<input type="hidden" name="id" value="<?php echo $rowid; ?>">
			<input type="hidden" name="gpio" value="<?php echo $gpio; ?>">

			<div class="block">
				
				<!-------------------------------------------->
				<!-- Switch -->
				<!-------------------------------------------->
				
				<?php	$state = $socket['State'];
						$vstate = ($state == 0) ? 1 : 0;
						if ($socket['Switch'] == 1) { ?>

					<div class="tile">
						Off&nbsp;<label class="switch">
							<input type="checkbox" <?php if ($state==1) echo "checked"; ?> onclick="this.form.submit()">
							<span class="slider"></span>
						</label>&nbsp;On
						<input type="hidden" name="switch" value="<?php echo $vstate; ?>">
					</div>
				<?php } ?>

				<!-------------------------------------------->
				<!-- Timer -->
				<!-------------------------------------------->
				
				<?php	$hon = $socket['HOn'];
						$hoff = $socket['HOff'];
						if ($socket['Timer'] == 1) { ?>

					<div class="tile">
						<b>On:</b><br><br>
						<select name="hon" onchange="this.form.submit()">
							<?php 
								for ($x = 0; $x < 24; $x++) {
									if ($x == $hon) { echo "<option value=\"$x\" selected>"; }
									else { echo "<option value=\"$x\">"; }
									echo sprintf("%.02d", $x) . ":00</option>";
								} 
							?>
						</select>
					</div>
					<div class="tile">
						<b>Off:</b><br><br>
						<select name="hoff" onchange="this.form.submit()">
							<?php 
								for ($x = 0; $x < 24; $x++) {
									if ($x == $hoff) { echo "<option value=\"$x\" selected>"; }
									else { echo "<option value=\"$x\">"; }
									echo sprintf("%'.02d", $x) . ":00</option>";
								} 
							?>
						</select>
					</div>
				<?php } ?>
				
				<!-------------------------------------------->
				<!-- Interval -->
				<!-------------------------------------------->
				
				<?php	$power = $socket['Power'];
						$pause = $socket['Pause'];
						if ($socket['Interval'] == 1) {
							$steps = array(0, 10, 20, 30, 40, 50, 60, 120, 180, 240, 300);
							$vals = array('-', '10min', '20min', '30min', '40min', '50min', '1h', '2h', '3h', '4h', '5h');
							$len = count($steps);
						?>

					<div class="tile">
						<b>Power:</b><br><br>
						<select name="power" onchange="this.form.submit()">
						<?php 
							for($x = 0; $x < $len; $x++) {
								if ($steps[$x] == $power) { echo "<option value=\"$steps[$x]\" selected>"; }
								else { echo "<option value=\"$steps[$x]\">"; }
								echo "$vals[$x]</option>";
							}
						?>
						</select>
					</div>
					<div class="tile">
						<b>Pause:</b><br><br>
						<select name="pause" onchange="this.form.submit()">
						<?php 
							for ($x = 0; $x < $len; $x++) {
								if ($steps[$x] == $pause) { echo "<option value=\"$steps[$x]\" selected>"; }
								else { echo "<option value=\"$steps[$x]\">"; }
								echo "$vals[$x]</option>";
							} 
						?>
						</select>
					</div>
				<?php } ?>
				
				<!-- Display Temp Humidity Power -->
				<?php $thpwr = 0; ?>
				
				<!-------------------------------------------->
				<!-- Max Temp -->
				<!-------------------------------------------->
				
				<?php	$tmax = $socket['TMax'];
						if ($socket['MaxTemp'] == 1) {
							$thpwr = 1;?>
						
					<div class="tile">
						<b>Max Temp:</b><br><br>
						<select name="tmax" onchange="this.form.submit()">
						<option value="0">Off</option>
						<?php 
							for ($x = 20; $x <= 40; $x+=5) {
								if ($x == $tmax) { echo "<option value=\"$x\" selected>"; }
								else { echo "<option value=\"$x\">"; }
								echo "$x &deg;C</option>";
							} 
						?>
						</select>
					</div>
				<?php } ?>
				
				<!-------------------------------------------->
				<!-- Min Temp -->
				<!-------------------------------------------->
				
				<?php	$tmin = $socket['TMin'];
						if ($socket['MinTemp'] == 1) {
							$thpwr = 1;?>
						
					<div class="tile">
						<b>Min Temp:</b><br><br>
						<select name="tmin" onchange="this.form.submit()">
						<option value="0">Off</option>
						<?php 
							for ($x = 10; $x <= 30; $x+=5) {
								if ($x == $tmin) { echo "<option value=\"$x\" selected>"; }
								else { echo "<option value=\"$x\">"; }
								echo "$x &deg;C</option>";
							} 
						?>
						</select>
					</div>
				<?php } ?>
				

				<!-------------------------------------------->
				<!-- Max Humi -->
				<!-------------------------------------------->
				
				<?php	$hmax = $socket['HMax'];
						if ($socket['MaxHumi'] == 1) {
							$thpwr = 1;?>
						
					<div class="tile">
						<b>Max Humidity:</b><br><br>
						<select name="hmax" onchange="this.form.submit()">
						<option value="0">-</option>
						<?php 
							for ($x = 40; $x <= 90; $x+=10) {
								if ($x == $hmax) { echo "<option value=\"$x\" selected>"; }
								else { echo "<option value=\"$x\">"; }
								echo "$x %</option>";
							} 
						?>
						</select>
					</div>
				<?php } ?>
				
				<!-------------------------------------------->
				<!-- Min Humi -->
				<!-------------------------------------------->
				
				<?php	$hmin = $socket['HMin'];
						if ($socket['MinHumi'] == 1) {
							$thpwr = 1;?>
						
					<div class="tile">
						<b>Min Humidity:</b><br><br>
						<select name="hmin" onchange="this.form.submit()">
						<option value="0">-</option>
						<?php 
							for ($x = 40; $x <= 90; $x+=10) {
								if ($x == $hmin) { echo "<option value=\"$x\" selected>"; }
								else { echo "<option value=\"$x\">"; }
								echo "$x %</option>";
							} 
						?>
						</select>
					</div>
				<?php } ?>


				<!-------------------------------------------->
				<!-- THPwr -->
				<!-------------------------------------------->
				
				<?php	$thpower = $socket['THPower'];
						if ($thpwr == 1) {
							$steps = array(0, 10, 20, 30, 40, 50, 60, 120, 180, 240, 300);
							$vals = array('-', '10min', '20min', '30min', '40min', '50min', '1h', '2h', '3h', '4h', '5h');
							$len = count($steps);
						?>

					<div class="tile">
						<b>Power:</b><br><br>
						<select name="thpower" onchange="this.form.submit()">
						<?php 
							for($x = 0; $x < $len; $x++) {
								if ($steps[$x] == $thpower) { echo "<option value=\"$steps[$x]\" selected>"; }
								else { echo "<option value=\"$steps[$x]\">"; }
								echo "$vals[$x]</option>";
							}
						?>
						</select>
					</div>
				<?php } ?>				
				
				
				<!-------------------------------------------->
				<!-- Pump -->
				<!-------------------------------------------->
				
				<?php	$days = $socket['Days'];
						$time = $socket['Time'];
						$ml = $socket['MilliLiters'];
						$flowrate = $socket['FlowRate'];
						$daycnt = $socket["DaysCnt"];
						if ($socket['Pump'] == 1) {
							$steps = array(100, 200, 300, 400, 500, 600, 700, 800, 900, 1000,
								1200, 1400, 1600, 1800, 2000, 2200, 2400, 2600, 2800, 3000);
							$len = count($steps);

							$date_now = new DateTime("now");
							$p_date = "";
							if ($days == -1) {
								$h = $date_now->format('H');
								$min = $date_now->format('i');
								$min = ((intval($min/10) + 1) * 10) % 60;
								if ($min == 0) {
									$h += 1;
									$h %= 24;
								}									
								$p_date = $date_now->format('Y-m-d ').sprintf( '%02d:', $h).sprintf( '%02d', $min);
							}
							if ($days > 0) {
								$date_now->add(new DateInterval("P".$daycnt."D"));
								$p_date = $date_now->format('Y-m-d').' '.sprintf( '%02d', $time).':00';
							}

							$interval = array(-1, 0, 1, 2, 3, 4, 5);
							$int_label = array("Now", "-", "1 d", "2 d", "3 d", "4 d", "5 d");
						?>

					<div class="tile">
						<b>Interval:</b><br><br>
						<select name="days" onchange="this.form.submit()">
						<?php 
							for ($x = 0; $x < count($interval); $x++) {
								$val = $interval[$x];
								$label = $int_label[$x];
								echo "<option value=\"$val\"";
								if ($val == $days) { echo " selected"; }
								echo ">$label</option>";
							}
						?>
						</select>
					</div>
					<div class="tile">
						<b>Time:</b><br><br>
						<select name="time" onchange="this.form.submit()">
						<?php 
							for ($x = 0; $x <= 24; $x++) {
								if ($x == $time) { echo "<option value=\"$x\" selected>"; }
								else { echo "<option value=\"$x\">"; }
								echo sprintf("%'.02d", $x) . ":00</option>";
							} 
						?>
						</select>
					</div>
					
					<div class="tile">
						<b>Amount:</b><br><br>
						<select name="ml" onchange="this.form.submit()">
						<?php
							for ($x = 0; $x < $len; $x++) {
								if ($steps[$x] == $ml) { echo "<option value=\"$steps[$x]\" selected>"; }
								else { echo "<option value=\"$steps[$x]\">"; }
								echo sprintf("%.1f", ($steps[$x]/1000)) . " l</option>";
							}
						?>
						</select>
					</div>
					
					<?php if ($days != 0) { ?>
					<div class="tile">
						<b>Date:</b><br><br>
						<?php echo $p_date; ?>
					</div>
					<?php } ?>

				<?php } ?>
				
			</div>
			
		</form>
		</div>
	
<?php } ?>
<?php } ?>

<?php $db->close(); ?>

</div>


<hr>

<?php include 'footer.php';?>

</body>
</html>
