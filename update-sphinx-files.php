<?php
require('minimisestring.php');

$index_name = $argv[1];

$con = mysqli_connect("localhost","root","oracle","fileindexdb");

mysqli_query($con,"DROP TABLE IF EXISTS ".$index_name."table");

mysqli_query($con,"CREATE TABLE ".$index_name."table ( id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,text VARCHAR(400),minimised VARCHAR(400) NOT NULL);");

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

mysqli_query($con,"TRUNCATE TABLE ".$index_name."table");

print "listing files\n";
$files = shell_exec("/home/shane/scripts/sphinx listf ".$index_name);

$separator = "\n";
$line = strtok($files, $separator);

while ($line !== false) {
  print "inserting into database: " . $line . "\n";
  if (!is_dir($line)) {
    mysqli_query($con,"INSERT INTO ".$index_name."table ( text,minimised ) VALUES ( '$line','".minimisestring($line)."' )");
  }
  $line = strtok( $separator );
}

mysqli_close($con);
?>