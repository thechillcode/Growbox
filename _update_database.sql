-- Database DB/GrowBox.db

BEGIN TRANSACTION;

-- GrowBox

-- SOCKETS
-- GPIO: 14, 15, 18, 23, 24, 25, 08, 07

/*
UPDATE Sockets SET GPIO=14 WHERE rowid=1;
UPDATE Sockets SET GPIO=15 WHERE rowid=2;
UPDATE Sockets SET GPIO=18 WHERE rowid=3;
UPDATE Sockets SET GPIO=23 WHERE rowid=4;
UPDATE Sockets SET GPIO=24 WHERE rowid=5;
UPDATE Sockets SET GPIO=25 WHERE rowid=6;
UPDATE Sockets SET GPIO=08 WHERE rowid=7;
UPDATE Sockets SET GPIO=07 WHERE rowid=8;
*/

-- AirSensors
UPDATE AirSensors SET name='Box', gpio=17 WHERE rowid=1;
--UPDATE AirSensors SET name='Box2', gpio=4 WHERE rowid=2;

-- Cameras
-- Usable Image size: 640x480, 800x600, 1024x768, 1280x720
UPDATE Cameras SET enabled=1, usb='', hres=800, vres=600, rotation=180, fps=60, brightness=50, contrast=0, awb='tungsten' WHERE rowid=1;
--UPDATE Cameras SET enabled=1, usb='v4l2:/dev/video0', hres=800, vres=600, rotation=0, fps=0, brightness=10, contrast=0, awb='' WHERE rowid=2;

-- WeightSensors
UPDATE WeightSensors SET name='Plant1', data=22, clk=10, cal=103, offset=-24892 WHERE rowid=1;
-- Scale Rectangular Shape
--UPDATE WeightSensors SET name='Rect', data=22, clk=10, cal=104, offset=-7600 WHERE rowid=1;
-- Scale Mow, square 3 holes
--UPDATE WeightSensors SET name='Plant1', data=9, clk=11, cal=108, offset=-54000 WHERE rowid=1;


-- Commands:
--DELETE FROM WeightSensorData WHERE weight<=1000;

COMMIT;
