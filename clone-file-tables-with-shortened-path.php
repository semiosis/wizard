<?php
require("conf.php");

// get the ids matched by path
$db = mysqli_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password']) or die("ERROR: unable to connect to database");
mysqli_select_db($db, $CONF['mysql_database']) or die("ERROR: unable to select database");

// Go over each table. For each table, create a new table.

//$CONF['mysql_table'] = "${index}table";
?>