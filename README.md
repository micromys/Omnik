PHP Class and Sample
====================
PHP Class to open and to access the Omnik Inverter and return 'readable' data in different ways.

CAUTION : Whenever you read from the wifi module you might run into timeouts conditions when also data is sent to the portal website, especially when the portal is performing poor

Changes 2013-06-05
------------------
1. BEFORE IMPLEMENTING THE NEW CLASS PLEASE UPDATE THE DATABASE TABLE, SEE inverter.sql
2. Implemented totalHours (changed from htotalkWh), totalHours is hours the inverter is running since last reset
3. database table solar_power extended and changed from INNODB to MEMORY (to speed up access)
4. trigger on solar_daily_direct changed to update solar_power table

Changes 2013-05-31
------------------
1. BEFORE IMPLEMENTING THE NEW CLASS PLEASE UPDATE THE DATABASE TABLE, SEE inverter.sql
2. Corrected a problem with pac1,pac2,pac3,fac1,fac2,fac3; pac1 & fac1 (was fac) were correct, pac2,fac2,pac3 & fac3 are now correct too
3. inverter_layout.html modified due to reflect correct data pac and fac
4. htotalkWh added (will be implemented in next version) 
5. database layout changed, fac changed to fac1, fac2 and fac3 added, see inverter.sql
6. class updated to reflect pac and fac changes
7. working on undetermined part in layout

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

inverter_layout.html (HTML format)
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
