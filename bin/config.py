# Growbox Config:

import Adafruit_DHT

# Define exeint, execute intervall in minutes !!!! Do not change !!!!
ExeInt = 10

# Define Pump Increment (ml), Pump 200ml per serving
PumpInc = 200
# Define Time between pumps (seconds), gives the earth time to rehydrate very well
PumpPause = 60*30

# Database Path
DB = '/home/pi/DB/Growbox.db'

#'Type': Adafruit_DHT.DHT11,
#'Type': Adafruit_DHT.DHT22,
AirSensorType = Adafruit_DHT.AM2302
