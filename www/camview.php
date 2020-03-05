<!DOCTYPE html>


<!-- database query -->
<?php
$db = new SQLite3('/home/pi/DB/Growbox.db');

function in_range($number, $min, $max, $default)
{
    return ($number >= $min && $number <= $max) ? $number : $default;
}

// ID
$id = 1;
if (isset($_GET['id']))
{
	$id = in_range(intval($_GET['id']), 1, 4, 1);
}
$cam = $db->querySingle('SELECT enabled,usb FROM Cameras WHERE rowid='.$id, true);

// entries
$images = $db->query('SELECT filename FROM Images WHERE id='.$id.' ORDER BY rowid DESC');
?>

<html>
<title>Growbox - CamView</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="greenstyle.css">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />

<body>

<?php
	$nav = array(
		array("Home", "growbox.php"),
		array("Cam", "cam.php?id=".$id),
		array("CamView", "camview.php?id=".$id),
		);
		
	$title = "CamView - " . date('Y-m-d H:i:s');
?>

<?php include 'header.php';?>

<br>

<!-- 3xX -->

<?php 

$img = $images->fetchArray(SQLITE3_NUM);
$date = substr($img[0], 0, 10);

while ($img) {

	echo "$date<br>\n";
	echo "<div class=\"block\">\n";

	$curdate = $date;
	while ($curdate == $date) {
	
		echo "\t<div class=\"tile\">\n";
		echo "\t\t<a href=\"cam/" . $img[0] . "\"><img src=\"cam/" . $img[0] . "\" width=\"360\" height=\"auto\"></a>\n";
		echo "\t</div>\n";
		$img = $images->fetchArray(SQLITE3_NUM);
		
		if ($img) {
			$date = substr($img[0], 0, 10);
		}
		else {
			$date = "";
		}
	}
	echo '</div>';
} 
?>

<br>

<?php include 'footer.php';?>

</body>
</html>
