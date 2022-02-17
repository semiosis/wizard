<?php

require("runcommand.php");

function getsvg($partgv) {
  return runcommand('/var/www/croogle/getsvg.sh', $partgv, '/var/www/croogle');
}

$CONF['debug'] = TRUE;

$user = '';
require("conf.php");

$db = mysqli_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password']) or die("ERROR: unable to connect to database");
mysqli_select_db($db, $CONF['mysql_database']) or die("ERROR: unable to select database");

//stream_set_blocking(STDIN, false);

$rawresults = array();
while($f = fgets(STDIN)){
  $rawresults[] = $f;
}

//print_r($rawresults);
$results = array();
$indicesdata = array();
foreach($rawresults as $rawresult) {
  //print $rawresult;
  $index = trim(preg_replace('/^([^:]+):.*$/','\1',$rawresult));
  if (!array_key_exists($index, $indicesdata)) {
    $indicesdata[$index] = array();
    $indicesdata[$index]['fids'] = array();
  }
  $matches = explode(' ', preg_replace('/^([^:]+):([^:]*):.*$/','\2',$rawresult));
  $matches = array_filter(array_map('trim', $matches));
  $query = trim(preg_replace('/^([^:]+):([^:]*):(.*)$/','\3',$rawresult));
  $result = array("index" => $index, "query" => $query, "matches" => $matches);
  $indicesdata[$index]['fids'] = array_merge($indicesdata[$index]['fids'], $matches);
  $results[] = $result;
}

foreach($indicesdata as $indexname => &$indexdata) {
  sort($indexdata['fids'], SORT_NUMERIC);
  $indexdata['fids'] = array_values(array_unique($indexdata['fids'], SORT_NUMERIC)); // array_values renumbers the array
  $mysql_query = 'SELECT minimised FROM '.$indexname.'table WHERE id IN ($ids)';
  //print $mysql_query;
  //print implode(' ',$indexdata['fids']);
  //print count($indexdata['fids']);
  $sql = str_replace('$ids',implode(',',$indexdata['fids']),$mysql_query);
  $result = mysqli_query($db,$sql) or die($CONF['debug']?("ERROR: mysql query failed: ".mysqli_error($db)):"ERROR: Please try later");
  $indexdata['paths'] = array();
  
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
      $indexdata['paths'][] = $row['minimised'];
    }
  }

  $indexdata['builds'] = preg_replace('/^.+\/(\d+)\/.+$/', '\1', $indexdata['paths']);
  $indexdata['fid_to_build'] = array_combine($indexdata['fids'], $indexdata['builds']);

  $indexdata['fid_to_path'] = array_combine($indexdata['fids'], $indexdata['paths']);

  $indexdata['build_to_id'] = array();
  foreach ($indexdata['fid_to_build'] as $id => $build) {
    if (!array_key_exists($build, $indexdata['build_to_id'])) {
      $indexdata['build_to_id'][$build] = array();
    }
    $indexdata['build_to_id'][$build][] = $id;
  }
  $indexdata['builds_and_events'] = array();
  foreach($indexdata['builds'] as $build) {
    $indexdata['builds_and_events'][$build] = array();
  }
  //print count($indexdata['paths']);
}

// need an array of builds each containing an array of the kind of failures they 
// get
// resultskey -- the id of the error message
// resultskey -- the id of the error mesag
$gvs = '';
//foreach($results as $resultskey => &$errortype) { // can't do this because of https://bugs.php.net/bug.php?id=29992
foreach($results as $resultskey => $errortype) {
  $gvs .= "error_".$resultskey." [label=\"".substr(str_replace(' ', "\n", $errortype['query']), 0, 30)."\l\" shape=box]\n";
  foreach($errortype['matches'] as $match) {
    $index = $errortype['index'];
    $build = $indexdata['fid_to_build'][$match];
    //print $index;
    $indicesdata[$index]['builds_and_events'][$build][] = $resultskey;;
  }
  $results[$resultskey]['gv_nodes'] = array();
}

foreach($indicesdata as $indexname => &$indexdata) {
  $oldbuild = null;
  foreach(array_reverse($indexdata['builds_and_events'], true) as $build => $events) {
    //print_r($events);
    $gvs .= "{rank=same\n";
    ////print "<br/>"."\n";
    $gvs .= $indexname."_".$build." [label=\"$build\l\" shape=box URL=\"http://jenkins/job/ebl_verify_autotest/".$build."/\"]\n";
    foreach($events as $event) {
      $nodename = $indexname."_".$build."_".$event;
      $results[$event]['gv_nodes'][] = $nodename;
      $gvs .= $nodename." [label=\"$event: $build\l\" shape=box URL=\"http://jenkins/job/ebl_verify_autotest/".$build."/\"]\n";
    }
    $gvs .= "}"."\n";
    if (!$oldbuild == null) {
      $gvs .= $indexname."_".$oldbuild." -> ".$indexname."_".$build." [arrowhead=none]\n";
    }
    $oldbuild = $build;
  }
}

//print_r($indicesdata);

//$results = array_values($results);
//print_r($results);
//print count($results);
//exit();

foreach($results as $resultskey => $errortype) {
  $lastnode = "error_".$resultskey;
  foreach($errortype['gv_nodes'] as $nodename) {
    $gvs .= $lastnode." -> ".$nodename." [arrowhead=none]\n";
    $lastnode = $nodename;
  }
}

$gvs .= "}\n";
//print $gvs;
print getsvg($gvs);
