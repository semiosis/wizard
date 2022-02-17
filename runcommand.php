<?php
/*
 * input: command, stdin, working directory
 * output: stdout
 * */
function runcommand($cmd, $stdin, $wd) {
  // 0 == stdin
  // 1 == stdout
  // 2 == stderr
  $descriptorspec = array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w"));
  $env = array();

  $pipes = null;
  $process = proc_open($cmd, $descriptorspec, $pipes, $wd, $env);

  if (is_resource($process)) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout

    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $return_value = proc_close($process);
    return $stdout;
  }
  return "";
}
?>