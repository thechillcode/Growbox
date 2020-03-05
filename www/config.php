<!DOCTYPE html>

<!-- database query -->
<?php

$max_gpio = 30;

function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

$db = new SQLite3('/home/pi/DB/Growbox.db');

$query = $db->query('SELECT COUNT(*) FROM Sockets');
$num_sockets = $query->fetchArray(SQLITE3_NUM)[0];

if (isset($_REQUEST['socket']))
{
	$socket = in_range(intval($_REQUEST['socket']), 1, $num_sockets, 0);
	
	$name = isset($_REQUEST['name']) ? $_REQUEST['name'] : "";
	
	$active = isset($_REQUEST['active']) ? in_range(intval($_REQUEST['active']), 0, 1, 0) : 0;

	// Turn Socket off
	if (($active == 0) && ($gpio > 0)) {
		exec("gpio -g mode " . $gpio . " out");
		exec("gpio -g write " . $gpio . " 1");
	}
	
	$load = isset($_REQUEST['load']) ? in_range(intval($_REQUEST['load']), 0, 10000, 0) : 0;
	
	$switch = isset($_REQUEST['switch']) ? in_range(intval($_REQUEST['switch']), 0, 1, 0) : 0;
	$timer = isset($_REQUEST['timer']) ? in_range(intval($_REQUEST['timer']), 0, 1, 0) : 0;
	$interval = isset($_REQUEST['interval']) ? in_range(intval($_REQUEST['interval']), 0, 1, 0) : 0;
	$maxt = isset($_REQUEST['maxt']) ? in_range(intval($_REQUEST['maxt']), 0, 1, 0) : 0;
	$mint = isset($_REQUEST['mint']) ? in_range(intval($_REQUEST['mint']), 0, 1, 0) : 0;
	$maxh = isset($_REQUEST['maxh']) ? in_range(intval($_REQUEST['maxh']), 0, 1, 0) : 0;
	$minh = isset($_REQUEST['minh']) ? in_range(intval($_REQUEST['minh']), 0, 1, 0) : 0;
	$pump = isset($_REQUEST['pump']) ? in_range(intval($_REQUEST['pump']), 0, 1, 0) : 0;
	$flowrate = isset($_REQUEST['flow']) ? in_range(floatval($_REQUEST['flow']), 0, 1000, 0) : 0;
	
	if ($socket != 0) {
		$query = $db->exec("UPDATE Sockets SET Active=" . $active . ",
			Name='". $name . "',
			Load=". $load .",
			Switch=". $switch .",
			Timer=". $timer .",
			Interval=". $interval .",
			MaxTemp=". $maxt .",
			MinTemp=". $mint .",
			MaxHumi=". $maxh .",
			MinHumi=". $minh .",
			Pump=". $pump .",
			FlowRate=". $flowrate ."
			WHERE rowid=". $socket);
	}
}

if (isset($_REQUEST['light'])) {
	$light = in_range(intval($_REQUEST['light']), 1, $num_sockets, 0);
	$db->exec("UPDATE Config SET val=" . $light . " WHERE name='Light'");
}

if (isset($_REQUEST['create'])) {
	exec('php /var/www/growbox/archive.php > /dev/null &');
}

if (isset($_REQUEST['reset'])) {

	exec('rm /var/www/growbox/cam/*');

	$db->exec("DELETE FROM Images WHERE rowid>0");
	$db->exec("DELETE FROM AirSensorData WHERE rowid>0");
	$db->exec("DELETE FROM WeightSensorData WHERE rowid>0");
	$db->exec("DELETE FROM PowerMeter WHERE rowid>0");
	$db->exec("DELETE FROM Water WHERE rowid>0");
}

// Get Config
$config_db = $db->query('SELECT * FROM Config');
$grwconfig = array();
while($conf = $config_db->fetchArray(SQLITE3_ASSOC))
{
	$grwconfig[$conf['name']] = $conf['val'];
}

?>

<html lang="en">
<head>

<script src="script/chart.js"></script>

<title>Growbox - Config</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width - 30px, initial-scale=1">
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
		array("Config", "config.php"),
		);
		
	$title = "Grow Config - " . date('Y-m-d H:i:s');
?>

<?php include 'header.php';?>

<hr>

<?php
	for ($i = 1; $i <= $num_sockets; $i++) { ?>
	
	<form id="socket<?php echo $i; ?>" action="<?php $_PHP_SELF ?>" method="post">
		<input type="text" name="socket" value="<?php echo $i; ?>" hidden />
	</form>
	
<?php } ?>

<table align="center">
	<tr>
		<th rowspan="2">Socket:</th>
		<th rowspan="2">Active:</th>
		<th rowspan="2">Name:</th>
		<th rowspan="2">Load (W):</th>
		<th rowspan="2">Switch:</th>
		<th rowspan="2">Timer:</th>
		<th rowspan="2">Interval:</th>
		<th colspan="2">Temperature</th>
		<th colspan="2">Humidity</th>
		<th rowspan="2">Pump:</th>
		<th rowspan="2">FlowRate (ml/s):</th>
	</tr>
	<tr>
		<th>Max:</th>
		<th>Min:</th>
		<th>Max:</th>
		<th>Min:</th>
	</tr>


<?php $sockets_db = $db->query('SELECT rowid,* FROM Sockets'); ?>

<?php while ($socket = $sockets_db->fetchArray(SQLITE3_ASSOC)) { ?>

<?php	$rowid = $socket['rowid'];
		$name = $socket['Name'];
?>
		
	<tr>
		<td># <?php echo $rowid; ?></td>
		<td>
			<label class="switch">
				<input form="socket<?php echo $rowid; ?>" type="checkbox" name="active" value="1" <?php if ($socket['Active']==1) echo "checked"; ?> onclick="this.form.submit()">
				<span class="slider"></span>
			</label>
		</td>
		
		<td><input form="socket<?php echo $rowid; ?>" type="text" name="name" value="<?php echo $socket['Name']; ?>" onfocusout="this.form.submit()" ></td>

		<td><input form="socket<?php echo $rowid; ?>" type="number" name="load" value="<?php echo $socket['Load']; ?>" min="0" max="10000" onfocusout="this.form.submit()" ></td>

		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="switch" value="1" <?php if ($socket['Switch']==1) echo "checked"; ?> onclick="this.form.submit()"></td>

		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="timer" value="1" <?php if ($socket['Timer']==1) echo "checked"; ?> onclick="this.form.submit()"></td>

		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="interval" value="1" <?php if ($socket['Interval']==1) echo "checked"; ?> onclick="this.form.submit()"></td>

		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="maxt" value="1" <?php if ($socket['MaxTemp']==1) echo "checked"; ?> onclick="this.form.submit()"></td>
		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="mint" value="1" <?php if ($socket['MinTemp']==1) echo "checked"; ?> onclick="this.form.submit()"></td>
		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="maxh" value="1" <?php if ($socket['MaxHumi']==1) echo "checked"; ?> onclick="this.form.submit()"></td>
		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="minh" value="1" <?php if ($socket['MinHumi']==1) echo "checked"; ?> onclick="this.form.submit()"></td>

		<td><input form="socket<?php echo $rowid; ?>" type="checkbox" name="pump" value="1" <?php if ($socket['Pump']==1) echo "checked"; ?> onclick="this.form.submit()"></td>
		<td><input form="socket<?php echo $rowid; ?>" type="number" name="flow" value="<?php echo $socket['FlowRate']; ?>" min="0" max="1000" step="0.01" onfocusout="this.form.submit()" ></td>
	</tr>

		
<?php } ?>

</table>

<hr>

<form action="<?php $_PHP_SELF ?>" method="post">
<table>
	<tr>
		<td>Light:</td>
		<td>
			<select name="light" onchange="this.form.submit()">
				<option value="0" <?php if ($grwconfig['Light'] == 0) { echo ' selected'; }?>> - </option>
			<?php for ($x = 1; $x <= $num_sockets; $x++) {
				if ($x == $grwconfig['Light']) { echo "<option value=\"$x\" selected>"; }
				else { echo "<option value=\"$x\">"; }
				echo "Socket ". $x ."</option>";
			} ?>
			</select>
			On: <?php echo $grwconfig['LightOn']; ?>:00, Off: <?php echo $grwconfig['LightOff']; ?>:00
			<?php $cam_db = $db->query('SELECT rowid,* FROM Cameras'); ?>
			<?php while ($cams = $cam_db->fetchArray(SQLITE3_ASSOC)) {
				echo ", Cam(#" . $cams['rowid'] . ", " . $cams['enabled'] . ", " . $cams['usb'] . ")";
			} ?>
		</td>
	</tr>
</table>
</form>

<hr>

<?php
	$archive = $grwconfig["Archive"];
	$archivedate = $grwconfig["ArchiveDate"];
	if ($archivedate != 0) {
		$jdate = $archivedate;
		$year = (int)($jdate/1000);
		$day = $jdate - ($year*1000) - 1;
		$adate = DateTime::createFromFormat('Y z', "{$year} {$day}");
		echo "Archive ".date_format($adate, "Y-m-d").": <a href='archive/archive.zip'>archive.zip</a>";
		echo "<br><br>";
	}
?>
<?php
	if ($archive == 0) {
?>
	<form action="<?php $_PHP_SELF ?>" method="post">
		<input type="submit" class="button" name="create" value="Create Archive" />
	</form>
<?php
	} else {
		echo "Creating Archive ...";
	}
?>

<hr>
	<form action="<?php $_PHP_SELF ?>" method="post">
		<input type="submit" class="button" name="reset" value="Reset" />&nbsp;Reset Database (delete images, reset database, configuration remains)
	</form>
<hr>

<?php $db->close(); ?>

<?php include 'footer.php';?>

</body>
</html>
