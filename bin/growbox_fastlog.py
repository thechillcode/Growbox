#############################################
# Log Sensor Value to SQL Database V1
#############################################

import datetime

import sqlite3

import growbox

import config

# date time
dt = datetime.datetime.now().strftime("%Y-%m-%d %H:%M")

# connect sql
connection = sqlite3.connect(config.DB)
cursor = connection.cursor()

# read sensors
cursor.execute('SELECT * FROM AirSensors')
id = 0
for row in cursor.fetchall():
	id = id + 1
	# row[0] = name, row[1] = gpio
	name = row[0]
	gpio = row[1]
	#print('FastLog AirSensor', id, name, gpio)
	if (gpio != 0):
		# try and read sensor
		humidity, temperature = growbox.airsensor_read(config.AirSensorType, gpio)
		#print('FastLog AirSensor', id, humidity, temperature)
		if humidity is not None and temperature is not None:
			# update AirSensorDataLog
			cursor.execute('INSERT INTO AirSensorDataLog(dt, id, temperature, humidity) VALUES (\'{0}\', {1}, {2:0.1f}, {3:0.1f})'.format(dt, id, temperature, humidity))
			connection.commit()

connection.close()