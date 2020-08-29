#!/bin/bash -x

echo "### Installing Software ###"

# Postfix for Cronjob Error reporting
# Setup Local, post: system
#sudo apt-get install postfix

# Update RPI
sudo apt-get update
sudo apt-get upgrade -y

# USB Camera
sudo apt-get install fswebcam -y

# Apache2 + PHP + sqlite3
sudo apt-get install apache2 -y
sudo apt-get install sqlite3 -y
sudo apt-get install php libapache2-mod-php php-sqlite3 php-zip -y

# Python
sudo apt-get install build-essential python-dev python-openssl git -y
sudo apt-get install python-picamera -y
sudo pip install python-crontab

# Adafruit Library, DHT22
cd /tmp
git clone https://github.com/adafruit/Adafruit_Python_DHT.git && cd Adafruit_Python_DHT
sudo python setup.py install

# Raspberry Pi 4, Wiring PI fix
# Currently wiring pi for rpi4 needs to be updated
# git clone https://github.com/WiringPi/WiringPi.git
cd /tmp
git clone https://github.com/WiringPi/WiringPi.git && cd WiringPi
sudo ./build

# Raspberry Pi 3
#sudo apt-get install wiringpi

cd /home/pi

echo "### Installing Software - DONE ###"
