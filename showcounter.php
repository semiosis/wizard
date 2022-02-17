<?php

/* counter */

//opens countlog.txt to read the number of hits
$datei = fopen("countlog.txt","r");
$count = fgets($datei,1000);
fclose($datei);
echo "$count" ;
echo " visits" ;
echo "\n" ;

?>