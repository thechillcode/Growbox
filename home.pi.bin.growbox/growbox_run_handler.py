#############################################
# Log Sensor Value to SQL Database V1
#############################################

# Debug (0=OFF, 1=ON)
_DEBUG = 0

import os

import sqlite3

import config

import datetime

import growbox

#now = datetime.datetime.now()
#now = datetime.datetime.now(datetime.timezone.utc)
now = datetime.datetime.utcnow()

################################################	
# Get Config
################################################
growbox_db = growbox.db()
growbox_db.connect()

grwconfig = growbox_db.get_config()

if (grwconfig["RunHandler"]==1) or ((now.minute % 10) == 0):
	growbox_db.set_config("RunHandler", 0)
	os.system('sudo python /home/pi/bin/growbox/growbox_handler.py >> /var/log/growbox.log 2>&1')

growbox_db.disconnect()

if _DEBUG == 1:
	#now2 = datetime.datetime.now()
	#now2 = datetime.datetime.now(datetime.timezone.utc)
	now2 = datetime.datetime.utcnow()
	delta = now2 - now
	print(now.strftime("%Y/%m/%d, %H:%M:%S"), delta.total_seconds())

