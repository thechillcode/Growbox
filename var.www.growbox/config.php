<!DOCTYPE html>
<?php

/////////////////////////////
// AUX
/////////////////////////////
function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

// UTC Offset in hours
$dt_now = new DateTime("now");
$utc_offset = intval($dt_now->getOffset() / 3600);

function calc_offset($hour, $offset) {
	$hour = $hour + $offset;
	if ($hour < 0) {
		$hour += 24;
	}
	if ($hour > 23) {
		$hour %= 24;
	}
	return $hour;
}
function get_local_hour($utc_hour) {
	global $utc_offset;
	return calc_offset($utc_hour, $utc_offset);
}
function get_utc_hour($local_hour) {
	global $utc_offset;
	return calc_offset($local_hour, -($utc_offset));
}

/////////////////////////////
// Config

// Controls
$socket_controls = array("Switch","Timer","Interval","Temperature","Humidity","Pump");
$socket_controls_num = count($socket_controls);

// Relay
$socket_gpio = array(1 => 14, 15, 18, 23, 24, 25, 8, 7);
$socket_num = 8;

// AirSensor DHT22,AS2302 (out pin)
$air_sensors_gpio = array(1 => 17, 2 => 27);
$air_sensors_num = 2;

// WeightSensor GPIO(DT, SCK)
$weight_sensors_gpio = array(1 => array(22, 10), 2 => array(9, 11));
$weight_sensors_num = 2;

// Cameras
$cameras_num = 4;
$cam_usb_devices = array( 1 => "v4l2:/dev/video0", "v4l2:/dev/video1", "v4l2:/dev/video2", "v4l2:/dev/video3");
$cam_rotations = array(0, 90, 180, 270);
$cam_brightness = array(0, 10, 20, 30, 40, 50, 60, 70, 80,  90, 100);
$cam_contrast = array(0, 10, 20, 30, 40, 50, 60, 70, 80,  90, 100);
$cam_hres = 800;
$cam_vres = 600;
$cam_fps=60;
$cam_awb="tungsten";

// Burst
$burst_gpio = 0;
$burst_sleep = 0.01;
$burst_cycles = 100;

/////////////////////////////
// SQL
/////////////////////////////
$db = new SQLite3('/home/pi/DB/Growbox.db');

/////////////////////////////
// SQL : UPDATE
$RunHandler = 0;

// Main Switch
if (isset($_REQUEST['main']))
{
	$main = in_range(intval($_REQUEST['main']), 0, 1, 0);
	$db->exec("UPDATE Config SET val=" . $main . " WHERE name='Main'");
	if ($main == 0)
	{
		$db->exec("UPDATE Sockets SET IsPumping=0 WHERE IsPumping=1");
		foreach ($socket_gpio as $i => $gpio)
		{
			exec("gpio -g mode " . $gpio . " out");
			exec("gpio -g write " . $gpio . " 1");
		}
	} else {
		$RunHandler = 1;
	}
}

// Cameras
if (isset($_REQUEST["cam"])) {
	$cam_id = in_range(intval($_REQUEST["cam"]), 1, $cameras_num, 1);
	$cam_enabled = isset($_REQUEST["enable"]) ? 1 : 0;
	$cam_usb_device = in_range(intval($_REQUEST["usb"]), 0, count($cam_usb_devices), 0);
	$cam_usb = ($cam_usb_device == 0) ? "" : $cam_usb_devices[$cam_usb_device];
	$cam_rot = in_range(intval($_REQUEST["rot"]), 0, 270, 0);
	$cam_bright = in_range(intval($_REQUEST["brightness"]), 0, 100, 50);
	$cam_cont = in_range(intval($_REQUEST["contrast"]), 0, 100, 0);
	
	$query = $db->exec("UPDATE Cameras SET Enabled=" . $cam_enabled . ",
		usb='". $cam_usb . "',
		rotation=". $cam_rot .",
		hres=" . $cam_hres .",
		vres=". $cam_vres .",
		fps=". $cam_fps .",
		brightness=". $cam_bright .",
		contrast=". $cam_cont .",
		awb='". $cam_awb ."'
		WHERE rowid=". $cam_id);
	$RunHandler = 1;
}

// AirSensors
if (isset($_REQUEST["air"])) {
	$air_id = in_range(intval($_REQUEST["air"]), 1, $air_sensors_num, 1);
	$air_name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : "";
	$air_enabled = isset($_REQUEST["enable"]) ? 1 : 0;
	$air_gpio = ($air_enabled == 0) ? 0 : $air_sensors_gpio[$air_id];	
	
	$query = $db->exec("UPDATE AirSensors SET name='" . $air_name . "',
		gpio=". $air_gpio . "
		WHERE rowid=". $air_id);
	$RunHandler = 1;
}

// WeightSensors
if (isset($_REQUEST["weight"])) {
	$weight_id = in_range(intval($_REQUEST["weight"]), 1, $weight_sensors_num, 1);
	$weight_name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : "";
	$weight_enabled = isset($_REQUEST["enable"]) ? 1 : 0;
	$weight_gpio = ($weight_enabled == 0) ? array(0,0) : $weight_sensors_gpio[$weight_id];
	$weight_cal = in_range(intval($_REQUEST["cal"]), 1, 200, 1);
	$weight_offset = in_range(intval($_REQUEST["offset"]), -1000000, 1000000, 0);
	
	$query = $db->exec("UPDATE WeightSensors SET name='" . $weight_name . "',
		data=". $weight_gpio[0] . ",
		clk=". $weight_gpio[1] . ",
		cal=". $weight_cal . ",
		offset=". $weight_offset . "
		WHERE rowid=". $weight_id);
	$RunHandler = 1;
}

// Update Sockets
if (isset($_REQUEST['socket']))
{
	$socket_id = in_range(intval($_REQUEST['socket']), 1, $socket_num, 0);
	if ($socket_id != 0)
	{
		$name = isset($_REQUEST['name']) ? $_REQUEST['name'] : "";
		
		$active = isset($_REQUEST['active']) ? in_range(intval($_REQUEST['active']), 0, 1, 0) : 0;
		
		$gpio = $socket_gpio[$socket_id];

		// Turn Socket off
		if ($active == 0) {
			exec("gpio -g mode " . $gpio . " out");
			exec("gpio -g write " . $gpio . " 1");
			// Reset Pump
			$query = $db->exec("UPDATE Sockets SET IsPumping=0, ToPump=0 WHERE rowid=". $socket_id);
		}
	
		$load = isset($_REQUEST['load']) ? in_range(intval($_REQUEST['load']), 0, 10000, 0) : 0;
	
		$control = isset($_REQUEST['control']) ? in_range(intval($_REQUEST['control']), 0, 5, 0) : 0;
	
		$switch = ($control == 0) ? 1 : 0;
		$timer = ($control == 1) ? 1 : 0;
		$interval = ($control == 2) ? 1 : 0;
		$pump = ($control == 5) ? 1 : 0;	
	
		$maxt = isset($_REQUEST['maxt']) ? in_range(intval($_REQUEST['maxt']), 0, 1, 0) : 0;
		$mint = isset($_REQUEST['mint']) ? in_range(intval($_REQUEST['mint']), 0, 1, 0) : 0;
		$maxh = isset($_REQUEST['maxh']) ? in_range(intval($_REQUEST['maxh']), 0, 1, 0) : 0;
		$minh = isset($_REQUEST['minh']) ? in_range(intval($_REQUEST['minh']), 0, 1, 0) : 0;

		$flowrate = isset($_REQUEST['flow']) ? in_range(floatval($_REQUEST['flow']), 0, 1000, 0) : 0;
		$wsensorid = isset($_REQUEST['wsensorid']) ? in_range(floatval($_REQUEST['wsensorid']), 1, 8, 0) : 0;
	
		$query = $db->exec("UPDATE Sockets SET Active=" . $active . ",
			Name='". $name . "',
			Load=". $load .",
			Control=" .$control.",
			Switch=". $switch .",
			Timer=". $timer .",
			Interval=". $interval .",
			MaxTemp=". $maxt .",
			MinTemp=". $mint .",
			MaxHumi=". $maxh .",
			MinHumi=". $minh .",
			Pump=". $pump .",
			FlowRate=". $flowrate .",
			WSensorID=". $wsensorid ."
			WHERE rowid=". $socket_id);
	}
}

if (isset($_REQUEST['light'])) {
	$light = in_range(intval($_REQUEST['light']), 1, $socket_num, 0);
	$db->exec("UPDATE Config SET val=" . $light . " WHERE name='Light'");
}

// Reboot
// Do not user UTC time since crontab uses local time
if (isset($_REQUEST['reboot'])) {
	$hour = get_utc_hour(in_range(intval($_REQUEST['reboot']), 0, 23, 0));
	$db->exec("UPDATE Config SET val=" . $hour . " WHERE name='Reboot'");
	$db->exec("UPDATE Config SET val=1 WHERE name='SetReboot'");
	$RunHandler = 1;
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
	$RunHandler = 1;
}

//Burst Relay
if (isset($_REQUEST['burst']))
{
	$burst_gpio = isset($_REQUEST['burst_gpio']) ? in_range(intval($_REQUEST['burst_gpio']), 1, 30, 0) : 0;
	$burst_sleep = isset($_REQUEST['burst_sleep']) ? in_range(floatval($_REQUEST['burst_sleep']), 0, 10, 0) : 0;
	$burst_cycles = isset($_REQUEST['burst_cycles']) ? in_range(intval($_REQUEST['burst_cycles']), 1, 100, 0) : 0;
	
	if (($burst_gpio != 0) && ($burst_sleep != 0) && ($burst_cycles != 0)) {
		exec('gpio mode '. $burst_gpio .' out');
		for ($x = 0; $x <= $burst_cycles; $x++) {
			exec('gpio -g write '. $burst_gpio . ' 0');
			sleep($burst_sleep);
			exec('gpio -g write '. $burst_gpio . ' 1');
			sleep($burst_sleep);
		}
	}	
}

if ($RunHandler == 1) {
	$db->exec("UPDATE Config SET val=1 WHERE name='RunHandler'");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	header( "Location: ". $_SERVER['PHP_SELF'] ."" );
	exit ;
}

/////////////////////////////
// SQL : READ

/////////////////////////////
// Config
$config_db = $db->query('SELECT * FROM Config');
$grwconfig = array();
while($conf = $config_db->fetchArray(SQLITE3_ASSOC))
{
	$grwconfig[$conf['name']] = $conf['val'];
}
$main = $grwconfig["Main"];

// Cameras
$cameras = array();
$cameras_db = $db->query('SELECT rowid,* FROM Cameras');
while ($result = $cameras_db->fetchArray(SQLITE3_ASSOC))
{
	$rowid = $result['rowid'];
	$enabled = $result['enabled'];
	$usb = $result['usb'];
	$rotation = $result['rotation'];
	$brightness = $result['brightness'];
	$contrast = $result['contrast'];
	
	$cameras[$rowid] = array($enabled, $usb, $rotation, $brightness, $contrast);
}

// AirSensor
$air_sensors = array();
$air_sensors_db = $db->query('SELECT rowid,name,gpio FROM AirSensors');
while ($result = $air_sensors_db->fetchArray(SQLITE3_ASSOC))
{
	$rowid = $result['rowid'];
	$name = $result['name'];
	$gpio = $result['gpio'];
	
	if ($rowid <= $air_sensors_num) {
		$air_sensors[$rowid] = array($name, $gpio);
	}
}

// WeightSensors
$weight_sensors = array();
$weight_sensors_active = array();
$weightsensors_db = $db->query('SELECT rowid,name,data,clk,cal,offset FROM WeightSensors');
while ($result = $weightsensors_db->fetchArray(SQLITE3_ASSOC))
{
	$rowid = $result['rowid'];
	$name = $result['name'];
	$data_gpio = $result['data'];
	$clk_gpio = $result['clk'];
	$calibration = $result['cal'];
	$offset = $result['offset'];
	
	if ($rowid <= $weight_sensors_num) {
		$weight_sensors[$rowid] = array($name, $data_gpio, $clk_gpio, $calibration, $offset);
	
		if (($data_gpio != 0) && ($clk_gpio != 0)) {
			$weight_sensors_active[$rowid] = $name;
		}
	}
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

<?php
/////////////////////////////
// Main Switch
/////////////////////////////
$main = $grwconfig["Main"];
?>
<hr>
<form action="<?php $_PHP_SELF ?>" method="post">
<input type="text" name="main" value="<?php echo (($main == 1) ? 0 : 1) ?>" hidden />
<table style="background-color:#9b2423">
	<tr>
		<th><span style="color:white">Main Switch:<span></th>
		<td>
			<label class="switch">
				<input type="checkbox" <?php if ($main==1) echo "checked"; ?> onclick="this.form.submit()">
				<span class="slider"></span>
			</label>
		</td>
	</tr>
</table>
</form>

<?php
/////////////////////////////
// Sockets
/////////////////////////////
?>
<hr>

<?php
	for ($i = 1; $i <= $socket_num; $i++) { ?>
	
	<form id="socket<?= $i ?>" action="<?php $_PHP_SELF ?>" method="post">
		<input type="text" name="socket" value="<?= $i ?>" hidden />
	</form>
	
<?php } ?>

<table align="center">
	<tr>
		<th>Socket:</th>
		<th>GPIO:</th>
		<th>Active:</th>
		<th>Name:</th>
		<th>Load (W):</th>
		<th>Control:</th>
		<th colspan="2">Settings:</th>
	</tr>


<?php $sockets_db = $db->query('SELECT rowid,* FROM Sockets'); ?>

<?php while ($socket = $sockets_db->fetchArray(SQLITE3_ASSOC)) { ?>

<?php
	$rowid = $socket['rowid'];
	$gpio = $socket['GPIO'];
	$name = $socket['Name'];
		
	$control = $socket['Control'];
?>
		
	<tr>
		<td># <?= $rowid ?></td>
		<td><?php echo sprintf('%02d', $gpio); ?></td>
		
		<td>
			<label class="switch">
				<input form="socket<?= $rowid ?>" type="checkbox" name="active" value="1" <?php if (($socket['Active']==1) && ($main==1)) echo "checked";
					elseif ($main==0) echo "disabled"; ?> onclick="this.form.submit()">
				<span class="slider"></span>
			</label>
		</td>
		
		<td><input form="socket<?= $rowid ?>" type="text" name="name" value="<?= $socket['Name'] ?>" onfocusout="this.form.submit()" ></td>

		<td><input form="socket<?= $rowid ?>" type="number" name="load" value="<?= $socket['Load'] ?>" min="0" max="2000" onfocusout="this.form.submit()" ></td>

		<td>
			<select name="control" form="socket<?= $rowid ?>" onchange="this.form.submit()">
			<?php foreach ($socket_controls as $i => $scontrol) { ?>
				<option value="<?= $i ?>" <?php if ($i == $control) echo "selected"; ?>><?= $scontrol ?></option>
			<?php } ?>
			</select>
			
		</td>
		
		<?php // Control
		
		switch ($control) {
			case 0: // Switch
			case 1: // Timer
			case 2: // Intervall
				echo "<td></td><td></td>";
				break;

			case 3: // Temperature
		?>
				<td>
					Min:&nbsp;<input form="socket<?= $rowid ?>" type="checkbox" name="mint" value="1" <?php if ($socket['MinTemp']==1) echo "checked"; ?> onclick="this.form.submit()">
					Max:&nbsp;<input form="socket<?= $rowid ?>" type="checkbox" name="maxt" value="1" <?php if ($socket['MaxTemp']==1) echo "checked"; ?> onclick="this.form.submit()">
				</td><td></td>
		<?php
				break;
			
			case 4: // Humidity
		?>
				<td>
					Min:&nbsp;<input form="socket<?= $rowid ?>" type="checkbox" name="minh" value="1" <?php if ($socket['MinHumi']==1) echo "checked"; ?> onclick="this.form.submit()">
					Max:&nbsp;<input form="socket<?= $rowid ?>" type="checkbox" name="maxh" value="1" <?php if ($socket['MaxHumi']==1) echo "checked"; ?> onclick="this.form.submit()">
				</td><td></td>
		<?php
				break;
			case 5: // Pump
		?>
				<td>Flowrate (ml/s):&nbsp;<input form="socket<?= $rowid ?>" type="number" name="flow" value="<?php echo $socket['FlowRate']; ?>" min="0" max="1000" step="0.1" onfocusout="this.form.submit()" ></td>
				
				<td>
					Weight&nbsp;Sensor:&nbsp;<select name="wsensorid" form="socket<?= $rowid ?>" onchange="this.form.submit()">
					<option value="0" <?php if ($socket['WSensorID'] == 0) { echo "selected"; }?>> - </option>
					<?php foreach ($weight_sensors_active as $wid => $name) { ?>
						<option value="<?= $wid ?>" <?php if ($wid == $socket["WSensorID"]) echo "selected"; ?>><?= $name ?></option>
					<?php } ?>
					</select>
				</td>
		<?php
				break;
		}
		?>
	</tr>
	
<?php } ?>

</table>

<?php
/////////////////////////////
// Light
/////////////////////////////
?>
<hr>

<form action="<?php $_PHP_SELF ?>" method="post">
<table>
	<tr>
		<td>Light:</td>
		<td>
			<select name="light" onchange="this.form.submit()">
				<option value="0" <?php if ($grwconfig['Light'] == 0) { echo ' selected'; }?>> - </option>
				<?php for ($x = 1; $x <= $socket_num; $x++) { ?>
					<option value="<?= $x ?>" <?php if ($x == $grwconfig['Light']) echo "selected"; ?>>Socket <?= $x ?></option>
				<?php } ?>
			</select>
			On: <?= get_local_hour($grwconfig['LightOn']) ?>:00, Off: <?= get_local_hour($grwconfig['LightOff']) ?>:00
		</td>
	</tr>
</table>
</form>

<?php
/////////////////////////////
// Cameras
/////////////////////////////
?>

<hr>

<?php
	foreach ($cameras as $id => $values) { ?>
	
		<form id="cam_<?= $id ?>" action="<?php $_PHP_SELF ?>" method="post">
			<input type="text" name="cam" value="<?= $id ?>" hidden />
		</form>
	
	<?php } ?>

<table align="center">
	<tr>
		<th>Camera:</th>
		<th>Enabled:</th>
		<th>PI/USB:</th>
		<th>Rotation:</th>
		<th>Brightness:</th>
		<th>Contrast:</th>
	</tr>
	<?php foreach ($cameras as $id => $values) {
		$enabled = $values[0];
		$usb = $values[1];
		$rotation = $values[2];
		$brightness = $values[3];
		$contrast = $values[4];
	?>
	<tr>
		<td># <?php echo $id; ?></td>
		
		<td>
		<input type="checkbox" form="cam_<?= $id ?>" name="enable" value="1" <?php if ($enabled==1) { echo "checked"; } ?> onchange="this.form.submit()">
		</td>
		
		<td>
		<select form="cam_<?= $id ?>" name="usb" onchange="this.form.submit()">
			<option value="0" <?php if ($usb == "") { echo "selected"; }?>>Pi Camera</option>
			<?php foreach ($cam_usb_devices as $i => $device) { ?>
				<option value="<?= $i ?>" <?php if ($usb == $device) echo "selected"; ?>><?= $device ?></option>
			<?php } ?>
		</select>
		</td>
		
		
		<td>
		<select form="cam_<?= $id ?>" name="rot" onchange="this.form.submit()">
			<?php foreach ($cam_rotations as $rot) { ?>
				<option value="<?= $rot ?>" <?php if ($rotation == $rot) echo "selected"; ?>><?= $rot ?>&deg;</option>
			<?php } ?>
		</select>
		</td>

		<td>
		<select form="cam_<?= $id ?>" name="brightness" onchange="this.form.submit()">
			<?php foreach ($cam_brightness as $value) { ?>
				<option value="<?= $value ?>" <?php if ($brightness == $value) echo "selected"; ?>><?= $value ?> %</option>
			<?php } ?>
		</select>
		</td>

		<td>
		<select form="cam_<?= $id ?>" name="contrast" onchange="this.form.submit()">
			<?php foreach ($cam_contrast as $value) { ?>
				<option value="<?= $value ?>" <?php if ($contrast == $value) echo "selected"; ?>><?= $value ?> %</option>
			<?php } ?>
		</select>
		</td>

	<tr>
	<?php }	?>
</table>

<?php
/////////////////////////////
// AirSensors
/////////////////////////////
?>

<hr>

<?php
	foreach ($air_sensors as $id => $values) { ?>
	
		<form id="air_<?= $id ?>" action="<?php $_PHP_SELF ?>" method="post">
			<input type="text" name="air" value="<?= $id ?>" hidden />
		</form>
	
	<?php } ?>

<table align="center">
	<tr>
		<th>AirSensor:</th>
		<th>Enabled:</th>
		<th>Location:</th>
		<th>GPIO (DHT22 DATA):</th>
	</tr>
	<?php foreach ($air_sensors as $id => $values) {
		$name = $values[0];
		$enabled_gpio = $values[1];
		$gpio = $air_sensors_gpio[$id];
	?>
	<tr>
		<td># <?php echo $id; ?></td>
		<td>
			<input type="checkbox" form="air_<?= $id ?>" name="enable" value="1" <?php if ($enabled_gpio !=0 ) { echo "checked"; } ?> onchange="this.form.submit()">
		</td>
		<td>
			<input form="air_<?= $id ?>" type="text" name="name" value="<?= $name ?>" onfocusout="this.form.submit()" >
		</td>
		<td><?= $gpio ?></td>
	<tr>
	<?php }	?>
</table>

<?php
/////////////////////////////
// WeightSensors
/////////////////////////////
?>

<hr>

<?php
	foreach ($weight_sensors as $id => $values) { ?>
	
		<form id="weight_<?= $id ?>" action="<?php $_PHP_SELF ?>" method="post">
			<input type="text" name="weight" value="<?= $id ?>" hidden />
		</form>
	
	<?php } ?>

<table align="center">
	<tr>
		<th>WeightSensor:</th>
		<th>Enabled:</th>
		<th>Name:</th>
		<th>Calibration:</th>
		<th>Offset:</th>
		<th>GPIO (HX711 DATA/CLK):</th>
	</tr>
	<?php foreach ($weight_sensors as $id => $values) {
		$name = $values[0];
		$enabled_gpio = $values[1];
		$gpio = $weight_sensors_gpio[$id];
		$cal = $values[3];
		$offset = $values[4];
	?>
	<tr>
		<td># <?php echo $id; ?></td>
		<td>
			<input type="checkbox" form="weight_<?= $id ?>" name="enable" value="1" <?php if ($enabled_gpio !=0 ) { echo "checked"; } ?> onchange="this.form.submit()">
		</td>
		<td>
			<input form="weight_<?= $id ?>" type="text" name="name" value="<?= $name ?>" onfocusout="this.form.submit()" >
		</td>
		<td><input form="weight_<?= $id ?>" type="number" name="cal" value="<?= $cal ?>" min="1" max="200" onfocusout="this.form.submit()" ></td>
		<td><input form="weight_<?= $id ?>" type="number" name="offset" value="<?= $offset ?>" min="-1000000" max="1000000" onfocusout="this.form.submit()" ></td>
		<td><?= $gpio[0]."/".$gpio[1] ?></td>
	</tr>
	<?php }	?>
</table>

<?php
/////////////////////////////
// Reboot
/////////////////////////////
?>
<hr>

<?php
	$reboot = get_local_hour($grwconfig["Reboot"]);
?>
<form action="<?php $_PHP_SELF ?>" method="post">
<table>
	<tr>
		<th>Reboot:</th>
		<td>
			<select name="reboot" onchange="this.form.submit()">
				<?php 
				for ($x = 0; $x < 24; $x++) {
					if ($x == $reboot) { echo "<option value=\"$x\" selected>"; }
					else { echo "<option value=\"$x\">"; }
					echo sprintf("%'.02d", $x) . ":05</option>";
				} 
				?>
			</select>
		</td>
	</tr>
</table>
</form>
				
				
				
<?php
/////////////////////////////
// Archive
/////////////////////////////
?>
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
		<input type="submit" class="button conf_button" name="create" value=" Backup"/>&nbsp;Save Images and Data
	</form>
<?php
	} else {
		echo "Creating Archive ...";
	}
?>

<?php
/////////////////////////////
// Reset
/////////////////////////////
?>
<hr>
<form action="<?php $_PHP_SELF ?>" method="post">
	<input type="submit" class="button conf_button" name="reset" value="Reset"/>&nbsp;Reset Database: (Delete Images, Delete Data, Keep Configuration)
</form>

<?php
/////////////////////////////
// Burst
/////////////////////////////
?>
<hr>
<form id="burst" action="<?php $_PHP_SELF ?>" method="post">
	<input type="text" name="burst" value="1" hidden />
</form>
<table align="center">
	<tr>
		<th>Toggle Relay</th>
	</tr>
</table>
<table align="center">
	<tr>
		<th>Socket:</th>
		<th>Sleep (s):</th>
		<th>Cycles:</th>
	</tr>
	<tr>
		<td>
			<select form="burst" name="burst_gpio">
				<option value="0" <?php if ($burst_gpio == 0) { echo ' selected'; }?>> - </option>
				<?php foreach ($socket_gpio as $i => $gpio) { ?>
					<option value="<?= $gpio ?>" <?php if ($burst_gpio == $gpio) echo "selected"; ?>># <?= $i ?></option>
				<?php } ?>
			</select>
		</td>
		<td><input form="burst" type="number" name="burst_sleep" value="<?php echo $burst_sleep; ?>" min="0" max="10" step="0.01"></td>
		<td><input form="burst" type="number" name="burst_cycles" value="<?php echo $burst_cycles; ?>" min="0" max="100"></td>
		<td><button type="submit" form="burst" value="burst_now">Burst</button></td>
	</tr>
</table>
<hr>

<?php $db->close(); ?>

<?php include 'footer.php';?>

</body>
</html>
