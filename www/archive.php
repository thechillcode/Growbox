<!-- database query -->
<?php

$db = new SQLite3('/home/pi/DB/Growbox.db');

// Get Config
$config_db = $db->query('SELECT * FROM Config');
$grwconfig = array();
while($conf = $config_db->fetchArray(SQLITE3_ASSOC))
{
	$grwconfig[$conf['name']] = $conf['val'];
}

if ($grwconfig["Archive"] == 0) {

	$db->exec("UPDATE Config SET val=1 WHERE name='Archive'");
	$db->exec("UPDATE Config SET val=0 WHERE name='ArchiveDate'");
	//$db->commit();
	
	unlink ("/var/www/growbox/archive/archive.zip");
	
	// AirSensorData
	exec("sqlite3 -header -csv /home/pi/DB/Growbox.db 'Select * From AirSensorData ORDER BY id, rowid ASC;' > /var/www/growbox/archive/AirSensorData.csv");
	exec("sqlite3 -header -csv /home/pi/DB/Growbox.db 'Select * From WeightSensorData ORDER BY id, rowid ASC;' > /var/www/growbox/archive/WeightSensorData.csv");
	exec("sqlite3 -header -csv /home/pi/DB/Growbox.db 'Select * From PowerMeter;' > /var/www/growbox/archive/PowerMeter.csv");
	exec("sqlite3 -header -csv /home/pi/DB/Growbox.db 'Select * From Water ORDER BY id, rowid ASC;' > /var/www/growbox/archive/Water.csv");
	
			// Create new zip class 
	$zip = new ZipArchive;
	$zipfile = "/var/www/growbox/archive/archive.zip";

	if($zip -> open($zipfile, ZipArchive::CREATE|ZipArchive::OVERWRITE ) === TRUE) {
	
		$zip -> addFile("/var/www/growbox/archive/AirSensorData.csv", "AirSensorData.csv");
		$zip -> addFile("/var/www/growbox/archive/WeightSensorData.csv", "WeightSensorData.csv");
		$zip -> addFile("/var/www/growbox/archive/PowerMeter.csv", "PowerMeter.csv");
		$zip -> addFile("/var/www/growbox/archive/Water.csv", "Water.csv");
		
		$pathdir = "/var/www/growbox/cam/";
		$dir = opendir($pathdir); 
		$zip->addEmptyDir("cam");
		while($file = readdir($dir)) { 
			if(is_file($pathdir.$file)) {
				$zip -> addFile($pathdir.$file, "cam/".$file);
			} 
		}
		$zip ->close(); 
	}
	
	$day = date('z')+1;
	$jdate = date('Y')*1000 + $day;
	
	$db->exec("UPDATE Config SET val=".$jdate." WHERE name='ArchiveDate'");
	$db->exec("UPDATE Config SET val=0 WHERE name='Archive'");
}

$db->close();

?>