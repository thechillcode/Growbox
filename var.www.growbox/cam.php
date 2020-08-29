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

// entries
$images = $db->query('SELECT filename FROM Images WHERE id='.$id.' ORDER BY dt DESC');
?>

<html>
<title>Growbox - Cam</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="w3.css">
<link rel="stylesheet" type="text/css" href="greenstyle.css">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<style>
.mySlides {display:none;}
</style>
<body>

<?php
	$nav = array(
		array("Home", "growbox.php"),
		array("Cam", "cam.php?id=".$id),
		array("CamView", "camview.php?id=".$id),
		);
		
	$title = "Cam - " . date('Y-m-d H:i:s');
?>

<?php include 'header.php';?>

<hr>

<div class="block">
	<div class="w3-content w3-display-container">

		<img class="mySlides" src="tmp/image-<?php echo $id;?>.jpg" width="100%" height="auto">
	<?php
		while($img = $images->fetchArray(SQLITE3_NUM))
		{
			echo "\t\t<img class='mySlides' src='cam/$img[0]' width='100%' height='auto'>\n";
		}
		$db->close();
	?>
	  <button class="w3-button w3-black w3-display-left" onclick="plusDivs(-1)">&#10094;</button>
	  <button class="w3-button w3-black w3-display-right" onclick="plusDivs(1)">&#10095;</button>
	</div>
</div>

<script>
var slideIndex = 1;
showDivs(slideIndex);

function plusDivs(n) {
  showDivs(slideIndex += n);
}

function showDivs(n) {
  var i;
  var x = document.getElementsByClassName("mySlides");
  if (n > x.length) {slideIndex = 1}    
  if (n < 1) {slideIndex = x.length}
  for (i = 0; i < x.length; i++) {
     x[i].style.display = "none";  
  }
  x[slideIndex-1].style.display = "block";  
}
</script>

<hr>

<?php include 'footer.php';?>

</body>
</html>
