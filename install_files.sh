#!/bin/bash -x

echo "### Installing Directories & Files ###"

# /var/www/growbox
echo "-> /var/www/growbox"
sudo rm -r /var/www/growbox
sudo mkdir /var/www/growbox

sudo mkdir /var/www/growbox/tmp

sudo mkdir /var/www/growbox/script

sudo mkdir /var/www/growbox/cam

sudo mkdir /var/www/growbox/archive

# copy files
cd www
cp -vr ./ /var/www/growbox
cd ..

rm /var/www/growbox/\[var-www-growbox\].txt

# apply rights

# pi, www-data takes ownership of /var/www/growbox
# growbox_handler.py can write images & development purposes
sudo chown -R pi:www-data /var/www/growbox
sudo chmod u+rxw,g+rx-w,o-rwx /var/www/growbox
# any new files will have the same permissions, does not count for copied files
sudo chmod g+s /var/www/growbox

sudo chown -R pi:www-data /var/www/growbox/tmp
sudo chmod u+rxw,g+rx-w,o-rwx /var/www/growbox/tmp

sudo chown -R pi:www-data /var/www/growbox/script
sudo chmod u+rxw,g+rx-w,o-rwx /var/www/growbox/script

sudo chown -R pi:www-data /var/www/growbox/cam
sudo chmod u+rxw,g+rwx,o-rwx /var/www/growbox/cam

sudo chown -R pi:www-data /var/www/growbox/archive
sudo chmod u+rxw,g+rwx,o-rwx /var/www/growbox/archive

mv -f /var/www/growbox/.htpasswd /etc/apache2/.htpasswd
mv -f /var/www/growbox/000-default.conf /etc/apache2/sites-enabled/000-default.conf

echo "-> /home/pi/bin/growbox"

# /home/pi/bin/growbox
sudo mkdir /home/pi/bin
sudo chown -R pi:pi /home/pi/bin
sudo chmod u+rxw,g+rx-w,o-rwx /home/pi/bin
sudo chmod g+s /home/pi/bin

sudo rm -r /home/pi/bin/growbox
sudo mkdir /home/pi/bin/growbox

cd bin
cp -vr ./ /home/pi/bin/growbox
cd ..

rm /home/pi/bin/growbox/\[home-pi-bin-growbox\].txt

# apply rights
sudo chown -R pi:pi /home/pi/bin/growbox
sudo chmod u+rxw,g+rx-w,o-rwx /home/pi/bin/growbox
sudo chmod g+s /home/pi/bin/growbox


echo "-> /home/pi/DB"

# Database
sudo mkdir /home/pi/DB

sudo rm /home/pi/DB/Growbox.db
sudo sqlite3 -init ./growbox_tables.sql /home/pi/DB/Growbox.db .quit

# Give www-data write access to DB/Growbox.db
sudo chown -R pi:www-data /home/pi/DB/
sudo chmod g+rwx,o-rwx /home/pi/DB
sudo chmod g+rw,o-rwx /home/pi/DB/Growbox.db

# HTML access for user pi
# Only for Debug purposes
# https://www.raspberrypi.org/forums/viewtopic.php?t=155067

echo "### Installing Directories & Files - DONE ###"


