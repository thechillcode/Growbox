
################################################	
# Growbox Boot
################################################

import growbox

################################################	
# Connect To SQL Server
################################################
growbox_db = growbox.db()
growbox_db.connect()

# Run Handler ASAP
growbox_db.set_config("RunHandler", 1)

# Sockets
sockets = growbox_db.get_sockets()
for row in sockets:
	# Reset GPIO
	gpio = row['GPIO']
	if (gpio > 0):
		growbox.relay_set(gpio, 0)

################################################
# SQL Close
################################################
growbox_db.disconnect()