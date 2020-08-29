# https://github.com/tatobari/hx711py
# stripped for growbox usage
# 

import RPi.GPIO as GPIO
import time

class HX711:

	def __init__(self, dout, pd_sck, gain=128):
		self.PD_SCK = pd_sck

		self.DOUT = dout

		GPIO.setmode(GPIO.BCM)
		GPIO.setup(self.PD_SCK, GPIO.OUT)
		GPIO.setup(self.DOUT, GPIO.IN)

		self.GAIN = 0

		# The value returned by the hx711 that corresponds to your reference
		# unit AFTER dividing by the SCALE.
		self.REFERENCE_UNIT = 1

		self.OFFSET = 0

		self.lastVal = int(0)

		self.set_gain(gain)
		time.sleep(0.1)

		
	def convertFromTwosComplement24bit(self, inputValue):
		return -(inputValue & 0x800000) + (inputValue & 0x7fffff)

	
	def is_ready(self):
		return GPIO.input(self.DOUT) == 0

	
	def set_gain(self, gain):
		#if gain is 128:
		self.GAIN = 1
		
		if gain is 64:
			self.GAIN = 3
		elif gain is 32:
			self.GAIN = 2

		GPIO.output(self.PD_SCK, False)

		# Read out a set of raw bytes and throw it away.
		self.readRawBytes()

		
	def get_gain(self):
		if self.GAIN == 1:
			return 128
		if self.GAIN == 3:
			return 64
		if self.GAIN == 2:
			return 32

		# Shouldn't get here.
		return 0
		

	def readNextBit(self):
		# Clock HX711 Digital Serial Clock (PD_SCK).  DOUT will be
		# ready 1us after PD_SCK rising edge, so we sample after
		# lowering PD_SCL, when we know DOUT will be stable.
		GPIO.output(self.PD_SCK, True)
		GPIO.output(self.PD_SCK, False)
		value = GPIO.input(self.DOUT)

		# Convert Boolean to int and return it.
		return int(value)


	def readNextByte(self):
		byteValue = 0

		# Read bits and build the byte from top, or bottom, depending
		# MSB bit mode.
		for x in range(8):
			byteValue <<= 1
			byteValue |= self.readNextBit()

		# Return the packed byte.
		return byteValue 
		

	def readRawBytes(self):
		# Wait until HX711 is ready for us to read a sample.
		cnt = 0
		while not self.is_ready():
			cnt = cnt + 1
			if (cnt == 30):
				raise Exception('hx711.py: Device not ready')
			time.sleep(0.1)
			pass

		# Read three bytes of data from the HX711.
		firstByte  = self.readNextByte()
		secondByte = self.readNextByte()
		thirdByte  = self.readNextByte()

		# HX711 Channel and gain factor are set by number of bits read
		# after 24 data bits.
		for i in range(self.GAIN):
		   # Clock a bit out of the HX711 and throw it away.
		   self.readNextBit()
		
		# MSB, return an orderd list of raw byte
		return [firstByte, secondByte, thirdByte]


	def read_long(self):
		# Get a sample from the HX711 in the form of raw bytes.
		dataBytes = self.readRawBytes()

		# Join the raw bytes into a single 24bit 2s complement value.
		twosComplementValue = ((dataBytes[0] << 16) |
							   (dataBytes[1] << 8)  |
							   dataBytes[2])
	
		# Convert from 24bit twos-complement to a signed value.
		signedIntValue = self.convertFromTwosComplement24bit(twosComplementValue)

		# Record the latest sample value we've read.
		self.lastVal = signedIntValue

		# Return the sample value we've read from the HX711.
		return int(signedIntValue)

	
	def read_average(self, times=10):
		# Make sure we've been asked to take a rational amount of samples.
		if times <= 0:
			times = 1

		# Get an uneven amount of times
		if (times % 2) == 0:
			times += 1
		
		vals = []
		for x in range(times):
			vals.append(self.read_long())
			time.sleep(0.01)

		vals.sort()
		
		middle = vals[int(times/2)+1]
		delta = abs(middle*0.1)
		lower = middle - delta
		upper = middle + delta
		
		avg = 0
		count = 0
		for val in vals:
			if (lower <= val) and (val <= upper):
				avg += val
				count += 1
				
		# Return the mean of remaining samples.
		return int(avg/count)

	# Compatibility function, uses channel A version
	def get_value(self, times=10):
		return self.read_average(times)

	# Compatibility function, uses channel A version
	def get_weight(self, times=10):
		return (self.read_average(times)/self.REFERENCE_UNIT) - self.OFFSET

	# Sets tare for channel A for compatibility purposes
	def tare(self, times=100):
		self.OFFSET = -(self.read_average(times)/self.REFERENCE_UNIT)

	# sets offset for channel A for compatibility reasons
	def set_offset(self, offset):
		self.OFFSET = offset

	def get_offset(self):
		return self.OFFSET

	def set_reference_unit(self, reference_unit):
		if (reference_unit < 1):
			reference_unit = 1;
		self.REFERENCE_UNIT = reference_unit

	def get_reference_unit(self):
		return self.REFERENCE_UNIT
	
	def power_down(self):
		# Cause a rising edge on HX711 Digital Serial Clock (PD_SCK).  We then
		# leave it held up and wait 100 us.  After 60us the HX711 should be
		# powered down.
		GPIO.output(self.PD_SCK, False)
		GPIO.output(self.PD_SCK, True)

		time.sleep(0.01)


	def power_up(self):
		# Lower the HX711 Digital Serial Clock (PD_SCK) line.
		GPIO.output(self.PD_SCK, False)

		# Wait 100 us for the HX711 to power back up.
		time.sleep(0.01)

		# HX711 will now be defaulted to Channel A with gain of 128.  If this
		# isn't what client software has requested from us, take a sample and
		# throw it away, so that next sample from the HX711 will be from the
		# correct channel/gain.
		if self.get_gain() != 128:
			self.readRawBytes()


	def reset(self):
		self.power_down()
		self.power_up()


# EOF - hx711.py
