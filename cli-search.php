<?php

// $index is the virtual index. because the index represented by the index 
// buttons actually searches a list of indices

// search backwards starting from haystack length characters from the end
function startsWith($haystack, $needle) {
  return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

if (isset($_GET['s'])) {
  // i want to be able to do exact string search
  $s = trim(strtolower($_GET['s']));
  //$s = trim($_GET['s']);

  if (startsWith($s, ';')) {
    $paras = explode(';', $s);
    $c = count($paras);
    $index = $paras[1];
    if ($c > 1) {
      $qpath = $paras[2];
    }
    if ($c > 2) {
      $q = $paras[3];
    }
  } else {
    $index = '';
    $qpath = '';
    $q = $s;
  }

  $_GET['q'] = $q;
  $_GET['index'] = $index;
  $_GET['qpath'] = $qpath;
} else {
  $q = isset($_GET['q'])?$_GET['q']:'';
  // i want to be able to do exact string search
  $q = trim(strtolower($q));
  //$q = trim($q);

  $qpath = isset($_GET['qpath'])?$_GET['qpath']:'';
  $qpath = trim(strtolower($q));

  $index = isset($_GET['index'])?$_GET['index']:'';
}

/*
 * Make this accept a 'super query' only.
 *
 * Remove html
 *
 * Return only IDs that match the query.
 *
 * Make another script outside of this one that takes a list of queries and
 * outputs results for each query on a single line preceeded by their index.
 * */

function stripnewlines($s) {
  return(str_replace(array("\r", "\n"), '', $s));
}

$CONF = array();

$CONF['sphinx_host'] = 'localhost';
$CONF['sphinx_port'] = 9312;

include("premium.php");

#$qpath = $_GET['qpath'];
#
#if (isset($_GET['index'])) {
#  $index = $_GET['index'];
#} else if (isset($index)) {
#    $_GET['index'] = $index;
#} else {
#  $index = '';
#  $_GET['index'] = '';
#}
#
#if ($index == '') {
#  $index = 'packages';
#}

require("conf.php");

if ($user == "shane") {
  $indextypes = array_merge($indextypes, $shaneindextypes);
}

foreach(array_keys($indextypes) as $istr)
{
  if(strpos($istr, $index) !== false)
  {
    $index = $istr;
    break;
  }
}

if ($user != "shane" && in_array($index, $shaneindextypes)) {
  $index = 'packages';
}
$indexlist = $indextypes[$index]['indiceslist'];

function append_index($s) {
  return implode(' ', array_map(function($str){return $str.'index';}, explode(' ', $s)));
}

$CONF['mysql_table'] = "${index}table";
$CONF['sphinx_index'] = append_index($index); // can also be a list of indexes, "main, delta"

#Change this to FALSE on a live site!
$CONF['debug'] = TRUE;

#maximum number of results - should match sphinxes max_matches. default 1000
$CONF['max_matches'] = 1000;

$projects = file('projects.txt');
$projects = array_filter(array_map('trim', $projects));

$thirdpartypackages = file('thirdparty.txt');
$thirdpartypackages = array_filter(array_map('trim', $thirdpartypackages));

$db = mysqli_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password']) or die("ERROR: unable to connect to database");
mysqli_select_db($db, $CONF['mysql_database']) or die("ERROR: unable to select database");

#$q = isset($_GET['q'])?$_GET['q']:'';
#$_GET['q'] = trim(strtolower($q));
#$q = $_GET['q'];

#print append_index($index)."\n";
#print "$q\n";
#print "$qpath\n";

require("sphinxapi.php");
?>
<?php

/*
 * Don't support this until I have time to develop the GUI
 * http://sphinxsearch.com/docs/current/extended-syntax.html
 * http://sphinxsearch.com/docs/current/matching-modes.html
 */
if (strpos($q,'~') === 0) {
  $q = preg_replace('/^\~/','',$q);
  $mode = SPH_MATCH_EXTENDED2;
} else {
  $mode = SPH_MATCH_ALL;
}

//Connect to sphinx, and run the query
$cl = new SphinxClient();
$cl->SetServer($CONF['sphinx_host'], $CONF['sphinx_port']);
$cl->SetSortMode(SPH_SORT_EXTENDED, $indextypes[$index]['sort']);
$cl->SetMatchMode($mode);

$path_ids = array();
$CONF['mysql_query'] = 'SELECT id,text FROM '.$CONF['mysql_table'].' WHERE id IN ($ids)';
if (!empty($qpath)) {
  $CONF['path_query'] = 'SELECT id FROM '.$CONF['mysql_table'].' WHERE minimised COLLATE UTF8_GENERAL_CI LIKE "%$qpathsub%"';
  $qpathsub = preg_replace('/ +/','%',$qpath);
  $path_sql = str_replace('$qpathsub',mysqli_real_escape_string($db,$qpathsub),$CONF['path_query']);
  $result = mysqli_query($db,$path_sql) or die($CONF['debug']?("ERROR: mysql query failed: ".mysqli_error($db)):"ERROR: Please try later");
  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
      $path_ids[] = $row['id'];
    }
  }
}

if (!empty($qpath) && count($path_ids) > 0) {
  $cl->setSelect('*,IN(id,'.implode(',',$path_ids).') as myint');
  $cl->setFilter('myint', array(1));
}

if (!empty($q)) {
  $res = $cl->Query($q, $CONF['sphinx_index']);

  $thematches = (!empty($res) && array_key_exists('matches', $res) && is_array($res["matches"]))?$res['matches']:null;
  $nmatches = $thematches?count($res['matches']):0;
  if ($nmatches > 0) {
    $ids = array_keys($res["matches"]);
  }
} else if (!empty($qpath) && count($path_ids) > 0) {
  $ids = $path_ids;
} else {
  $ids = null;
}

function getLongestWord($q) {
  $q = preg_replace('/[^\w~\|\(\)\^\$\?"\/=-]+/',' ',trim(strtolower($q)));

  $words  = explode(' ', $q);
  $words = array_filter(array_map('trim', $words));

  $longestWordLength = 0;
  $longestWord = '';

  foreach ($words as $word) {
    if (strlen($word) > $longestWordLength) {
      $longestWordLength = strlen($word);
      $longestWord = $word;
    }
  }
  return $longestWord;
}

#print $index.':'.implode(' ', $ids).':'.getLongestWord($q);
print $index.':'.implode(' ', $ids).':'.$q;

#foreach($ids as $id)
#{
#  print $id."\n";
#}

// should have $ids now
// print them out
?>
