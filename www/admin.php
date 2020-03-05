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
	
	$gpio = isset($_REQUEST['gpio']) ? in_range(intval($_REQUEST['gpio']), 1, 30, 0) : 0;

	
	if ($socket != 0) {
		$query = $db->exec("UPDATE Sockets SET GPIO=". $gpio ." WHERE rowid=". $socket);
	}
}

//Burst Relay
$burst_gpio = 0;
$burst_sleep = 0.01;
$burst_cycles = 100;
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

<title>Growbox - Admin</title>
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
		array("Admin", "admin.php"),
		);
		
	$title = "Grow Admin - " . date('Y-m-d H:i:s');
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
		<th>Socket:</th>
		<th>Name:</th>
		<th>GPIO:</th>
	</tr>


<?php $sockets_db = $db->query('SELECT rowid,* FROM Sockets'); ?>

<?php while ($socket = $sockets_db->fetchArray(SQLITE3_ASSOC)) { ?>

<?php	$rowid = $socket['rowid'];
		$name = $socket['Name'];
?>
		
	<tr>
		<td># <?php echo $rowid; ?></td>

		<td><?php echo $name; ?></td>
		
		<td>
			<select form="socket<?php echo $rowid; ?>" name="gpio" onchange="this.form.submit()">
				<option value="0" <?php if ($socket['GPIO'] == 0) { echo ' selected'; }?>> - </option>
				<?php for ($x = 1; $x <= $max_gpio; $x++) {
					if ($x == $socket['GPIO']) { echo "<option value=\"$x\" selected>"; }
					else { echo "<option value=\"$x\">"; }
					echo $x ."</option>";
				} ?>
			</select>
		</td>
	</tr>

		
<?php } ?>

</table>

<hr>

<form id="burst" action="<?php $_PHP_SELF ?>" method="post">
	<input type="text" name="burst" value="1" hidden />
</form>

<table align="center">
	<tr>
		<th>GPIO:</th>
		<th>Sleep (s):</th>
		<th>Cycles:</th>
	</tr>

	<tr>
		<td>
			<select form="burst" name="burst_gpio">
				<option value="0" <?php if ($burst_gpio == 0) { echo ' selected'; }?>> - </option>
				<?php for ($x = 1; $x <= $max_gpio; $x++) {
					if ($x == $burst_gpio) { echo "<option value=\"$x\" selected>"; }
					else { echo "<option value=\"$x\">"; }
					echo $x ."</option>";
				} ?>
			</select>
		</td>
		<td><input form="burst" type="number" name="burst_sleep" value="<?php echo $burst_sleep; ?>" min="0" max="10" step="0.01"></td>
		<td><input form="burst" type="number" name="burst_cycles" value="<?php echo $burst_cycles; ?>" min="0" max="100"></td>
	</tr>
	
	<tr>
		<td colspan="3"><button type="submit" form="burst" value="burst2">Burst</button></td>
	</tr>
</table>

<hr>

<?php $db->close(); ?>

<?php include 'footer.php';?>

</body>
</html>
