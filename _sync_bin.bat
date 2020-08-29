CALL ..\root.bat
CALL ..\server.bat
SET srcdir=%rootdir%\RaspberryPi_GrowBox

TITLE %host% - bin\growbox

SET srcdir=%srcdir%\home.pi.bin.growbox
SET destdir=%user%@%host%:/home/pi/bin/growbox

ECHO %pswd%|pscp^
	%srcdir%\config.py^
	%srcdir%\growbox.py^
	%srcdir%\growbox_handler.py^
	%srcdir%\growbox_run_handler.py^
	%srcdir%\growbox_boot.py^
	%srcdir%\hx711.py^
	%destdir%

PAUSE