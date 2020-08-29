CALL ..\root.bat
CALL ..\server.bat

TITLE %host% - Update Database

::plink -pw %pswd% %user%@%host% sudo sqlite3 /home/pi/DB/Growbox.db < growbox_tables.sql

plink -pw %pswd% %user%@%host% sudo sqlite3 /home/pi/DB/Growbox.db < update_database.sql

::plink -pw %pswd% %user%@%host% sudo sqlite3 -line /home/pi/DB/Growbox.db '[SQL]^;'

PAUSE