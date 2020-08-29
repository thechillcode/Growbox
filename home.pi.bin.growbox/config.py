# Growbox Config:

import Adafruit_DHT

# Define Watering Increment in (ml), Water/Pump 200ml per serving
PumpInc = 200
# Define Time between incremental watering (minutes), to make sense should be = 10,20,30
PumpPause = 30

# Database Path
DB = '/home/pi/DB/Growbox.db'

#'Type': Adafruit_DHT.DHT11,
#'Type': Adafruit_DHT.DHT22,
AirSensorType = Adafruit_DHT.AM2302

# Define exeint, execute intervall in minutes !!!! Do not change !!!!
ExeInt = 10

images_path = "/var/www/growbox/cam/"
