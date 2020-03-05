
################################################	
# Call every 10 minutes, checks schedule and sets Relay accordingly
# Pump is executed on hour check in case script misses iteration on full hour
# but this will execute the pump is updated right after execution with same time, e.g. change the amount
# maybe set DaysCnt to 1 after update?
################################################

import config

import os

import datetime

import sqlite3

import growbox

from time import sleep

from shutil import copyfile

from threading import Thread

#Update fake hardware clock
os.system("sudo fake-hwclock")

# Define exeint, execute intervall in minutes !!!! Important for Vent and Fan !!!!
exeint = config.ExeInt

# date time
now = datetime.datetime.now()
dt = now.strftime("%Y-%m-%d %H:%M")
#print now.year, now.month, now.day, now.hour, now.minute, now.second
# 2015 5 6 8 53 40

################################################	
# Connect To SQL Server
################################################
connection = sqlite3.connect(config.DB)
cursor = connection.cursor()

################################################	
# Get Config
################################################
cursor.execute('SELECT * FROM Config')

grwconfig = dict()
for row in cursor:
	grwconfig[row[0]] = row[1]
	#print(row)

################################################	
# New Day
################################################
newday = None
tt = now.timetuple()
jday = tt.tm_year * 1000 + tt.tm_yday
if (jday != grwconfig['JulienDay']):
	newday = True
	cursor.execute("UPDATE Config SET val={0} WHERE name='JulienDay'".format(jday))

	# SQL Cleanup, Save Data Max 6 months
	cursor.execute("DELETE FROM AirSensorData WHERE DATE(dt) < DATE('now', '-6 months')")
	cursor.execute("DELETE FROM WeightSensorData WHERE DATE(dt) < DATE('now', '-6 months')")
	cursor.execute("DELETE FROM PowerMeter WHERE DATE(dt) < DATE('now', '-6 months')")
	cursor.execute("DELETE FROM Water WHERE DATE(dt) < DATE('now', '-6 months')")
	#cursor.execute("DELETE FROM Images WHERE DATE(dt) < DATE('now', '-6 months')")
	connection.commit()

################################################	
# Air Sensors
################################################
cursor.execute('SELECT * FROM AirSensors')
id = 0
temperature1 = 0.0
humidity1 = 0.0
for row in cursor.fetchall():
	id = id + 1
	# row[0] = name, row[1] = gpio
	name = row[0]
	gpio = row[1]
	if (gpio != 0):
		# read sql data
		cursor.execute('SELECT temperature, humidity FROM AirSensorDataLog WHERE id={0}'.format(id))
		count = 0
		temperature = 0.0
		humidity = 0.0
		for row2 in cursor.fetchall():
			# row2[0] = dt, row2[1] = id, row2[2] = temp, row2[3] = humidity
			temperature += row2[0]
			humidity += row2[1]
			count += 1
		
		if (count > 0):
			temperature /= count
			humidity /= count
			cursor.execute('INSERT INTO AirSensorData(dt, id, temperature, humidity) VALUES (\'{0}\', {1}, {2:0.1f}, {3:0.1f})'.format(dt, id, temperature, humidity))
			if (id == 1):
				temperature1 = temperature
				humidity1 = humidity
		
if (id > 0):
	cursor.execute("DROP TABLE IF EXISTS AirSensorDataLog")
	cursor.execute("CREATE TABLE AirSensorDataLog(dt DATETIME NOT NULL, id INT NOT NULL, temperature REAL NOT NULL, humidity REAL NOT NULL)")
	connection.commit()

################################################	
# Weight Sensors
################################################
cursor.execute('SELECT * FROM WeightSensors')
id = 0
for row in cursor.fetchall():
	id = id + 1
	# row[0] = name, row[1] = data, row[2] = clk, row[3] = cal, row[4] = offset
	name = row[0]
	data = row[1]
	clk = row[2]
	cal = row[3]
	offset = row[4]
	#print('Weight', id, name, data, clk, cal, offset)
	if (data > 0) and (clk > 0):
		# read data
		weight = growbox.get_weight(data, clk, cal, offset)
		cursor.execute('INSERT INTO WeightSensorData(dt, id, weight) VALUES (\'{0}\', {1}, {2})'.format(dt, id, weight))
		connection.commit()
	
################################################	
# Get Sockets
################################################
def dict_factory(cursor, row):
	d = {}
	for idx, col in enumerate(cursor.description):
		d[col[0]] = row[idx]
	return d

#connection.row_factory = sqlite3.Row
connection.row_factory = dict_factory
cursor = connection.cursor()

cursor.execute('SELECT rowid,* FROM Sockets')
sockets = cursor.fetchall()
pumps = []
sockets_load = []

# Reset row_factory
connection.row_factory = None
cursor = connection.cursor()

################################################	
# Manage Sockets
################################################

for i in range(len(sockets)):

	socket = sockets[i]
	rowid = i+1
	sockets_load.append(0)
	
	#print(socket)

	# Active
	gpio = socket['GPIO']
	if (socket['Active'] == 1) and (gpio > 0):
	
		name = socket['Name']
		load = socket['Load']
		switch_on = 0
		
		################################################
		# Switch, Timer, Intervall work together
		################################################
		################################################
		# Switch
		################################################
		if ((socket["Switch"] == 1) and (socket["State"] == 1)):
			switch_on = 1

		################################################
		# Timer
		################################################
		if ((socket["Timer"] == 1) and growbox.switch_on(socket['HOn'], socket['HOff'], now.hour)):
			switch_on = 1
		
		################################################
		# Interval, works with timer if activated
		################################################
		if (socket["Interval"] == 1):
			if (socket['PowerCnt'] > 0):
				if (socket['Pause'] > 0):
					socket['PowerCnt'] -= exeint
					if (socket["PowerCnt"] <= 0):
						socket['PowerCnt'] = 0
						# Update Database
						cursor.execute("UPDATE Sockets SET PauseCnt={0} WHERE rowid={1}".format(socket['Pause'], rowid))
					cursor.execute("UPDATE Sockets SET PowerCnt={0} WHERE rowid={1}".format(socket['PowerCnt'], rowid))
					connection.commit()
				switch_on = 1
			
			
			elif (socket['PauseCnt'] > 0):
				socket['PauseCnt'] -= exeint
				switch_on = 0
				if (socket["PauseCnt"] <= 0):
					socket['PauseCnt'] = 0
					cursor.execute("UPDATE Sockets SET PowerCnt={0} WHERE rowid={1}".format(socket['Power'], rowid))
				cursor.execute("UPDATE Sockets SET PauseCnt={0} WHERE rowid={1}".format(socket['PauseCnt'], rowid))
				connection.commit()
			
		################################################
		# Max Temp
		################################################
		thpwr = 0
		if ((socket["MaxTemp"] == 1) and (socket["TMax"] > 0)):
			thpwr = 1
			if (temperature1 > socket["TMax"]):
				socket["THPowerCnt"] = socket["THPower"]
				
		################################################
		# Min Temp
		################################################
		if ((socket["MinTemp"] == 1) and (socket["TMin"] > 0)):
			thpwr = 1
			if (temperature1 < socket["TMin"]):
				socket["THPowerCnt"] = socket["THPower"]
		
		################################################
		# Max Humidity
		################################################
		if ((socket["MaxHumi"] == 1) and (socket["HMax"] > 0)):
			thpwr = 1
			if (humidity1 > socket["HMax"]):
				socket["THPowerCnt"] = socket["THPower"]
				
		################################################
		# Min Humidity
		################################################
		if ((socket["MinHumi"] == 1) and (socket["HMin"] > 0)):
			thpwr = 1
			if (humidity1 < socket["HMin"]):
				socket["THPowerCnt"] = socket["THPower"]
				
		################################################
		# TH Power
		################################################
		if ((socket["THPowerCnt"] > 0) and (thpwr == 1)):
			socket["THPowerCnt"] -= exeint
			if (socket["THPowerCnt"] <= 0):
				socket["THPowerCnt"] = 0
			# Update Database
			cursor.execute("UPDATE Sockets SET THPowerCnt={0} WHERE rowid={1}".format(socket['THPowerCnt'], rowid))
			connection.commit()
			switch_on = 1
			
				
		################################################
		# Pump Handle
		# Pump 200ml with 2m pause, should have a positive effect on how the earth absorbes the water
		# Do not set pump while IsPumping, cannot use check, in case power out during pumping
		################################################
		ml = socket['MilliLiters']
		fr = socket['FlowRate']

		if (socket["Pump"] == 1):

			if (socket['Days'] > 0):
				if (socket['DaysCnt'] > 0) and (newday):
					socket['DaysCnt'] -= 1
					if (socket["DaysCnt"] <= 0):
						socket['DaysCnt'] = 0
					# Update Database
					cursor.execute("UPDATE Sockets SET DaysCnt={0} WHERE rowid={1}".format(socket['DaysCnt'], rowid))
					connection.commit()

				if (socket['DaysCnt'] == 0) and (now.hour == socket["Time"]):
					cursor.execute("UPDATE Sockets SET DaysCnt={0} WHERE rowid={1}".format(socket['Days'], rowid))
					connection.commit()
					# Only Pump if FlowRate is greater than zero
					if (fr > 0):
						pumps.append([ rowid, gpio, ml, fr, name ])
						sockets_load[i] = load
			
			if (socket['Days'] == -1) and (fr > 0):
				pumps.append([ rowid, gpio, ml, fr, name ])
				sockets_load[i] = load
				cursor.execute("UPDATE Sockets SET Days=0 WHERE rowid={0}".format(rowid))
				connection.commit()

		################################################
		# Switch Socket On or Off, except for Pump
		################################################
		if switch_on:
			growbox.relay_set(gpio, 1)
			sockets_load[i] = load
		elif (socket['IsPumping'] == 0):
			growbox.relay_set(gpio, 0)

################################################
# Update Power Meter
################################################
cursor.execute("INSERT INTO PowerMeter VALUES ('{0}',{1},{2},{3},{4},{5},{6},{7},{8})".format(dt,
	sockets_load[0],sockets_load[1],sockets_load[2],sockets_load[3],sockets_load[4],sockets_load[5],sockets_load[6],sockets_load[7]))
connection.commit()

################################################
# capture new image
################################################
cursor.execute('SELECT * FROM Cameras')
s_info = " " + now.strftime("%Y-%m-%d %H:%M, %a, W%W") + ", H: {0:0.1f}%, T: {1:0.1f}C ".format(humidity1,temperature1)
id = 0
for row in cursor.fetchall():
	id = id + 1
	# row[0] = enabled, row[1] = usb, row[2] = rotation
	enabled = row[0]
	usb = row[1]
	rotation = row[2]
	if (enabled == 1):
		# capture image
		#img_file = '/var/www/growbox/cam/image-{0}-{1}.jpg'.format(id, usb)
		img_file = '/var/www/growbox/tmp/image-{0}-{1}.jpg'.format(id, usb)
		if (usb == 0):
			growbox.camera_capture(img_file, rotation, s_info)
		if (usb == 1):
			growbox.camera_capture_usb(img_file, rotation, s_info)

if ((grwconfig["Light"] != 0) and (now.minute == 0) and growbox.log_image(grwconfig['LightOn'], grwconfig['LightOff'], now.hour)):
	cursor.execute('SELECT * FROM Cameras')
	id = 0
	for row in cursor.fetchall():
		id = id + 1
		# row[0] = enabled, row[1] = usb
		enabled = row[0]
		usb = row[1]
		if (enabled == 1):
			# save image
			src_file = '/var/www/growbox/tmp/image-{0}-{1}.jpg'.format(id, usb)
			filename = now.strftime("%Y-%m-%d_%H-%M_{0}-{1}.jpg".format(id, usb))
			dest_file = '/var/www/growbox/cam/' + filename
			copyfile(src_file, dest_file)
			cursor.execute('INSERT INTO Images(dt, id, filename) VALUES (\'{0}\', {1}, \'{2}\')'.format(dt, id, filename))
			connection.commit()

################################################
# Watering, Start Pumping
# Pump 200ml with 5m pause, should have a positive effect on how the earth absorbes the water
# Pumping is threaded so all pumps can start at the same time
################################################
def water_plant(rowid, gpio, ml, fr):
	connection = sqlite3.connect(config.DB)
	cursor = connection.cursor()

	ml_total = ml
	if (ml > 0):
		cursor.execute("UPDATE Sockets SET IsPumping=1 WHERE rowid={0}".format(rowid))
		connection.commit()
		
		while ml > 0:
			pump_now = config.PumpInc
			if ml < pump_now:
				pump_now = ml
			ml -= pump_now
			slp = int(pump_now/fr) + 1 # add one second for pump to start till water comes from the tubes
			growbox.relay_set(gpio, 1)
			sleep(slp)
			growbox.relay_set(gpio, 0)
			if ml > 0:
				sleep(config.PumpPause)
			
		cursor.execute("UPDATE Sockets SET IsPumping=0 WHERE rowid={0}".format(rowid))
		connection.commit()

	cursor.execute("INSERT INTO Water (dt, id, ml) VALUES ('{0}', {1}, {2})".format(dt, rowid, ml_total))
	connection.commit()
	connection.close()
	#print "Thread Stop:", database, rowid, gpio, ml, fr, pump_inc
	
for topump in pumps:
	rowid = topump[0]
	gpio = topump[1]
	ml = topump[2]
	fr = topump[3]
	name = topump[4]

	thread = Thread(target = water_plant, args = (rowid, gpio, ml, fr))
	#print "Thread Started:", config.DB, rowid, gpio, ml, fr, config.PumpInc
	thread.start()
	
################################################
# SQL Close
################################################
connection.close()

#print "Handler: End"
