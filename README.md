PHP Class and Sample
====================
PHP Class to open and to access the Omnik Inverter and return 'readable' data in different ways.


inverter_class.php
------------------
This class is the engine

1. to identify your inverter using the WIFI ip-address, WIFI tcpport (8899) and the WIFI serialnumber
2. to connect to and read from the inverter
3. to process the data received from the inverter
4. to (optionally) insert data into a mysql database
5. to display the databuffer 
6. to the read the current power generated

The class is fully documented inline

inverter_sample.php
-------------------
Sample php script to access and read data from the inverter

The script is self-explaining, comment out some lines to test different parts

inverter_layout.html
------------
description of databuffer returned from inverter

inverter_config.php
-------------------
Contains MySQL DB parameters to make a DB connection

Change the parameters according to your MySQL environment

Do not change the filename
 
inverter.sql
------------
Contains the database table and trigger definitions

Check the inline documentation before defining the tables
