# Growbox Aux Functions

import config
import Adafruit_DHT

import sys
import RPi.GPIO as GPIO
import time

from picamera import PiCamera, Color
from time import sleep

import datetime

import os

from hx711 import HX711

# Helper to check if switch should be on
def switch_on(on, off, hour):
	if (on == off):
		return True
	if (on < off):
		if (hour >= on) and (hour < off):
			return True
	if (on > off):
		if not ((hour >= off) and (hour < on)):
			return True
	return False

# Helper to get log image on start, one hour before stop and in the middle, middle seems to be int by default
def log_image(on, off, hour):
	if (on == hour):
		return True
	
	loff = off - 1
	if (loff < 0):
		loff = loff + 24
	if (loff == hour):
		return True
		
	diff = off - on
	if (diff < 0):
		diff = 24 + diff
	middle = on + (diff/2)
	if (middle > 23):
		middle = middle - 24
	if (middle == hour):
		return True
	
	return False
		
	
# Camera capture
# info optional string to be included in Image print
def camera_capture(file, rotation, text=None):
	try:
		camera = PiCamera()
		
		#camera.resolution = (2592, 1944)
		#camera.resolution = (1920, 1080)
		#camera.resolution = (1280, 720)
		camera.resolution = (720, 576)
		camera.rotation = rotation
		camera.start_preview()
		
		camera.brightness = 60
		camera.contrast = 0
		
		#https://picamera.readthedocs.io/en/release-1.13/api_camera.html
		# set white balance red and blue
		#camera.awb_mode = "off"
		#camera.awb_gains = (1, 1)
		
		camera.awb_mode = "fluorescent"
		#camera.awb_mode = "sunlight"
		#camera.awb_mode = "flash"
		#camera.exposure_mode = "snow"
		
		if text is not None:
			#camera.annotate_text_size = 32
			camera.annotate_text_size = 20
			camera.annotate_background = Color('black')
			camera.annotate_foreground = Color('white')
			camera.annotate_text = text
		sleep(5)
		#camera.capture(file, quality=50)
		camera.capture(file)
		camera.stop_preview()
		camera.close()
	except:
		pass
		#print("Unexpected error:", sys.exc_info()[0])

def camera_capture_mode(file, brightness=None, contrast=None, awb=None, exposure=None, effect=None, text=None):
	camera = PiCamera()
	#camera.resolution = (2592, 1944)
	#camera.resolution = (1920, 1080)
	#camera.resolution = (1280, 720)
	camera.resolution = (720, 576)
	camera.rotation = 0
	camera.start_preview()
	if brightness is not None:
		camera.brightness = brightness
	if contrast is not None:
		camera.contrast = contrast
	if awb is not None:
		camera.awb_mode = awb
	if exposure is not None:
		camera.exposure_mode = exposure
	if effect is not None:
		camera.image_effect = effect
	if text is not None:
		camera.annotate_text = text
	sleep(5)
	camera.capture(file)
	#camera.capture(file, quality=70)
	camera.stop_preview()
	camera.close()
	
# USB Camera capture function
# sudo apt-get install fswebcam
def camera_capture_usb(file, rotation, text=None):
	cmd = "fswebcam --quiet --delay 5 -r 720x576 --top-banner --rotate " + str(rotation)
	if text is not None:
		cmd = cmd +  " --title \"" + text + "\""
	os.system(cmd + " " + file)

# AirSensor Read
def airsensor_read(sensor, gpio):
	humidity, temperature = Adafruit_DHT.read_retry(sensor, gpio)
	if (humidity is not None and temperature is not None):
		if humidity >= 0 and humidity <=100 and temperature >= -20 and temperature <= 50:
			return humidity, temperature
	return None, None
	
# Set Relay, (gpio, value 1:on, 0:off)
def relay_set(gpio, val):
	pin = gpio
	if pin is not None:
		GPIO.setwarnings(False)
		GPIO.setmode(GPIO.BCM)
		GPIO.setup(pin, GPIO.OUT)
		if val == 1:
			GPIO.output(pin, GPIO.LOW) # on
		else:
			GPIO.output(pin, GPIO.HIGH) # out
			
# Get Weight from HX711
def get_weight(data, clk, cal, offset):
	GPIO.setwarnings(False)
	hx = HX711(data, clk)
	# I've found out that, for some reason, the order of the bytes is not always the same between versions of python, numpy and the hx711 itself.
	# Still need to figure out why does it change.
	# If you're experiencing super random values, change these values to MSB or LSB until to get more stable values.
	# There is some code below to debug and log the order of the bits and the bytes.
	# The first parameter is the order in which the bytes are used to build the "long" value.
	# The second paramter is the order of the bits inside each byte.
	# According to the HX711 Datasheet, the second parameter is MSB so you shouldn't need to modify it.
	hx.set_reading_format("MSB", "MSB")

	# HOW TO CALCULATE THE REFFERENCE UNIT
	# To set the reference unit to 1. Put 1kg on your sensor or anything you have and know exactly how much it weights.
	# In this case, 92 is 1 gram because, with 1 as a reference unit I got numbers near 0 without any weight
	# and I got numbers around 184000 when I added 2kg. So, according to the rule of thirds:
	# If 2000 grams is 184000 then 1000 grams is 184000 / 2000 = 92.
	#hx.set_reference_unit(113)
	hx.set_reference_unit_A(cal)
	
	hx.set_offset_A(offset)

	hx.reset()
	val = int(hx.get_weight_A(3))
	#val = hx.read_long()
	hx.power_down()
	if val < 0:
		val = 0
	return val
	
	