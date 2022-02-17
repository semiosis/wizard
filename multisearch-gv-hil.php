<?php

// Forked from /media/www/html/croogle/multisearch-gv.php
// This should output more information (i.e. hil-node number and not the query but

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

function getidfromdataline($dataline) {
  return trim(preg_replace('/^([^,]+),.*$/','\1',$dataline));
}

function getnodefromdataline($dataline) {
  return trim(preg_replace('/^([^,]+),([^,]*),.*$/','\2',$dataline));
}

function getpathfromdataline($dataline) {
  return trim(preg_replace('/^([^,]+),([^,]*),\(.*\)$/','\3',$dataline));
}

$buildtonode = array();

//print_r($rawresults);
$results = array();
$indicesdata = array();
foreach($rawresults as $rawresult) {
  //print $rawresult;
  $index = trim(preg_replace('/^([^:]+):.*$/','\1',$rawresult));
  if (!array_key_exists($index, $indicesdata)) {
    $indicesdata[$index] = array();
    $indicesdata[$index]['rawfids'] = array();
    $indicesdata[$index]['rawhilnodes'] = array();
  }
  $matchdatalines = explode(' ', preg_replace('/^([^:]+):([^:]*):.*$/','\2',$rawresult));
  $matches = array_map('getidfromdataline', $matchdatalines);
  $rawhilnodes = array_map('getnodefromdataline', $matchdatalines);
  $paths = array_map('getpathfromdataline', $matchdatalines);
  //$matches = array_filter(array_map('trim', $matches));
  $query = trim(preg_replace('/^([^:]+):([^:]*):(.*)$/','\3',$rawresult));
  $result = array("index" => $index, "query" => $query, "matches" => $matches, "paths" => $paths);
  $indicesdata[$index]['rawfids'] = array_merge($indicesdata[$index]['rawfids'], $matches);
  $indicesdata[$index]['rawhilnodes'] = array_merge($indicesdata[$index]['rawhilnodes'], $rawhilnodes);
  $results[] = $result;
}

foreach($indicesdata as $indexname => &$indexdata) {
  $indexdata['fids'] = $indexdata['rawfids'];
  sort($indexdata['fids'], SORT_NUMERIC);
  $indexdata['fids'] = array_values(array_unique($indexdata['fids'], SORT_NUMERIC)); // array_values renumbers the array
  $indexdata['fids'] = array_filter($indexdata['fids']); // sometimes one of these is empty and that breaks the graph. maybe it's when there is no match for a query.
  $indexdata['paths'] = array();
  //print_r($indexdata['fids']);
  //if (count($indexdata['fids']) > 0) {
    $mysql_query = 'SELECT minimised FROM '.$indexname.'table WHERE id IN ($ids)';
    //print $mysql_query;
    //print implode(' ',$indexdata['fids']);
    //print count($indexdata['fids']);
    $sql = str_replace('$ids',implode(',',$indexdata['fids']),$mysql_query);
    $result = mysqli_query($db,$sql); // or die($CONF['debug']?("ERROR: mysql query failed: ".mysqli_error($db)):"ERROR: Please try later");
    if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
        $indexdata['paths'][] = $row['minimised'];
      }
    }
  //}

  $indexdata['builds'] = preg_replace('/^.+\/(\d+)\/.+$/', '\1', $indexdata['paths']);
  $indexdata['fid_to_build'] = array_combine($indexdata['fids'], $indexdata['builds']);
  $indexdata['fid_to_hilnode'] = array_combine($indexdata['rawfids'], $indexdata['rawhilnodes']);
  $indexdata['fid_to_path'] = array_combine($indexdata['fids'], $indexdata['paths']);

  $indexdata['build_to_id'] = array();
  foreach ($indexdata['fid_to_build'] as $fid => $build) {
    if (!array_key_exists($build, $indexdata['build_to_id'])) {
      $indexdata['build_to_id'][$build] = array();
    }
    $indexdata['build_to_id'][$build][] = $fid;
  }
  $indexdata['build_to_hilnode'] = array();
  foreach ($indexdata['fid_to_hilnode'] as $fid => $hilnode) {
    $build = $indexdata['fid_to_build'][$fid];
    $indexdata['build_to_hilnode'][$build] = $hilnode;
  }
  $indexdata['builds_and_event'] = array();
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

$gvs .= "node [fontname=Ubuntu, fontcolor=\"#ffaa66\", style=\"filled\", width=0, height=0, shape=box, color=gray, fillcolor=\"#333333\", concentrate=true, peripheries=\"0\"]\n";
//foreach($results as $resultskey => &$errortype) { // can't do this because of https://bugs.php.net/bug.php?id=29992
foreach($results as $resultskey => $errortype) {
  $gvs .= "error_".$resultskey." [label=\"".substr(str_replace(' ', "\n", $errortype['query']), 0, 30)."\l\" shape=box]\n";
  foreach($errortype['matches'] as $matchnum => $match) {
    $index = $errortype['index'];
    $build = $indexdata['fid_to_build'][$match];
    //print $index;
    $indicesdata[$index]['builds_and_events'][$build][] = $resultskey;
  }
  $results[$resultskey]['gv_nodes'] = array();
}

$gvs .= "node [fontname=Ubuntu, fontcolor=\"#8888ff\", style=\"filled,rounded\", width=0, height=0, shape=box, color=gray, fillcolor=\"#dddddd\", concentrate=true, peripheries=\"0\"]\n";
//$gvs .= "ranksep=0.2\n";

$gvs .= "jenkins_buildnum"." [label=\"Autotest Build\l\", shape=box, fontcolor=\"#000000\", style=\"filled\", width=0, height=0, shape=box, color=gray, fillcolor=\"#ffcc00\", concentrate=true, peripheries=\"0\"]\n";
foreach($indicesdata as $indexname => &$indexdata) {
  $oldbuild = "buildnum";
  foreach(array_reverse($indexdata['builds_and_events'], true) as $build => $events) {
    //print_r($events);
    $gvs .= "{rank=same\n";
    ////print "<br/>"."\n";
    $gvs .= $indexname."_".$build." [label=\"$build\l\", style=\"filled\", fontcolor=\"#ffffff\", fillcolor=\"#8888ff\" shape=box URL=\"http://jenkins/job/ebl_verify_autotest/".$build."/\"]\n";
    foreach($events as $eventi => $event) {
      $nodename = $indexname."_".$build."_".$event;
      $results[$event]['gv_nodes'][] = $nodename;
      $hilnode = $indexdata['build_to_hilnode'][$build];
      $path = $results[$event]['paths'][$eventi];
      //$label = "e$event hil$hilnode b$build";
      $label = "hil $hilnode";
      $gvs .= $nodename." [label=\"$label\l\" shape=box URL=\"http://jenkins/job/ebl_verify_autotest/".$build."/\"]\n";
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

/* These are the edges for the error event columns */
foreach($results as $resultskey => $errortype) {
  $errornode = "error_".$resultskey;
  $lastnode = $errornode;
  foreach($errortype['gv_nodes'] as $nodename) {
    if ($lastnode == $errornode) {
      $gvs .= $lastnode." -> ".$nodename." [dir=back]\n";
    } else {
      $gvs .= $lastnode." -> ".$nodename." [arrowhead=none]\n";
    }
    $lastnode = $nodename;
  }
}

$gvs .= "}\n";
//print $gvs;
print getsvg($gvs);
