
################################################	
# Growbox Boot
################################################

import config

import sqlite3

import growbox

################################################	
# Connect To SQL Server
################################################
connection = sqlite3.connect(config.DB)
cursor = connection.cursor()

# Reset IsPumping
cursor.execute("UPDATE Sockets SET IsPumping=0 WHERE IsPumping=1")
connection.commit()

# Sockets
connection.row_factory = sqlite3.Row
cursor = connection.cursor()

cursor.execute('SELECT rowid,* FROM Sockets')
sockets = cursor.fetchall()
#num_rows = len(sockets)
for row in sockets:
	# Reset GPIO
	gpio = row['GPIO']
	if (gpio > 0):
		growbox.relay_set(gpio, 0)

################################################
# SQL Close
################################################
connection.close()
