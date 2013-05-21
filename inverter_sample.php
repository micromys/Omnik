<?php

	/* 
		sample : 	php-script returns data,
				put into a mysql tables, 
				returns an array into $p and
				var_dumps the entire object
				
				comment out to test different parts
	 */

	require_once('inverter_class.php');

	$ip='192.168.1.2';		// your ip-address
	$port=8899;			// do not change, unless you have other info about the port
	$sn=602123456;			// your serial-number

	$inverter	=	new Inverter($ip,$port,$sn);		//	initialize class and create identification string
	if ($inverter->read()===true)								//	read data from socket (inverter)
	{
		if($inverter->insert()===false)							//	store in db if true
		{
			echo $inverter->error;
		}
	}
	else
	{
		echo "$inverter->errorcode : $inverter->error";			// split databuffer into structure
	}
		
	//$p=$inverter->power("Array");
	//$inverter->message("Status",$p['Inverter'], $p['pac1'].' - '.$p['todaykWh'].' - '.$p['totalkWh']);

	//$p=$inverter->power();
	//echo $p;

	echo $inverter->displaybuffer();
				
	//var_dump($inverter);		
	
?>
