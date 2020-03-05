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
----------------------------------------------
DROP TABLE IF EXISTS Sockets;
CREATE TABLE Sockets (Name TEXT NOT NULL, Active INTEGER NOT NULL, GPIO INTEGER NOT NULL, Load INTEGER NOT NULL, -- Socket
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
						Pump INTEGER NOT NULL, Days INTEGER NOT NULL, Time INTEGER NOT NULL, MilliLiters INTEGER NOT NULL, FlowRate REAL NOT NULL, DaysCnt INTEGER NOT NULL,
						-- IsPumping
						IsPumping INTEGER NOT NULL
						);
						
							--           #Swi  #Timer   #Interval      #TMax #Tmin #HMax #HMin #THPw #Pump               #IsPumping
INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);

INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);
INSERT INTO Sockets VALUES ('', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0, 0, 0);

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
-- Cameras
----------------------------------------------
DROP TABLE IF EXISTS Cameras;
CREATE TABLE Cameras(enabled INT NOT NULL, usb int NOT NULL, rotation int NOT NULL);
INSERT INTO Cameras VALUES (0, 0, 0);
INSERT INTO Cameras VALUES (0, 0, 0);
INSERT INTO Cameras VALUES (0, 0, 0);
INSERT INTO Cameras VALUES (0, 0, 0);

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

COMMIT;
