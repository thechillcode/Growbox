
################################################	
# Call every 10 minutes, checks schedule and sets Relay accordingly
# Pump is executed on hour check in case script misses iteration on full hour
# but this will execute the pump is updated right after execution with same time, e.g. change the amount
# maybe set DaysCnt to 1 after update?
# Growbox uses UTC Time, to avoid Winter Summer Time Switch
################################################

import config

import growbox

import os

import datetime

from time import sleep

from shutil import copyfile

from threading import Thread

from crontab import CronTab

# Define exeint, execute intervall in minutes !!!! Important for Vent and Fan !!!!
exeint = config.ExeInt

# date time
#now = datetime.datetime.now()
#now = datetime.now(timezone.utc)
now_local = datetime.datetime.now()
now = datetime.datetime.utcnow()
dt = now.strftime("%Y-%m-%d %H:%M")
# 2015 5 6 8 53 40
#print (now.year, now.month, now.day, now.hour, now.minute, now.second)

################################################	
# HW Clock
################################################
if (now.minute == 0):
	#Update fake hardware clock
	os.system("sudo fake-hwclock")

################################################	
# Connect To SQL Server
################################################
growbox_db = growbox.db()
growbox_db.connect()

################################################	
# Get Config
################################################
grwconfig = growbox_db.get_config()

################################################	
# Exit if Main Switch is off
if (grwconfig["Main"] == 0):
	exit()

################################################	
# Reboot
if (grwconfig["SetReboot"] == 1):
	reboot = grwconfig["Reboot"]

	growbox_db.set_config("SetReboot", 0)

	my_cron = CronTab(user='root')
	for job in my_cron:
		if job.comment == 'growbox_reboot':
			job.hour.on(reboot)
			#job.minute.on(5)
			my_cron.write()
			
################################################	
# New Day
################################################
newday = None
tt = now.timetuple()
jday = tt.tm_year * 1000 + tt.tm_yday
if (jday != grwconfig['JulienDay']):
	newday = True
	
	growbox_db.set_config("JulienDay", jday)

	# SQL Cleanup, Remove old data
	growbox_db.clean()

################################################	
# Air Sensors
################################################

temperature1 = 0.0
humidity1 = 0.0

# get sensors
air_sensors = growbox_db.get_airsensors()

for v in air_sensors:
	id = v[0]
	name = v[1]
	gpio = v[2]
	if (gpio != 0):
		humidity, temperature = growbox.airsensor_read(config.AirSensorType, gpio)
		if humidity is not None and temperature is not None:
			growbox_db.insert_airsensordata(dt, id, temperature, humidity)
			if (id == 1):
				temperature1 = temperature
				humidity1 = humidity
	

################################################	
# Weight Sensors
################################################
weight_sensors = growbox_db.get_weightsensors()
weight_sensor_data = dict()

for v in weight_sensors:
	id = v[0]
	name = v[1]
	data = v[2]
	clk = v[3]
	cal = v[4]
	offset = v[5]
	if (data > 0) and (clk > 0):
		weight = growbox.get_weight(data, clk, cal, offset)
		growbox_db.insert_weightsensordata(dt, id, weight)
		
		weight_sensor_data[id] = weight

################################################	
# LOG Image before running Sockets when <log_image> == "OFF" else after
################################################
log_image = growbox.log_image(grwconfig['LightOn'], grwconfig['LightOff'], now.hour)

cameras = growbox_db.get_cameras()

def take_image():
	# save image text with local time
	s_info = " " + now_local.strftime("%Y-%m-%d %H:%M, %a, W%W") + ", H: {0:0.1f}%, T: {1:0.1f}C ".format(humidity1,temperature1)

	for v in cameras:
		id = v[0]
		enabled = v[1]
		usb_device = v[2]
		hres = v[3]
		vres = v[4]
		rotation = v[5]
		fps = v[6]
		brightness = v[7]
		contrast = v[8]
		awb = v[9]
		#print("Cam:", id, enabled, usb_device, hres, vres, rotation, fps, brightness, contrast, awb)
		if (enabled == 1):
			# capture image
			img_file = '/var/www/growbox/tmp/image-{0}.jpg'.format(id)
			if (usb_device == ''):
				growbox.camera_capture(img_file, hres, vres, rotation, s_info, fps, brightness, contrast, awb)
			else:
				growbox.camera_capture_usb(usb_device, img_file, hres, vres, rotation, s_info, fps, brightness, contrast)


def save_image(log_image):
	if ((grwconfig["Light"] != 0) and (now.minute == 0) and (log_image != "")):
	
		for v in cameras:
			id = v[0]
			enabled = v[1]
			if (enabled == 1):
				# save image
				src_file = '/var/www/growbox/tmp/image-{0}.jpg'.format(id)
				filename = now.strftime("%Y-%m-%d_%H-%M_{0}.jpg".format(id))
				dest_file = '/var/www/growbox/cam/' + filename
				copyfile(src_file, dest_file)
				growbox_db.insert_image(dt, id, filename)

if (log_image == "OFF"):
	take_image()
	save_image(log_image)
	
################################################	
# Get Sockets
################################################
sockets = growbox_db.get_sockets()
sockets_load = dict()
water_plants = []

################################################	
# Manage Sockets
################################################

for socket in sockets:

	rowid = socket["rowid"]
	
	sockets_load[rowid] = 0
	
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
						growbox_db.set_socket(rowid, "PauseCnt", socket["Pause"])
					growbox_db.set_socket(rowid, "PowerCnt", socket["PowerCnt"])
				switch_on = 1
			
			
			elif (socket['PauseCnt'] > 0):
				socket['PauseCnt'] -= exeint
				switch_on = 0
				if (socket["PauseCnt"] <= 0):
					socket['PauseCnt'] = 0
					growbox_db.set_socket(rowid, "PowerCnt", socket["Power"])
				growbox_db.set_socket(rowid, "PauseCnt", socket["PauseCnt"])
			
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
			growbox_db.set_socket(rowid, "THPowerCnt", socket["THPowerCnt"])
			switch_on = 1
			
				
		################################################
		# Pump Handle
		# Pump 200ml with 30m pause, should have a positive effect on how the earth absorbes the water
		# Note: Watering using the scale does not work when your pump's flowrate is high => don't do it, use the flowrate
		################################################
		ml = socket['MilliLiters']
		fr = socket['FlowRate']
		wsid = socket['WSensorID']
		minw = socket['MinWeight']
		ispumping = socket['IsPumping']
		topump = socket['ToPump']
		water_manual = 0
		
		if (socket["Pump"] == 1):
			
			# decrement days
			if (socket['DaysCnt'] > 0) and (newday):
				socket['DaysCnt'] -= 1
				growbox_db.set_socket(rowid, "DaysCnt", socket["DaysCnt"])
			
			if (ispumping == 0):
				# is it time to water?
				if (now.hour == socket["Time"]) and (now.minute == 0):
					# using load cell
					if (wsid != 0):
						if (len(weight_sensors) >= wsid):
							# get sensor
							weight = weight_sensor_data[wsid]
							if (minw > weight) and (minw > 0):
								growbox_db.set_socket(rowid, "IsPumping", 1)
								ispumping = 1
								topump = ml
					
					# not using load cell
					elif (socket['DaysCnt'] == 0):
						growbox_db.set_socket(rowid, "DaysCnt", socket['Days'])
						growbox_db.set_socket(rowid, "IsPumping", 1)
						ispumping = 1
						topump = ml
						
				# start manual watering, without pausing
				if (socket["Time"] == -2):
					growbox_db.set_socket(rowid, "Time", -1)
					growbox_db.set_socket(rowid, "IsPumping", 1)
					ispumping = 1
					topump = ml
					water_manual = 1
				
			# water plants every 0 and 10,20,30 of the hour (config.PumpPause)
			if (ispumping == 1) and (fr > 0) and (((now.minute % config.PumpPause) == 0) or (water_manual == 1)):
			
				water_plants.append([ rowid, gpio, ml, topump, fr, water_manual, name ])
				sockets_load[rowid] = load
						
	
		################################################
		# Switch Socket On or Off, except for Pump
		################################################
		if switch_on:
			if (growbox_db.get_main() == 1):
				growbox.relay_set(gpio, 1)
				sockets_load[rowid] = load
		elif (socket['IsPumping'] == 0):
			growbox.relay_set(gpio, 0)

################################################
# Update Power Meter
################################################
growbox_db.insert_power(dt, sockets_load[1],sockets_load[2],sockets_load[3],sockets_load[4],sockets_load[5],sockets_load[6],sockets_load[7],sockets_load[8])

################################################
# capture new image
################################################
take_image()
if (log_image != "OFF"):
	save_image(log_image)

################################################
# Watering, Start Pumping
# Pump [config.PumpInc]ml with [config.PumpPause]s pause, should have a positive effect on how the earth absorbes the water
# Pumping is threaded so all pumps can start at the same time
################################################
def water_plant(socketid, gpio, ml, towater, fr, water_manual):

	pump_now = config.PumpInc
	if (water_manual == 1):
		pump_now = ml
	if towater < pump_now:
		pump_now = towater
	towater -= pump_now
	
	slp = int(pump_now/fr) + 1 # add one second for pump to start till water comes from the tubes

	growbox.relay_set(gpio, 1)
	sleep(slp)
	growbox.relay_set(gpio, 0)
			
	grow_db = growbox.db()
	grow_db.connect()
	
	# finished watering
	if (towater <= 0):
		towater = 0
		grow_db.set_socket(socketid, "IsPumping", 0)
		grow_db.insert_water(dt, socketid, ml)

	# update watering
	grow_db.set_socket(socketid, "ToPump", towater)
	
	grow_db.disconnect()
	
if (growbox_db.get_main() == 1):
	for row in water_plants:
		rowid = row[0]
		gpio = row[1]
		ml = row[2]
		towater = row[3]
		fr = row[4]
		water_manual = row[5]
		name = row[6]

		thread = Thread(target = water_plant, args = (rowid, gpio, ml, towater, fr, water_manual))
		thread.start()
		
#else:
#	raise Exception("Pump: Main Switch Interrupted")
	
################################################
# SQL Close
################################################
growbox_db.disconnect()

#print "Handler: End"
