CALL ..\root.bat
CALL ..\server.bat
SET srcdir=%rootdir%\RaspberryPi_GrowBox

TITLE %host% - DB\

SET instpath=/home/pi/Install/Growbox
SET destdir=%user%@%host%:%instpath%

pscp -r -pw %pswd%^
	%srcdir%/growbox_tables.sql^
	%destdir%

plink -pw %pswd% %user%@%host% sudo sqlite3 -init /home/pi/Install/Growbox/growbox_tables.sql /home/pi/DB/Growbox.db .quit

PAUSE