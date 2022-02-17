<?php

$myips = array("::1", "127.0.0.1", "127.0.1.1", "172.26.100.168"); // cyclops
$rachips = array("172.26.100.123"); // blackbird
if (in_array($_SERVER['REMOTE_ADDR'], $myips)) {
  $user="shane";
} else if (in_array($_SERVER['REMOTE_ADDR'], $myips)) {
  $user="rach";
}

$premiumusers = array("shane", "rach");
if (in_array($user, $premiumusers)) {
  $usertype="premium";
} else {
  $usertype="standard";
}

?>
