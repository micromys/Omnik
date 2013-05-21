<?php

	/*	inverter_config file to be used for MySQL database access; see inverter.sql for MySQL table definition	*/

	self::$dbtype		=	'MySQL';				// 	not used here
	self::$host		=	'127.0.0.1';			// 	specify other address if your MySQL is running on another server
	self::$port		=	'3306';				// 	port for remote db access
	self::$database		=	'database';			// 	your database
	self::$dbuser		=	'dbuser';				// 	your userid
	self::$dbpassword	=	'dbpassword';			//	your password
	self::$table		=	'solar_daily_direct';		// 	your table	

?>

