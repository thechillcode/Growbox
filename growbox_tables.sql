-- Database DB/GrowBox.db

BEGIN TRANSACTION;

----------------------------------------------
-- Air Sensors, HT22 - GPIO
----------------------------------------------
DROP TABLE IF EXISTS AirSensors;
CREATE TABLE AirSensors(name TEXT NOT NULL, gpio INT NOT NULL);
INSERT INTO AirSensors VALUES ('', 0);
INSERT INTO AirSensors VALUES ('', 0);
INSERT INTO AirSensors VALUES ('', 0);
INSERT INTO AirSensors VALUES ('', 0);

----------------------------------------------
-- Air Sensor Readings, Inside the Box, T°C, H%
-- DROP TABLE also drops the indexes or triggers
----------------------------------------------
DROP TABLE IF EXISTS AirSensorData;
CREATE TABLE AirSensorData(dt DATETIME NOT NULL, id INT NOT NULL, temperature REAL NOT NULL, humidity REAL NOT NULL);
CREATE INDEX IDX_AirSen ON AirSensorData (dt, id);
DROP TABLE IF EXISTS AirSensorDataLog;
CREATE TABLE AirSensorDataLog(dt DATETIME NOT NULL, id INT NOT NULL, temperature REAL NOT NULL, humidity REAL NOT NULL);

----------------------------------------------
-- Weight Sensors, data: data gpio, clk: clock
----------------------------------------------
DROP TABLE IF EXISTS WeightSensors;
CREATE TABLE WeightSensors(name TEXT NOT NULL, data INT NOT NULL, clk INT NOT NULL, cal INT NOT NULL, offset INT NOT NULL);
INSERT INTO WeightSensors VALUES ('', 0, 0, 0, 0);
INSERT INTO WeightSensors VALUES ('', 0, 0, 0, 0);
INSERT INTO WeightSensors VALUES ('', 0, 0, 0, 0);
INSERT INTO WeightSensors VALUES ('', 0, 0, 0, 0);

DROP TABLE IF EXISTS WeightSensorData;
CREATE TABLE WeightSensorData(dt DATETIME NOT NULL, id INT NOT NULL, weight INTEGER NOT NULL);
CREATE INDEX IDX_WeightSen ON WeightSensorData (dt, id);

----------------------------------------------
-- Sockets, Relay:
-- rowid is Sockets ID on Relay
-- Control : (0=Switch,1=Timer,2=Interval,3=T,4=H,5=Pump)
-- GPIO: 14, 15, 18, 23, 24, 25, 08, 07
----------------------------------------------
DROP TABLE IF EXISTS Sockets;
CREATE TABLE Sockets (Name TEXT NOT NULL, Active INTEGER NOT NULL, GPIO INTEGER NOT NULL, Load INTEGER NOT NULL, -- Socket
						-- Control
						Control INTEGER NOT NULL,
						
						-- Switch
						Switch INTEGER NOT NULL, State INTEGER NOT NULL,
						-- Timer
						Timer INTEGER NOT NULL, HOn INTEGER NOT NULL, HOff INTEGER NOT NULL,
						-- Interval
						Interval INTEGER NOT NULL, Power INTEGER NOT NULL, PowerCnt INTEGER NOT NULL, Pause INTEGER NOT NULL, PauseCnt INTEGER NOT NULL,
						-- Max Temp
						MaxTemp INTEGER NOT NULL, TMax INTEGER NOT NULL,
						-- Min Temp
						MinTemp INTEGER NOT NULL, TMin INTEGER NOT NULL,
						-- Max Humidity
						MaxHumi INTEGER NOT NULL, HMax INTEGER NOT NULL,
						-- Min Humidity
						MinHumi INTEGER NOT NULL, HMin INTEGER NOT NULL,
						-- Temp, Humidity Power
						THPower INTEGER NOT NULL, THPowerCnt INTEGER NOT NULL,
						-- Pump
						Pump INTEGER NOT NULL, Days INTEGER NOT NULL, Time INTEGER NOT NULL, MilliLiters INTEGER NOT NULL, FlowRate REAL NOT NULL, DaysCnt INTEGER NOT NULL, WSensorID INTEGER NOT NULL, MinWeight INTEGER NOT NULL,
						-- IsPumping
						IsPumping INTEGER NOT NULL, ToPump INTEGER NOT NULL
						);
						
							--            #C #Swi  #Timer   #Interval      #TMax #Tmin #HMax #HMin #THPw #Pump                     #IsPumping #ToPump
INSERT INTO Sockets VALUES ('', 0, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 15, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);

INSERT INTO Sockets VALUES ('', 0, 24, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 25, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);
INSERT INTO Sockets VALUES ('', 0,  8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);
INSERT INTO Sockets VALUES ('', 0,  7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0, 0, 0, 0);

----------------------------------------------
-- Power Meter, load in W, l(x) x=Sockets.rowid
----------------------------------------------
DROP TABLE IF EXISTS PowerMeter;
CREATE TABLE PowerMeter(dt DATETIME NOT NULL, l1 INTEGER NOT NULL, l2 INTEGER NOT NULL, l3 INTEGER NOT NULL,
	l4 INTEGER NOT NULL, l5 INTEGER NOT NULL, l6 INTEGER NOT NULL, l7 INTEGER NOT NULL, l8 INTEGER NOT NULL);
CREATE INDEX IDX_PowerMeter ON PowerMeter (dt);

----------------------------------------------
-- Watering, id = Sockets.rowid,
----------------------------------------------
DROP TABLE IF EXISTS Water;
CREATE TABLE Water(dt DATETIME NOT NULL, id INT NOT NULL, ml INTEGER NOT NULL);
CREATE INDEX IDX_Water ON Water (dt, id);

----------------------------------------------
-- Cameras (Resolution: 640x480)
-- RaspiCam (default)[range]: fps (30)[1,60], brightness (50)[0,100], contrast (0)[0,100],
--	awb ('auto')['auto','sunlight','cloudy','shade','tungsten','fluorescent','incandescent','flash','horizon']
-- RaspiCam settings: (2, 35, 0, 'fluorescent') or (60, 50, 0, 'fluorescent')
-- USB Cam usb: 'v4l2:/dev/video0' 2nd 'v4l2:/dev/video1' ... or '/dev/video0'
-- USB Cam (default): fps (0)[?], brightness (50)[0,100], contrast (0)[0,100]
-- USB Came Note: awb mode has no effect, set to ''
----------------------------------------------
DROP TABLE IF EXISTS Cameras;
CREATE TABLE Cameras(enabled INT NOT NULL, usb TEXT NOT NULL,
	hres int NOT NULL, vres int NOT NULL, rotation int NOT NULL,
	fps int NOT NULL, brightness int NOT NULL, contrast int NOT NULL, awb TEXT NOT NULL);
INSERT INTO Cameras VALUES (0, '', 0, 0, 0, 0, 50, 0, '');
INSERT INTO Cameras VALUES (0, '', 0, 0, 0, 0, 50, 0, '');
INSERT INTO Cameras VALUES (0, '', 0, 0, 0, 0, 50, 0, '');
INSERT INTO Cameras VALUES (0, '', 0, 0, 0, 0, 50, 0, '');

----------------------------------------------
-- Images from Cameras, id = Cameras.rowid
----------------------------------------------
DROP TABLE IF EXISTS Images;
CREATE TABLE Images(dt DATETIME NOT NULL, id INT NOT NULL, filename TEXT NOT NULL);
CREATE INDEX IDX_Images ON Images (dt, id);

----------------------------------------------
-- Config
----------------------------------------------
DROP TABLE IF EXISTS Config;
CREATE TABLE Config(name TEXT NOT NULL, val INTEGER NOT NULL);
-- Light Config
INSERT INTO Config (name, val) VALUES ('Light', 0);
INSERT INTO Config (name, val) VALUES ('LightOn', 0);
INSERT INTO Config (name, val) VALUES ('LightOff', 0);

-- Julien Day Count
INSERT INTO Config (name, val) VALUES ('JulienDay', 0);

-- Archive
-- 0=NotCreating, 1=Creating,
INSERT INTO Config (name, val) VALUES ('Archive', 0);
INSERT INTO Config (name, val) VALUES ('ArchiveDate', 0);

-- Run Handler on next iteration
INSERT INTO Config (name, val) VALUES ('RunHandler', 0);

-- Main Switch
INSERT INTO Config (name, val) VALUES ('Main', 0);

-- Reboot Time
INSERT INTO Config (name, val) VALUES ('Reboot', 0);
INSERT INTO Config (name, val) VALUES ('SetReboot', 0);

COMMIT;
