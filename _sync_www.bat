CALL ..\root.bat
CALL ..\server.bat
SET srcdir=%rootdir%\RaspberryPi_GrowBox

TITLE %host% - var/www/growbox

SET srcdir=%srcdir%\var.www.growbox
SET destdir=%user%@%host%:/var/www/growbox

ECHO %pswd%|pscp^
	%srcdir%\script\chart.js^
	%destdir%/script

ECHO %pswd%|pscp^
	%srcdir%\cam.php^
	%srcdir%\camview.php^
	%srcdir%\config.php^
	%srcdir%\favicon.ico^
	%srcdir%\footer.php^
	%srcdir%\greenstyle.css^
	%srcdir%\header.php^
	%srcdir%\growbox.php^
	%srcdir%\powermeter.php^
	%srcdir%\sensor.php^
	%srcdir%\slider.css^
	%srcdir%\water.php^
	%srcdir%\w3.css^
	%srcdir%\water_can.png^
	%srcdir%\archive.php^
	%destdir%

PAUSE