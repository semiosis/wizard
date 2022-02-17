<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

if (isset($_GET['s'])) {
  $s = $_GET['s'];
  //$s = str_replace('%3B',';',$s);
  $s = preg_replace('/ OR /',' | ',$s);
  $s = trim(strtolower($s));
  $sbarstr = $s;

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

  // need to do at least both of these
  //$q = $sbarstr;
  $_GET['q'] = $q;
  $_GET['index'] = $index;
  $_GET['qpath'] = $qpath;
} else {
  $q = isset($_GET['q'])?$_GET['q']:'';
  $q = preg_replace('/ OR /',' | ',$q);
  $q = preg_replace('/[^\w.~\|\(\)\^\$\?"\/=-]+/',' ',trim(strtolower($q)));

  $qpath = isset($_GET['qpath'])?$_GET['qpath']:'';
  $qpath = preg_replace('/ OR /',' | ',$qpath);
  $qpath = preg_replace('/[^\w.~\|\(\)\^\$\?"\/=-]+/',' ',trim(strtolower($qpath)));

  //$_GET['qpath'] = $qpath; // same for qpath ?
  $index = isset($_GET['index'])?$_GET['index']:'';

  $sbarstr = ';'.$index.';'.$qpath.';'.$q;
  if (startsWith($sbarstr,';;;')) {
    $sbarstr = $q;
  }
}

$page = isset($_GET['page'])?$_GET['page']:'';
$pagesize = isset($_GET['pagesize'])?$_GET['pagesize']:'8';
$_GET['pagesize'] = $pagesize; // this is including search.php works

include("premium.php");

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

?>
<!DOCTYPE html>
<html>
<head>
  <title>Croogle - RTC Search Engine</title>
  <meta name="description" content="" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="jquery-ui.css" />
  <link rel="stylesheet" href="nv.d3.css" />
  <link rel="stylesheet" href="jquery.contextMenu.css" />
  <link title="Croogle: RTC Search Engine" type="application/opensearchdescription+xml" rel="search" href="croogle.xml" />
<?php
if ($usertype == "premium") {
  print "<link rel=\"stylesheet\" href=\"shane.css\" />";
}
?>
  <script src="js/jquery-1.12.4.min.js" type="text/javascript">></script>
  <script src="js/jquery-ui.min.js" type="text/javascript">></script>
  <script src="js/jquery-migrate-1.2.1.js" type="text/javascript">></script>
  <script src="js/jquery.ui.position.min.js" type="text/javascript">></script>
  <script src="js/jquery.contextMenu.js" type="text/javascript">></script>
  <!-- this is for histogram.js -->
  <!-- <script src="http://d3js.org/d3.v4.0.0-alpha.45.min.js" type="text/javascript"></script> -->
  <script src="js/d3.min.js" type="text/javascript"></script><!-- novus needs this one -->
  <script src="js/nv.d3.min.js" type="text/javascript"></script>
  <script src="js/script.js" type="text/javascript">></script>
  <script src="js/tartan.js" type="text/javascript">></script>
  <script src="js/underscore.js" type="text/javascript">></script>
</head>
<body>
<script src="js/histogram2.js" type="text/javascript">></script>
<div id="crooglelogo"></div>
<div id="hitcounter">
<?php
if ($user == "shane") {
  include("showcounter.php");
} else {
  include("counter.php");
}

?>
</div>
<div id="toprow">
<input id="superbar" onclick="changequery()" onchange="changequery()" onkeyup="changequery()" name="superbarjs" type="text" value="<?= $sbarstr ?>"/>
<a id="cls" href="javascript:void(0);" onclick="searchselect()">Select</a>
<form action="javascript:void(0);" method="get" id="search">
  <input id="q" style="display:none" name="q" type="text" value="<?= $q ?>"/>
  <input id="qpath" style="display:none" name="qpath" type="text" value="<?= $qpath ?>"/>
  <a id="permalink" alt="clear" href="?"><div class='rainbow'><span class='rainbow'>C R O O G L E</span></div></a>
  <input id="pagenum" style="display:none" type="text" name="page" value="<?= $page ?>"/>
  <input id="pagesizenum" style="display:none" type="text" name="pagesize" value="<?= $pagesize ?>"/>
  <input id="qtype" style="display:none" type="text" name="qtype" value=""/>
  <input id="indextype" style="display:none" type="text" name="index" value="<?= $index ?>"/>
  <input style="display:none" type="submit" id="submitbtn" value="Search"/>
</form>
</div>
<div id='queryhelp'><span>Query Syntax: &nbsp;plain text &nbsp;OR &nbsp;<em>;</em>index<em>;</em>path<em>;</em>content</span></div>
<form class="getcrooglesh" method="get" action="croogle.sh">
<button type="submit">&#8450;&#8477;&#120134;&#120134;&#120126;&#120131;&#120124;.sh for vim/eclipse links</button>
<!--<a href="javascript:void(0);" onclick="window.external.AddSearchProvider('http://croogle/croogle.xml');">Get Search Plugin</a>-->
</form>
<div id="resultsbg">
<div id="results">
<?php
if (!empty($q) || !empty($qpath)) {
include('search.php');
}
?>
</div>
</div>
<?php
if ($user == "shane") {
  print "<div id='user'>Shane Mulligan</div>";
}
?>
</body>
</html>