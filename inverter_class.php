<?php

	/* 	______________________________________________________________________________________________________________________________________________________
		
		a.	RUNNIG THIS SCRIPT(s) IS AT YOUR OWN RISC/RESPONSIBILITY, THERE ARE NO GUARANTEES THAT THIS WILL ALSO WORK IN YOUR ENVIRONMENT
		b.	YOU MAY FREELY CHANGE and/or DISTRIBUTED THIS SET OF SCRIPTS BUT DO NOT FORGET TO THANK ALL OTHERS WHO PARTIPATED TO MAKE THIS WORK
		c.	ALTHOUGH I WROTE THIS SERIES OF SCRIPTS, I USED IDEAS, ALGORITHMES, etc FROM OTHER DEVELOPERS, GOOGLE, GITHUB, etc
		d.	SPECIAL CREDITS to https://github.com/Woutrrr/Omnik-Data-Logger (Wouter van de Zwan) for the python version
		
		e.	IF YOU PLAN TO SWAP YOUR WIFI MODULE BE VERY CAREFULL : 
			1. 	SWITCH OFF THE POWER/CURRENT AT THE DC- AND AC-side
			2. 	REPLACE THE MODULE
			3. 	CHECK THE ANTENNA
			4.	SWITCH ON DC AND AC SIDE AND CHECK THE LEDS ON THE WIFI MODULE
			5.	RE-CONFIGURE THE WIFI MODULE
			6.	DO NOT FORGET TO UPDATE THE OMNIK-PORTAL website with your setting (new serial-number, but same inverter ID)
			7.	YOU SHOULD NOT LOSE ANY DATA on the OMNIK_PORTAL website
			
		f.	THESE SCRIPTS ARE TESTED AND RUNNING IN :
			1. 	Windows 8 with Apache 2, PHP 5.3 and MySQL 5.1
			2.	UNIX DEBIAN WHEEZY with Apache2 (and Lighttpd), PHP 5.4 and MySQL 5.5.30 on
				a RASPBERRY PI model B 512Mb,
				a i386 PC running DEBIAN SQUEEZE with Apache2, PHP 5.4, MySQL 5.5.30 and MariaDB 5.5
				a CUBIEBOARD 1Ghz, 1GB with DEBIAN Wheezy, Apache2, PHP 5.4, MySQL 5.5.30
			3. 	you can add the script to your unix crontab either via php cli or 
			4.	w3m (w3m http://localhost/yourwww/inverter_sample.php >>output.html), where output (if any) is added to output.html (w3m is a text-based browser that runs from the command-line)
			5.	crontab for windows : consider installing http://www.kalab.com/freeware/pycron/pycron.htm (looks like unix crontab) or use your task-scheduler
			6.	Attention : Reading the 8899 port in intervals of less than 2 or 3 seconds might cause a buffer returned with only x'FF' values or other undetermined values. Normally 5 seconds or higher works fine
			7.	WARNING : Because your wifi-module also sends data to the OMNIK-PORTAL website it simotimes generates time-outs when read-request and send-request are at the same time. 
			8.	MAC OS NOT EVEN TESTED, NO PLANS FOR IT AT ALL
			9. 	Not tested in IIS
			
		g.	FEEDBACK may be send via GITHUB	(English please)
		
		h.	I'M NOT RESPONSIBLE FOR ANY HARM DONE BY THIS SCRIPT TO YOUR HARDWARE and /or SOFTWARE CONFIGURATION;
			ALL CLAIMS WILL BE REJECTED/DECLINED;
		
		It is presumed that sufficient knowledge of Windows, Unix, Apache, IIS, PHP, MYSQL is available, please refer to the appropriate websites for more information 
		
		@COPYRIGHT : V.H. Lemoine
		______________________________________________________________________________________________________________________________________________________

		Info		:	This class is designed to access a OMNIK WIFI module using it's local LAN Ip-address, port 8899 and its serial-number
				
		Warning	: 	Currently only WIFI module's starting with serial-number 602xxxxxxx are supported, it also should have port 8899, use nmap to check
					Also check the firmware of WIFI-module; use the latest firmware to ensure that your wifi-module does not lose it's ip-settings
					
					This class is NOT TESTED for IPv6 addresses
					
		CAUTION	:	Supplying incorrect ipaddress, port and/or serialnumber might cause 'hanging' the program (until php limits are reached), 
					be cautious in supplying correct input parameters

		Syntax 	:	$o = new Inverter('ip-ipadress',8899,serial-number[,inverterID])
		
					ip-addres		-> 	ip-address of WIFI Module
					8899			-> 	tcp-port of WIFI module (module should have port 80 and 8899)
					serial-number	-> 	serial-number of WIFI module
					inverterID		->	optional Inverter ID 16 chars and is used to compare it with the data read
							
		Functions	:	__construct()	->	initialize class, i.e. $o = new Inverter('ip-adress',8899,serial-number[,inverterID]);		
								->	the __construct function creates a unique identication string to determine the Inverter
								
					read()		->	creates socket connection using ip-adress and port
								->	sends identification string to the inverter
								->	reads data from the inverter
								->	process data into several values using the data() function
								->	Important : stream_socket_client is used, fsockopen can also be used but has less options. Both are working in UNX ans WINDOWS
								->	DO NOT USE socket_create, socket_open, it is not working properly in UNIX
								
					data()		->	splits the datastream received into several parts; an array is filled as $o->PV['Datum'],$o->PV['Inverter'],$o->PV['vpv1'],etc
								->	the $o->PV array is also converted in JSON-format and can be returned as $o->JSON
					
					insert()		->	call $o->insert() to insert the data into MySQL database table, see invert_config.php and inverter.sql, database and table must exist!
					
					power()		->	if called as $o->power() or $o->power("JSON") it returns the JSON string, if called as $o->power("ARRAY") it returns the $o->PV array as an array
					
					displaybuffer()	->	returns HTML formatted table containing the databuffer in string and hex format
					
					message()	->	returns html formatted table containing error information;
						
					other		-> 	str2hex(), hex2str(),str2dec() : see inline comments
					
					errors		->	each function returns true or false, $o->errorcode;$o->error and $o->Method contains error information
					
		databuffer layout			->	see inverter_layout.html
		______________________________________________________________________________________________________________________________________________________	

	*/

	class Inverter
	{
		public		 	$bytessent		=	0;		// bytes sent to inverter
		public		 	$bytesreceived		=	0;		// bytes from from inverter
		public			$databuffer		=	'';		// databuffer contain data received from inverter
		public			$errorcode		=	0;		// errorcode
		public			$error			=	'';		// error (text)
		public			$invStr			=	'';		// inverter identification string sent to inverter
		public			$invStrLen		=	0;		// length of identification string
		public 			$ipaddress		=	'';		// IPV4 ip address
		public 			$tcpport			=	0;		// tcp port 8899 
		public 			$serialnumber		=	0;		// inverter serialnumber of wifi card
		public			$PV;							// PV structure create from databuffer
		public			$socket			=	'';		// socket handler
		
		//		see inverter_config.php for setting
		private 	static	$database;					// database 
		private	static	$table;						// table
		private	static	$logtable;						// logtable
		private	static	$host;						// host
		private	static	$port;						// host port of mysql db
		private	static	$dbtype;						// not used
		private	static	$dbuser;						// db user
		private	static	$dbpassword;					// db password
		
		function message()								// echo message
		{
			$html	=	"<style>td {border:1px black solid;font-size:11pt;font-weight:bold;padding:5px;text-align:left}</style><table style='border-collapse:collapse;min-width:50%;'>";
			$html	.=	"<tr><td>Method</td><td>".date('Y-m-d- H:i:s')." - ".$this->Method."</td></tr>";
			$html	.=	"<tr><td>Step</td><td>".$this->step."</td></tr>";
			$html	.=	"<tr><td>Errorcode</td><td>".$this->errorcode."</td></tr>";
			$html	.=	"<tr><td>Error</td><td>".$this->error."</td></tr>";
			$html	.=	"</table>";
			return $html;		
		}
			
		function hex2str($hex)							// convert readable hexstring to chracter string i.e. "41424344" => "ABCD"
		{
			$string='';									// init
			for ($i=0; $i < strlen($hex)-1; $i+=2)				// process each pair of bytes
			{
				$string .= chr(hexdec($hex[$i].$hex[$i+1]));	// pick 2 bytes, convert via hexdec to chr
			}
			return $string;								// return string
		}
		
		function str2hex($string)							// convert readable charatcer string to readable hexstring i.e. "ABCD"=> "41424344"
		{
			$hex='';									// init
			for ($i=0; $i < strlen($string); $i++)				// process all bytes in string
			{
				$hex	.=	substr('0'.dechex(ord($string[$i])),-2);	// prepend 0 if hexvalue is 0 thru f, so 'd' = > '0d', '4e' => '04e'; now take last byte i.e. '0d', '4e'
			}
			return $hex;								// return hex string
		}
		
		function str2dec($string) 							// convert string to decimal	i.e. string = 0x'0101' (=chr(1).chr(1)) => dec = 257
		{
			$str=strrev($string);							// reverse string 0x'121314'=> 0x'141312' 
			$dec=0;									// init 
			for ($i=0;$i<strlen($string);$i++)				// foreach byte calculate decimal value multiplied by power of 256^$i
			{
				$dec+=ord(substr($str,$i,1))*pow(256,$i);		// take a byte, get ascii value, muliply by 256^(0,1,...n where n=length-1) and add to $dec
			}	
			return $dec;								// return decimal
		}
		
		private function clearError($method)				// initalize error block
		{
			$this->Method=$method;
			$this->error='';
			$this->errorcode=0;
			$this->step='';		
		}

		public function __construct($ipaddress='',$tcpport=8899,$serialnumber=-1,$inverterID='')
		{
			$this->clearError(__METHOD__);
			
			if ($ipaddress!='' and $serialnumber!=-1 and $tcpport>0)	// check if IPv4 address, port and s/n are supplied
			{		
				$this->ipaddress	=	$ipaddress;
				$this->tcpport		=	$tcpport;
				$this->serialnumber	=	$serialnumber;
				$this->inverterID	=	$inverterID;
				
				/* 	build inverter identification string to be sent to the inverter 
				
				the identification string is build from several parts. 
				
				a. The first part is a fixed 4 char string: 0x68024030;
				b. the second part is the reversed hex notation of the s/n twice; 
				c. then again a fixed string of two chars : 0x0100; 
				d. a checksum of the double s/n with an offset; 
				e. and finally a fixed ending char : 0x16;
				
			    	*/
    	
				$hexsn	=	dechex($this->serialnumber);					// convert serialnumber to hex
				$cs		=	115;										// offset, not found any explanation sofar for this offset
				$tmpStr	=	'';
	
				for ($i=strlen($hexsn);$i>0;$i-=2)							// in reverse order of serial; 11223344 => 44332211 and calculate checksum
				{
					$tmpStr	.=	substr($hexsn,$i-2,2);					// create reversed string byte for byte	
					$cs		+=	2*ord($this->hex2str(substr($hexsn,$i-2,2)));	// multiply by 2 because of rule b and d		
				}
		
				$checksum	=	$this->hex2str(substr(dechex($cs),-2));		// convert checksum and take last byte
				
				// now glue all parts together : fixed part (a) + s/n twice (b) + fixed string (c) + checksum (d) + fixend ending char
				$this->invStr		=	"\x68\x02\x40\x30".$this->hex2str($tmpStr.$tmpStr)."\x01\x00".$checksum."\x16";	// create inverter ID string
				$this->invStrLen	=	strlen($this->invStr);													// get length	
			}
			else
			{
				$this->errorcode=1004;
				$this->error="Init parameters ipaddress : '$ipaddress' and/or tcp-port : '$tcpport' and/or serialnumber : '$serialnumber' are incorrect";
				return false;
			}	
			
			return true;		
		}
		
		public function displaybuffer()								// for debugging : create html formatted table that display the databuffer in str and hex format by offset, 
		{
			$this->clearError(__METHOD__);
			
			$html	=	"<style>td {border:1px black solid;width:30px;padding:5px;text-align:left}</style><table style='border:1px black solid;border-collapse:collapse'>";
			$html	.=	"<tr><td colspan=33 style='text-align:center;font-size:16pt'>Databuffer returned from Inverter at ".$this->PV['Datum']."</td></tr>";
			//$html	.=	"<tr><td colspan=33 style='text-align:center;font-size:12pt'><pre>".$this->databuffer."</pre></td></tr>";
			//$html	.=	"<tr><td colspan=33 style='text-align:center;font-size:12pt'><pre>".$this->str2hex($this->databuffer)."</pre></td></tr>";
			$html	.=	"<tr><td>&nbsp;</td>";
			$c	= 	$this->bytesreceived/16;						// calculate 16 bytes parts
			$c	=	ceil($c);									// ceil it
			
			for ($i=0;$i<16;$i++)
			{
				$html	.=	"<td>$i</td><td>&nbsp;</td>";			// create header block
			}
			
			$html	.=	"</tr>";

			for ($i=0;$i<$c;$i++)									// for each byte in databuffer calculate offset and convert to str and hex
			{
				$z=$i*16;
				$html	.=	"<tr><td>$z</td>";					// create row
				$j=0;
				for ($j=0;$j<16;$j++)								// create cells
				{
					$k=$i*16+$j;
					$html	.=	"<td>".$this->str2hex(substr($this->databuffer,$k,1))."</td><td>".substr($this->databuffer,$k,1)."</td>";
				}
				$html	.=	"</tr>";
			}

			$html	.=	"</table>";
			return $html;										// return result		
		}
		
		private function data()
		{
			$this->clearError(__METHOD__);
			
			$this->PV['Datum'] = date('Y-m-d H:i:s');					// set timestamp, Year, Month, Day, Hour
			$this->PV['Inverter'] = substr($this->databuffer,15,16);		// get inverterID
			
			if ($this->inverterID!='' and $this->PV['Inverter']!=$this->inverterID)
			{
				$this->errorcode=1016;
				$this->error="InverterID: $this->inverterID does not match with returned InverterID: ".$this->PV['Inverter'];
				$this->step="check InverterID";
				return false;
			}
			
			$this->getShort('temperature',31,10);					// get Temperature
			$this->getShort('vpv',33,10,3);							// get VPV
			$this->getShort('ipv',39,10,3);							// get IPV
			$this->getShort('iac',45,10,3);							// get Ampere	
			$this->getShort('vac',51,10,3);							// get Volt Ampere	
			$this->getShort('fac',57,100,3);							// get ...
			$this->getShort('pac',59,1,3);							// get  current Power
			$this->getShort('todaykWh',69,100);						// get EToday in Watt
			$this->getLong('totalkWh',71,10);						// get ETotal in kW
			$this->getLong('totalHours',75,1);						// get Total hours since last reset
			$this->JSON = json_encode($this->PV);					// create JSON string for later (ie. javascript)
			return true;
		}
					
		private function getLong($type='totalkWh',$start=71,$divider=10)				// get Long 
		{
			$this->clearError(__METHOD__);

			$t=floatval($this->str2dec(substr($this->databuffer,$start,4)));				// convert 4 bytes to decimal
			$this->PV["$type"] =$t/$divider;									// return value/divder
			return;		
		}

		private function getShort($type='pac',$start=59,$divider=10,$iterate=0)			// return (optionally repeating) values
		{
			$this->clearError(__METHOD__);

			if ($iterate==0)													// 0 = no repeat, return one value
			{
				$t=floatval($this->str2dec(substr($this->databuffer,$start,2)));				// convert to decimal 2 bytes
				$this->PV["$type"] = ($t==65535) ? 0 : $t/$divider;					// if 0xFFFF return 0 else value/divder		
			}
			else
			{
				$offset=2;
				$type=strtolower($type);
				if ($type=="pac"or $type=="fac")
				{
					$offset=4;
				}
				$iterate=min($iterate,3);										// max iterations = 3
				for ($i=1;$i<=$iterate;$i++)
				{				
					$t=floatval($this->str2dec(substr($this->databuffer,$start+$offset*($i-1),2)));	// convert two bytes from databuffer to decimal
					$this->PV["$type$i"] = ($t==65535) ? 0 : $t/$divider;				// if 0xFFFF return 0 else value/divder
				}
			}
			return;
		}
	
		public function insert()
		{
			$this->clearError(__METHOD__);

			require_once('inverter_config.php'); 												// get db credentials
			@$this->mysqli = new mysqli(self::$host,self::$dbuser,self::$dbpassword,self::$database);		// set resource and connect
			if ($this->mysqli->connect_error)													// if any errors return
			{
				$this->errorcode=$this->mysqli->connect_errno;
				$this->error=$this->mysqli->connect_error;
				$this->step="MySQL - Connect";
				return false;
			}
			
			$sql=$this->mysqli->query("SHOW TABLES LIKE '".self::$table."' ");							// query table existence
			if ($sql->num_rows==0)															// if not generate error and return false;	
			{
				$this->errorcode=1020;
				$this->error=self::$table." does not exist; no data stored";
				$this->step="MySQL - SHOW TABLE";
				return false;
			}

			$columns	= '';
			$values	= '';
			foreach ($this->PV as $key => $value)												// foreach iten in array create column and value
			{
				$columns	.= "$key,";
				$values	.= "'$value',";
			}
			$sql	= "insert into ".self::$table. " (".substr($columns,0,-1).") values (".substr($values,0,-1).")";	// insert in database after stripping last comma in both columns and values	
			$this->mysqli->query($sql);														// execute sql statement

			if ($this->mysqli->errno>0)														// test for errors, return errors if any error > 0
			{
				$this->errorcode=$this->mysqli->errno;
				$this->error=$this->mysqli->error.' - '.$sql;
				$this->step="MYSQL - INSERT";
				return false;
			}
			$this->mysqli->close();															// close db
			return true;
		}
	
		public function power($format="JSON")										// return data from inverter either as JSON string or as array
		{
			$this->clearError(__METHOD__);

			return ($format=="JSON") ? $this->JSON : $this->PV;							// return JSON String if format="JSON" else array		
		}
				
		public function read()													// read data from inverter
		{
			$this->clearError(__METHOD__);

			$f=false;															// init as false;
			
			// stream_socket_client is used, fsockopen can also be used but has less options; DO NOT USE socket_create because it does not work properly under UNIX
			// Both stream_socket_client and fsockopen work on UNIX and WINDOWS (MAC OS not tested!)
			
			$this->socket=@stream_socket_client("tcp://".$this->ipaddress.":".$this->tcpport,$this->errorcode,$this->error, 3);	// setup socket, timeout 3 sec
			
			if ($this->socket===false) 												// if something fails return error message
			{
				$this->step="stream_socket_client";								
			}
			else
			{
				$this->bytessent=fwrite($this->socket, $this->invStr,$this->invStrLen);		// send identication to wifi-module and returns bytes sent
				if ($this->bytessent!==false)										// bytessent is either numeric or false
				{
					$this->databuffer	=	'';									// init databuffer;
					$this->databuffer	=	@fread($this->socket, 128);				// (binary) read data buffer (expected 99 bytes), do not use fgets()
					if ($this->databuffer!==false)
					{
						$this->bytesreceived=strlen($this->databuffer);				// get bytes received length
						if ($this->bytesreceived>90)								// if enough data is returned
						{
							if ($this->data()===true)								// split databuffer into structure
							{
								$f=true;										// ok, ready to return
							}
						}
						else
						{
							$this->errorcode=1008;
							$this->error="Incorrect data (length=$this->bytesreceived) returned; expected 99 bytes";	
							$this->step="databuffer error";
						}
					}
					else
					{
						$this->errorcode=1012;
						$this->error="Error reading data from Inverter";	
						$this->step="fread";
					}
					@fclose($this->socket);										// close socket (ignore warning)
				}
			}	
			return $f;			
		}		
	}
?>
