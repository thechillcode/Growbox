##############################
# Setup Helper
##############################

import os
from crontab import CronTab

##############################
# CRONTAB
##############################

growbox_reboot = 0
growbox_boot = 0
growbox_run = 0

cron = CronTab(user="root")

for job in cron:
	if job.comment == "growbox_reboot":
		growbox_reboot = 1
	if job.comment == "growbox_boot":
		growbox_atreboot = 1
	if job.comment == "growbox_run":
		growbox_run = 1

if (growbox_reboot == 0):
	job = cron.new(command="sudo reboot", comment="growbox_reboot")
	job.hour.on(0)
	job.minute.on(5)		
	cron.write()

if (growbox_boot == 0):
	job = cron.new(command="python /home/pi/bin/growbox/growbox_boot.py >> /var/log/growbox.log 2>&1", comment="growbox_boot")
	job.every_reboot()
	cron.write()
	
if (growbox_run == 0):
	job = cron.new(command=" python /home/pi/bin/growbox/growbox_run_handler.py >> /var/log/growbox.log 2>&1", comment="growbox_run")
	job.minute.every(1)
	cron.write()

##############################
# etc/fstab
##############################

file = open('/etc/fstab', 'r')
Lines = file.readlines() 
file.close()

grow_mount = 0
# Strips the newline character 
for line in Lines:
	if (line.strip() == "# growbox"):
		grow_mount = 1

if (grow_mount == 0):
	file = open('/etc/fstab', 'a')
	file.write("# growbox\n")
	file.write("tmpfs /var/www/growbox/tmp tmpfs defaults,nofail,noatime,nosuid,uid=1000,gid=33,mode=0755,size=10m 0 0\n")
	file.close()
	os.system("sudo mount -a")