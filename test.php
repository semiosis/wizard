<?php
function getsvg($partgv) {
  $descriptorspec = array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w"));
  $env = array();

  $pipes = null;
  $process = proc_open('/var/www/croogle/getsvg.sh', $descriptorspec, $pipes, '/media/www/croogle', $env);

  if (is_resource($process)) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout

    fwrite($pipes[0], $partgv);
    fclose($pipes[0]);

    $fullsgv = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $return_value = proc_close($process);
    return $fullsgv;
  }
  return "";
}            
getsvg("");
?>