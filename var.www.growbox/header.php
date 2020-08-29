<style>
ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
}

li {
    display: inline;
}
</style>

<ul>

<?php
$navlen = count($nav);

for($x = 0; $x < $navlen; $x++) {
	echo "<li><a href=\"".$nav[$x][1]."\">".$nav[$x][0]."</a></li>\n";
	if ($x < ($navlen-1))
	{
	echo "<li>|</li>\n";
	}
}

?>

<li style="float: right;"><a href="config.php">Config</a></li>
</ul>

<header>
	<?php echo $title;?>
</header>