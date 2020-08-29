CALL ..\root.bat
CALL ..\server.bat
SET srcdir=%rootdir%\RaspberryPi_GrowBox

TITLE %host% - Install GrowBox

plink -pw %pswd% %user%@%host% mkdir /home/pi/Install

SET instpath=/home/pi/Install/GrowBox
SET destdir=%user%@%host%:%instpath%

plink -pw %pswd% %user%@%host% rm -r %instpath%
plink -pw %pswd% %user%@%host% mkdir %instpath%

pscp -r -pw %pswd%^
	%srcdir%/LICENSE.txt^
	%srcdir%/INSTALL.txt^
	%srcdir%/VERSION.txt^
	%srcdir%/install_software.sh^
	%srcdir%/install_files.sh^
	%srcdir%/setup.py^
	%srcdir%/growbox_tables.sql^
	%srcdir%/etc.default.cron^
	%destdir%
	
plink -pw %pswd% %user%@%host% sudo chmod u+rxw,g+rx-w,o-rwx %instpath%/install_software.sh
plink -pw %pswd% %user%@%host% sudo chmod u+rxw,g+rx-w,o-rwx %instpath%/install_files.sh
plink -pw %pswd% %user%@%host% sudo chmod u+rxw,g+rx-w,o-rwx %instpath%/growbox_tables.sql

ECHO "-> %destdir%"

ECHO "-> bin"

plink -pw %pswd% %user%@%host% mkdir %instpath%/home.pi.bin.growbox
pscp -pw %pswd% %srcdir%/home.pi.bin.growbox/* %destdir%/home.pi.bin.growbox

ECHO "-> www"

plink -pw %pswd% %user%@%host% mkdir %instpath%/var.www.growbox
pscp -r -pw %pswd% %srcdir%/var.www.growbox/* %destdir%/var.www.growbox

PAUSE