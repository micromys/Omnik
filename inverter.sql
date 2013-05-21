/*
	MySQL Table definitions
	
	a. solar_daily_direct table used for inserting data from inverter
	b. TRIGGER : inverter_insert does:
		1. update new records with YY.MM,DD,HH indexed fields for fast access later with queries
		2. adds latest inverter data to solar_power so you can easily access the most actual data from the database, instead of asking to inverter
		
		This trigger can be omitted
		
	c.	solar_power : table used for latest inverter data queried from the inverter, you may omit this table if you also omit the trigger too

*/

-- ----------------------------
-- Table structure for `solar_daily_direct`
-- ----------------------------

DROP TABLE IF EXISTS `solar_daily_direct`;

CREATE TABLE `solar_daily_direct` (
  `ID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `Datum` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'timestamp',
  `YY` smallint(4) DEFAULT NULL COMMENT 'Year',
  `MM` smallint(2) DEFAULT NULL COMMENT 'Month',
  `DD` smallint(2) DEFAULT NULL COMMENT 'Day',
  `HH` smallint(2) DEFAULT NULL COMMENT 'Hour',
  `Inverter` varchar(32) NOT NULL COMMENT 'Inverter ID',
  `vpv1` decimal(7,2) NOT NULL DEFAULT '0.00',
  `vpv2` decimal(7,2) NOT NULL DEFAULT '0.00',
  `vpv3` decimal(7,2) NOT NULL DEFAULT '0.00',
  `ipv1` decimal(7,2) NOT NULL DEFAULT '0.00',
  `ipv2` decimal(7,2) NOT NULL DEFAULT '0.00',
  `ipv3` decimal(7,2) NOT NULL DEFAULT '0.00',
  `vac1` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT 'Voltage AC-side',
  `vac2` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT 'Voltage AC-side',
  `vac3` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT 'Voltage AC-side',
  `iac1` decimal(7,2) NOT NULL DEFAULT '0.00',
  `iac2` decimal(7,2) NOT NULL DEFAULT '0.00',
  `iac3` decimal(7,2) NOT NULL DEFAULT '0.00',
  `pac1` decimal(7,2) NOT NULL DEFAULT '0.00',
  `pac2` decimal(7,2) NOT NULL DEFAULT '0.00',
  `pac3` decimal(7,2) NOT NULL DEFAULT '0.00',
  `fac` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT 'Frequency',
  `temperature` decimal(7,2) NOT NULL DEFAULT '0.00' COMMENT 'Temperature',
  `time` varchar(24) DEFAULT NULL,
  `todaykWh` decimal(9,2) NOT NULL DEFAULT '0.00' COMMENT 'PV Today',
  `totalkWh` decimal(9,2) NOT NULL DEFAULT '0.00' COMMENT 'PV Total',
  PRIMARY KEY (`ID`),
  KEY `Datum` (`Datum`) USING BTREE,
  KEY `YYMMDDHH` (`YY`,`MM`,`DD`,`HH`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2019 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for `solar_power`
-- ----------------------------
DROP TABLE IF EXISTS `solar_power`;

CREATE TABLE `solar_power` (
  `ID` char(4) NOT NULL DEFAULT 'OOAK',
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pac1` decimal(7,2) NOT NULL DEFAULT '0.00',
  `todaykWh` decimal(9,2) NOT NULL DEFAULT '0.00',
  `totalkWh` decimal(9,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of solar_power : ADD THIS RECORD !!!!
-- ----------------------------
INSERT INTO `solar_power` VALUES ('OOAK', '2012-12-31 12:00:00', '1.00', '1.00', '1.00');

DROP TRIGGER IF EXISTS `inverter_insert`;
DELIMITER ;;
CREATE TRIGGER `inverter_insert` BEFORE INSERT ON `solar_daily_direct` FOR EACH ROW begin
set new.YY=Year(curdate());
set new.MM=Month(curdate());
set new.DD=Day(curdate());
set new.HH=Hour(curtime());
update solar_power set pac1=new.pac1, todaykWh=new.todaykWh, totalkWh=new.totalkWh where ID='OOAK';
end
;;
DELIMITER ;


