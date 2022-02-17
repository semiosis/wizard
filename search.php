<div id="fileresults">
<?php

require("runcommand.php");

function stripnewlines($s) {
  return(str_replace(array("\r", "\n"), '', $s));
}

function isBadUrl($url) {
  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

  /* Get the HTML or whatever is linked in $url. */
  $response = curl_exec($handle);

  /* Check for 404 (file not found). */
  $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
  curl_close($handle);
  return $httpCode == 404;
}

$CONF = array();

$CONF['sphinx_host'] = 'localhost';
$CONF['sphinx_port'] = 9312;

include("premium.php");

/*
  'path' => array(
    'title' => 'Path',
    'indiceslist'=> 'crownfiles'
  ),
  'public' => array(
    'title' => 'Public',
    'indiceslist'=> 'crownfiles'
  ),
 * */

$qpath = $_GET['qpath'];

if (isset($_GET['index'])) {
  $index = $_GET['index'];
} else if (isset($index)) {
    $_GET['index'] = $index;
} else {
  $index = '';
  $_GET['index'] = '';
}

if ($index == '') {
  $index = 'packages';
}

require("conf.php");

/*if ($user == "shane") {
  $indextypes = array_merge($indextypes, $shaneindextypes);
}*/

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
//$index = $indextypes[$index]['indiceslist'];
$indexlist = $indextypes[$index]['indiceslist'];

function append_index($s) {
  return implode(' ', array_map(function($str){return $str.'index';}, explode(' ', $s)));
}

$CONF['mysql_table'] = "${index}table";
$CONF['sphinx_index'] = append_index($index); // can also be a list of indexes, "main, delta"

#can use 'excerpt' to highlight using the query, or 'asis' to show description as is.
$CONF['body'] = 'excerpt';

#the link for the title (only $id) placeholder supported
$CONF['link_format'] = '/page.php?page_id=$id';

#Change this to FALSE on a live site!
$CONF['debug'] = TRUE;

if (isset($_GET['page'])) {
  $page = $_GET['page'];
} else if (isset($page)) {
    $_GET['page'] = $page;
} else {
  $page = '';
  $_GET['page'] = '';
}

if (isset($_GET['qtype'])) {
  $qtype = $_GET['qtype'];
} else if (isset($qtype)) {
    $_GET['qtype'] = $qtype;
} else {
  $qtype = '';
  $_GET['qtype'] = '';
}

/*
if (isset($_GET['page_size'])) {
  $page_size = $_GET['page_size'];
} else if (isset($page_size)) {
    $_GET['page_size'] = $page_size;
} else {
  $page_size = '';
  $_GET['page_size'] = '';
}
print 'thepage_size: '.$page_size;
 */

#How many results per page
#$CONF['page_size'] = 37;
#$CONF['page_size'] = 35;
#good for without grep
#$CONF['page_size'] = 30;
#$CONF['page_size'] = 15;
if ($qtype == "change") {
  true;
  $CONF['page_size'] = 8;
  //$CONF['page_size'] = 40;
} else {
  //$CONF['page_size'] = 8;
  if (isset($_GET['pagesize'])) {
    $CONF['page_size'] = $_GET['pagesize'];
  }
}

#maximum number of results - should match sphinxes max_matches. default 1000
$CONF['max_matches'] = 1000;

$projects = file('projects.txt');
$projects = array_filter(array_map('trim', $projects));

$thirdpartypackages = file('thirdparty.txt');
$thirdpartypackages = array_filter(array_map('trim', $thirdpartypackages));

#print_r($projects);

#$projects = array("infosphere", "3dsensing");
#print_r($projects);

######################
#mysql query to fetch results, needs `id`, `title` and `body` columns in the final result.
#$ids is replaced by the list of ids
#this query can be as arbitary complex as required - but mysql has be able to run it quickly

#DO NOT include an order by (but if use GROUP BY, put ORDER BY NULL) - the order of the results doesnt matter

#TIP can also do :: CONCAT(description,' Category:',category) AS body :: for example


$db = mysqli_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password']) or die("ERROR: unable to connect to database");
mysqli_select_db($db, $CONF['mysql_database']) or die("ERROR: unable to select database");

//Sanitise the input
$q = isset($_GET['q'])?$_GET['q']:'';
$_GET['q'] = $q;

$q = preg_replace('/ OR /',' | ',$q);

// do not make lowercase because I want to be able to do exact string search
$q = preg_replace('/[^\w~\|\(\)\^\$\?"\/=-]+/',' ',trim(strtolower($q)));
//$q = preg_replace('/[^\w~\|\(\)\^\$\?"\/=-]+/',' ',trim($q));

require("sphinxapi.php");

#print($CONF['mysql_query']);

#might need to put in path to your file

if ($_GET['qtype'] == "change") {
  $liclass = '';
} else {
  $liclass = 'trans';
}

$pathwords  = explode(' ', $qpath);
$pathwords = array_filter(array_map('trim', $pathwords));

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

//Display the HTML search form
?>
<!--<div id="query"><?php echo htmlentities($q); ?></div>-->
<?php

//Choose an appropiate mode (depending on the query)
$mode = SPH_MATCH_ALL;
//$mode = SPH_MATCH_EXTENDED;
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
$CONF['mysql_query'] = 'SELECT id,text,minimised FROM '.$CONF['mysql_table'].' WHERE id IN ($ids)';
if (!empty($qpath)) {
  // Keep in mind: it's possible to use regex within mysql select (for path)
  // http://dev.mysql.com/doc/refman/5.7/en/regexp.html#operator_regexp
  $CONF['path_query'] = 'SELECT id FROM '.$CONF['mysql_table'].' WHERE minimised COLLATE UTF8_GENERAL_CI LIKE "%$qpathsub%"';
  //Run the Mysql Query
  $qpathsub = preg_replace('/ +/','%',$qpath);
  //$qpathsub = implode('%',str_split($qpathsub));
  $path_sql = str_replace('$qpathsub',mysqli_real_escape_string($db,$qpathsub),$CONF['path_query']);
  $result = mysqli_query($db,$path_sql) or die($CONF['debug']?("ERROR: mysql query failed: ".mysqli_error($db)):"ERROR: Please try later");
  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
      $path_ids[] = $row['id'];
    }
  }
}

if (!empty($qpath) && count($path_ids) > 0) {
  //$cl->setSelect('*,IN(id,'.implode(',',range(1,100000)).') as myint');
  $cl->setSelect('*,IN(id,'.implode(',',$path_ids).') as myint');
  //$cl->setSelect("*,IN(id) as myint");
  $cl->setFilter('myint', array(1));
}

if (!empty($q) || !empty($qpath)) {
  //produce a version for display
  $qo = $q;
  if (strlen($qo) > 64) {
    $qo = '--complex query--';
  }

  if (!empty($_GET['page'])) {
    $currentPage = intval($_GET['page']);
    if (empty($currentPage) || $currentPage < 1) {$currentPage = 1;}

    $currentOffset = ($currentPage -1)* $CONF['page_size'];

    if (!empty($q) && $currentOffset > ($CONF['max_matches']-$CONF['page_size'])) {
      die("Only the first {$CONF['max_matches']} results accessible");
    }
  } else {
    $currentPage = 1;
    $currentOffset = 0;
  }

  if (!empty($q)) {
    $cl->SetLimits($currentOffset,$CONF['page_size']); // current page and number of results
    $res = $cl->Query($q, $CONF['sphinx_index']);

    $thematches = (!empty($res) && array_key_exists('matches', $res) && is_array($res["matches"]))?$res['matches']:null;
    $nmatches = $thematches?count($res['matches']):0;

    if (empty($res)) {
      print "<pre class=\"results\" id='queryfailed'>";
      print "Query failed: -- please try again later.\n";
      if ($CONF['debug'] && $cl->GetLastError())
        print "<br/>Error: ".$cl->GetLastError()."\n\n";
      print "</pre>";
    } else {
      if ($CONF['debug'] && $cl->GetLastWarning())
        print "<br/>WARNING: ".$cl->GetLastWarning()."\n\n";
      $query_info = "<span class='greytext'>Retrieved <span class='bluetext'>$nmatches</span> of <span class='bluetext'>$res[total_found]</span> matches in $res[time] seconds</span>";

      $resultCount = $res['total_found'];
      $numberOfPages = ceil($res['total']/$CONF['page_size']);
    }

    if ($nmatches > 0) {
      $ids = array_keys($res["matches"]);
    } else {
      print "<pre class=\"results\" id='nothingrelated'>No exact matches found within ".$indextypes[$index]['humanname'].".</pre>";
    }
  } else if (!empty($qpath) && count($path_ids) > 0) {
    // this should be true, but just to clarify.
    $ids = array_slice($path_ids, $currentOffset, $CONF['page_size']);
    $nmatches = count($path_ids);
    $resultCount = $nmatches;
    $numberOfPages = ceil($nmatches/$CONF['page_size']);
  }

  $cl_indices = new SphinxClient();
  $cl_indices->SetServer($CONF['sphinx_host'], $CONF['sphinx_port']);
  #$cl_indices->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
  $cl_indices->SetSortMode(SPH_SORT_EXTENDED, $indextypes[$index]['graphsort']);
  $cl_indices->SetMatchMode($mode);
  $cl_indices->SetLimits(0,100000);

  $indicesdiv = '';
  $indicesdiv .= "<div id='indices'>";
  foreach ($indextypes as $t => $n) {
    $path_ids_indexbtn = array();

    $ftable = str_replace('index', '', $n['indiceslist']).'table';
    if (!empty($qpath)) {
      $index_path_query = 'SELECT id FROM '.$ftable.' WHERE minimised COLLATE UTF8_GENERAL_CI LIKE "%$qpathsub%"';

      //Run the Mysql Query
      // Dont do this again. It only had to be done once.
      //$qpathsub = preg_replace('/ +/','%',$qpath);
      //$qpathsub = implode('%',str_split($qpathsub));
      //print "$qpathsub";
      $path_sql = str_replace('$qpathsub',mysqli_real_escape_string($db,$qpathsub),$index_path_query);
      $result = mysqli_query($db,$path_sql) or die($CONF['debug']?("ERROR: mysql query failed: ".mysqli_error($db)):"ERROR: Please try later");
      if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
          $path_ids_indexbtn[] = $row['id'];
        }
      }
    }

    //$cl_indices->resetFilters();
    if (!empty($qpath) && count($path_ids_indexbtn) > 0) {
      //$cl->setSelect('*,IN(id,'.implode(',',range(1,100000)).') as myint');
      $cl_indices->setSelect('*,IN(id,'.implode(',',$path_ids_indexbtn).') as myint');
      //$cl->setSelect("*,IN(id) as myint");
      $cl_indices->setFilter('myint', array(1));
    }

    if (!empty($q)) {
      $r = $cl_indices->Query($q, $n['indiceslist']);
      if (empty($r['matches'])) {
        $c = 0;
      } else {
        $c = count($r['matches']);
      }
    } else {
      $c = count($path_ids_indexbtn);
    }

    if ($index == $t) {
      $extraclass = ' current';
    } else {
      $extraclass = '';
    }
    if ($c > 0) {
      if ($c >= 1000) {
        $ctext = "<span class='indexhits'>$c+</span>";
      } else {
        $ctext = "<span class='indexhits'>$c</span>";
      }
    } else {
      $ctext = "";
    }
    $indicesdiv .= "<div class='index$extraclass' title='index: ".str_replace('index', '', $n['indiceslist'])."' id='$t' onclick=\"changeindex('$t');\">$ctext".$n['title']."</div>";
  }
  $indicesdiv .= "</div>";

  //We have results to display
  if (!empty($ids)) {
    print "<br>";
    print "$indicesdiv";

    //Setup Database Connection
    //$db = mysqli_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password']) or die("ERROR: unable to connect to database");
    //mysqli_select_db($db, $CONF['mysql_database']) or die("ERROR: unable to select database");

    //Run the Mysql Query
    $sql = str_replace('$ids',implode(',',$ids),$CONF['mysql_query']);
    $result = mysqli_query($db,$sql) or die($CONF['debug']?("ERROR: mysql query failed: ".mysqli_error($db)):"ERROR: Please try later");

    require('minimisestring.php');

    function minimisestringres($s) {
      global $index;
      // not sure why $index is not accessible here
      if ($index == 'public') {
        $s = preg_replace("/.txt$/", "", $s);
      }
      $s = minimisestring($s);
      return $s;
    }

    if (mysqli_num_rows($result) > 0) {
      //Fetch Results from MySQL (Store in an associative array, because they wont be in the right order)
      $rows = array();
      while ($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
        $rows[$row['id']] = $row;
      }

      function cmptext($a, $b) {
        return strcmp($a['text'], $b['text']);
      }

      if ($index == 'logs' || $index == 'jenkins') {
        uasort($rows, 'cmptext');

        $sortedids = array();
        foreach ($ids as $c => $id) {
          $row = $rows[$id];
          $fullpath = $row['text'];
        }
      }

      // Should be using this instead of grep...
      ////Call Sphinxes BuildExcerpts function
      //if ($CONF['body'] == 'excerpt') {
      //  $docs = array();
      //  //foreach ($ids as $c => $id) {
      //  //  $docs[$c] = strip_tags($rows[$id]['body']);
      //  //}
      //  $reply = $cl->BuildExcerpts($docs, $CONF['sphinx_index'], $q);
      //}

      //if ($numberOfPages > 1 && $currentPage > 1) {
      print "<div class='pages'>".pagesString($currentPage,$numberOfPages)."</div>";
      //}

      //Actually display the Results
      print "<ol class=\"results\" start=\"".($currentOffset+1)."\">";

      $dataForSVG = array();
      // Work out if this is the full list of IDs or if this has been filtered
      // to the current page.
      // I want to graph the full list.
      foreach($ids as $id) {
          $dataForSVG[$id]['path'] = minimisestringres($rows[$id]['text']);
          $dataForSVG[$id]['url'] = '';
      }
      // $dataForSVG now has unique elements.

      // sort alphanumerically

      //function cmppath($a, $b) {
      //  return strcmp($a['path'], $b['path']);
      //}

      //uasort($dataForSVG, 'cmppath');

      //foreach ($dataForSVG as $d) {
      //  print_r(explode('/', $d['path']));
      //  print "<br/>";
      //}

      //print "<br/>";
      //print "<br/>";

      // sort by number of slashes

      function getsvg($partgv) {
        $descriptorspec = array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w"));
        $env = array();

        $pipes = null;
        $process = proc_open('/var/www/croogle/getsvg.sh', $descriptorspec, $pipes, '/var/www/croogle', $env);

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

      function cmpslash($a, $b) {
        // The ones with the most slashes should go first
        // Because that will make it look better
        $ac = substr_count($a['path'], '/');
        $bc = substr_count($b['path'], '/');
        if ($ac < $bc) {
          return 1;
        } else if ($ac > $bc) {
          return -1;
        }

        // If they have the SAME NUMBER OF SLASHES, try string and slash comparison
        // $ac and $bc are equal
        $ae = explode('/', $a['path']);
        $be = explode('/', $b['path']);

        for ($i = 0; $i <= $ac + 1; $i++) {
          $sc = strcmp($ae[$i], $be[$i]);
          if ($sc == 0) {
            continue;
          } else {
            return $sc;
          }
        }
        return 0;
      }

      uasort($dataForSVG, 'cmpslash');

      // from printing out the array here, I can see that it only contains
      // what's on the screen
      #print "<br/>";
      #print "<br/>";

      //foreach ($dataForSVG as $d) {
      //  print_r(explode('/', $d['path']));
      //  print "<br/>";
      //}

      //foreach ($dataForSVG as $d) {
      //  print_r(dirname($d['path']));
      //  print "<br/>";
      //}

      // This will be piped into gv
      $gvs = "";

      $sqrtval = intval(sqrt(count($ids)));

      $created = array();
      foreach ($dataForSVG as $d) {
        // fp means full path
        // dn means dirname(full path)
        // de means exploded full path
        // dc means number of slashes + 1
        // fn means fullname
        $fp = $d['path'];
        $dn = dirname($fp);
        $de = explode('/', $fp);
        $dc = count($de);

        $gvfn = '';
        for ($i = 1; $i < $dc; $i++) {
          // I have to reconstruct like this, so I can split later on using
          // dirname and use dirnames to check if exists by using the hashes
          $gvfn = $gvfn.'/'.$de[$i];
          $hashgvfn = hash('crc32', $gvfn);
          if (! isset($created[$hashgvfn])) {
            $created[$hashgvfn] = $de[$i];

            // Non-leaves can be made longer because they are less-densely distributed.
            // But leave this high because there's generally no need to cut down
            // the length of the labels.

            // don't use $i alone. need $i + number of siblings
            // actually, i digress. as the number of siblings increases, allow
            // for more characters.
            // Actually, only limit the first sibling.

            if ($i == $dc - 1) {
              // If is a leaf
              //print node [fontname=Ubuntu, fontcolor="#8888ff", style="filled", width=0, height=0, shape=box, color=gray, fillcolor="#dddddd", concentrate=true, peripheries="0"]
              //$labelextras = ", color=\"#ddaa00\", fontcolor=\"#0077ff\", fillcolor=\"#eeeeee\", style=\"filled,rounded\", peripheries=\"0\"";
              $labelextras = ", color=\"#ddaa00\", fontcolor=\"#0077ff\", fillcolor=\"#eeeeee\", style=\"filled,rounded\", peripheries=\"1\"";

              $maxlen = 30;
            } else {
              $labelextras = "";
              $maxlen = 30;
            }

            //if (!isset($created[$prevhashgvfn.'child'])) {
            //  $maxlen -= $sqrtval * $i;
            //}

            if ($maxlen < 10) {
              $maxlen = 10;
            }

            $label=$de[$i];
            if (strlen($label) > $maxlen) {
                $label = substr($label, 0, ($maxlen - 1)) . '…';
            }
            $label=htmlentities($label);

            if ($i > 1) {
              $pddir = trim(dirname($pd['path']));
              //print 'pddir:'.$pddir.' dn:'.$dn.'<br/>';
              // DARN IT! I didn't think about linking to siblings
              if (isset($created[$prevhashgvfn.'child'])) {
                $parentorsib = $created[$prevhashgvfn.'child'].'p';
              } else {
                $parentorsib = $prevhashgvfn;
              }

              if ($i == $dc - 1) {
                /* If is child ($i != 1), print rank and arrow */
                $gvs .= "{rank=same"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_".$hashgvfn."p [shape=point]"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_$hashgvfn [label=\"".$label."\l\"".$labelextras."]\n";
                //$gvs .= "n_$hashgvfn [label=<<u>$label</u>>".$labelextras."]\n";
                //print "<br/>"."\n";
                $gvs .= "}"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_$parentorsib -> n_".$hashgvfn."p [arrowhead=none]"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_".$hashgvfn."p -> n_".$hashgvfn." [arrowhead=none]\n"; // could have arrow
                //print "<br/>"."\n";
              } else {
                /* If is child ($i != 1), print rank and arrow */
                $gvs .= "n_".$hashgvfn."bp [shape=point]"."\n";
                $gvs .= "n_".$hashgvfn."cp [shape=point]"."\n";
                $gvs .= "{rank=same"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_".$hashgvfn."p [shape=point]"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_$hashgvfn [label=\"".$label."\l\"".$labelextras."]\n";
                //print "<br/>"."\n";
                $gvs .= "}"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_$parentorsib -> n_".$hashgvfn."bp [arrowhead=none]"."\n";
                //print "<br/>"."\n";
                $gvs .= "n_".$hashgvfn."bp -> n_".$hashgvfn."cp [arrowhead=none]\n";
                $gvs .= "n_".$hashgvfn."cp -> n_".$hashgvfn."p [arrowhead=none]\n";
                $gvs .= "n_".$hashgvfn."p -> n_".$hashgvfn." [arrowhead=none]\n"; // could have arrow
                //print "<br/>"."\n";
              }

              // set this paren't most recent child to this one
              $created[$prevhashgvfn.'child'] = $hashgvfn;
            } else {

                //<<u>Tada</u>>
              $gvs .= "n_$hashgvfn [label=\"".$label."\l\"".$labelextras."]\n";
              //print "<br/>"."\n";
            }
            /*
              * if is child ($i != 1), print parent/sibling arrow
              * how to determine parent/sibling?
             */
            //print $hashgvfn.'  '.$gvfn.'  '.$created[$hashgvfn];
            //print "<br/>";
          }
          $prevgvfn = $gvfn;
          $prevhashgvfn = $hashgvfn;
        }
        $pd = $d;
      }
      $gvs .= "}\n";
      print getsvg($gvs);

      //foreach ($created as $k => $v) {
      //  print $k.'  '.$v;
      //  print "<br/>";
      //}

      //print_r($dataForSVG);

      // how to unique this array based on the value of path.

      //print(passthru("/var/www/croogle/getsvg.sh"));
      //print "<img src='dirs.svg'/>";
      foreach ($ids as $c => $id) {
        $row = $rows[$id];

        $fullpath = $row['text'];
        $restext = minimisestringres($fullpath);

        // array_filter in its own simply removes empty elements
        $items = array_filter(explode("/", $restext));
        $len = count($items);

        $link = htmlentities(str_replace('$id',$row['id'],$CONF['link_format']));
        //print "<li><a href=\"$link\">".htmlentities($restext)."</a><br/>";
        //print "<li><a href=\"$link\">".htmlentities($restext)."</a><br/>";
        //
        print "<li class='$liclass' onmouseenter=\"resultsunfaderesults();\" onmouseleave=\"resultsfaderesults();\"><div>";
        print "<a class='pointera' href=\"javascript:void(0);\" onclick='$(\"#path$id\").selectText();'>&#9659;</a>";
        print "<span class='path' id='path$id'>";
        #$thirdparty = preg_replace("~^/ebl/packages/thirdParty/(\w+)/.*/", "$1", $restext);
        #$project = preg_replace("~^/ebl/packages/(\w+)/.*~", "$1", $restext);
        if (preg_match("/^\/ebl\/packages\/([0-9A-Za-z-]+)(\/.*)/i", $restext, $matches)) {
          $project = $matches[1];
          $fpath = $matches[2];
        } else {
          $project = preg_replace("/^\/([0-9A-Za-z-]+)\/.*/", "$1", $restext);
          #$project = str_replace(array("\r", "\n"), '', $project);
          $fpath = preg_replace("/^\/([0-9A-Za-z-]+)(\/.*)/", "$2", $restext);
        }

        $project = ltrim(strtolower(preg_replace('/[A-Z]/', '-$0', $project)), '-');

                /*
                $tra = [["geoReporting", "geo-reporting"],
                        ["canInterface", "can-interface"],
                        ["hilTestCommon", "hil-test-common"]];
                foreach ($tra as $tr) {
                  if (strcmp($tr[0], $project) == 0) {
                    $project = $tr[1];
                    break;
                  }
                }
                 */
        print "/";
        $i = 0;
        foreach ($items as $im) {
          $matches = array_filter($pathwords, function($var) use ($im) { return strlen($var) > 1 && preg_match('/^.*'.$var.'.*$/i', $im); });

          if (count($matches) > 0) {
            $aclass = "query";
          } else {
            $aclass = "";
          }

          if ($i == count($items) - 1) {
            $queryfunc = "newquery";
          } else {
            $queryfunc = "newpathquery";
            $aclass = "$aclass folder-menu";
          }
          $aclass = "class='$aclass'";
          print "<a $aclass href=\"javascript:void(0);\" onclick=\"$queryfunc('".basename($im)."');\">".$im."</a>";
          if ($i != $len - 1) {
            print "/";
          }
          $i++;
        }
        print "</span>";
        print "&nbsp;&nbsp;";
        print "&nbsp;&nbsp;";
        //print "<a href=\"javascript:void(0);\" onclick=\"copyToClipboard('".$restext."');\">".htmlentities($restext)."</a>";
        //print "&nbsp;&nbsp;<a class='copypath' href=\"javascript:void(0);\" onclick=\"copyToClipboard('".$restext."');\">Copy Path</a>";
        if ($index == "packages" && in_array($project, $projects)) {
          print "&nbsp;&nbsp;<a class='stash' href=\"javascript:void(0);\" onclick=\"window.open('http://stash.rtc.crownlift.net/projects/PROJECTS/repos/$project/browse$fpath');\">Stash</a>";
        } else if ($index == "logs" || $index == "jenkins") {
          if (preg_match("/^\/results\/jenkins\/([0-9A-Za-z-_]+)\/([0-9]+)(\/.*)/i", $restext, $matches)) {
            $job = $matches[1];
            $job = str_replace('-','_',$job);
            $buildnum = $matches[2];
            $joburl = "http://jenkins/job/$job/$buildnum/console";
            $proxyjoburl = "http://localhost/jenkins/job/$job/$buildnum/console";
            if (!isBadUrl($proxyjoburl)) {
              print "&nbsp;&nbsp;<a class='jenkins' href=\"javascript:void(0);\" onclick=\"window.open('$joburl');\">Jenkins</a>";
              /*
              print "&nbsp;&nbsp;<a class='jenkins' href=\"javascript:void(0);\" onclick=\"window.open('$joburl');\">Rerun</a>";
              curl -X GET "http://localhost/jenkins/job/$jobtype/$chosenjobnum/retry/"
               */
            } else {
              print "&nbsp;&nbsp;Cleared from Jenkins";
            }
          }
        }
        if ($index == "thirdparty" && in_array($project, $thirdpartypackages)) {
          print "&nbsp;&nbsp;<a class='stash' href=\"javascript:void(0);\" onclick=\"window.open('http://stash.rtc.crownlift.net/projects/ECLONE/repos/$project/browse$fpath');\">Stash</a>";
        }
        if ($index == "public") {
          //print "&nbsp;&nbsp;<a class='windows' href=\"javascript:void(0);\" onclick=\"window.open('file:///P:".$restext."');\">Open</a>";
          print "&nbsp;&nbsp;<a href=\"javascript:void(0);\" onclick=\"copyToClipboard('P:\\".str_replace('/','\\\\',$restext)."');\">Show path</a>";
        }
        if ($user == "shane") {
          print "&nbsp;&nbsp;<a class='vim' href=\"croogle://vimsh#$longestWord#$restext\">Vim</a>";
          print "&nbsp;&nbsp;<a class='eclipse' href=\"croogle://eclipseclient#$longestWord#$restext\">Eclipse</a>";
        } else {
          print "&nbsp;&nbsp;<a class='vim' href=\"croogle://vim#$longestWord#$restext\">Vim</a>";
          print "&nbsp;&nbsp;<a class='eclipse' href=\"croogle://eclipse#$longestWord#$restext\">Eclipse</a>";
        }
        print "&nbsp;&nbsp;<a class='xdg' href=\"croogle://xdg#$longestWord#$restext\">Open</a>";

        if (! $_GET['qtype'] == "change") {
          //print "<a href=\"javascript:void(0);\" onclick=\"copyToClipboard('".$restext."');\">".htmlentities($restext)."</a>";

          #$sample = exec("sudo /var/www/croogle/getcontext.sh $fullpath $longestWord");
          $sample = trim(runcommand("/var/www/croogle/getcontext.sh $fullpath $longestWord", "", '/var/www/croogle'));
          if (!empty($sample)) {
            $contextlines = explode("\n", $sample);
            foreach ($contextlines as $contextline) {
              $linenum = preg_replace('/(^\d+):.*/','\1',$contextline);
              $rest = preg_replace('/^\d+: *(.*)/','\1',$contextline);
              if (preg_match("/(\w|\d)+/i", $rest)) {
                print "<br/>";
                if (strlen($rest) > 150) {
                  $rest = substr($rest, 0, 147) . '...';
                }
                print "<a class='pointer' href=\"javascript:void(0);\" onclick='$(\"#code".$id."_line$linenum\").selectText();'>&#9659;</a>";
                print "<span class='linenum'>$linenum:</span><code id='code".$id."_line$linenum'>";
                $e = htmlentities($rest);
                //$e = preg_replace("/(?<!&)(\b[.0-9A-Za-z-_]{3,})/", "<a href=\"javascript:void(0);\" onclick=\"newquery('$1');\">$1</a>", $e);
                $e = preg_replace_callback(
                  "/(?<!&)(\b[.0-9A-Za-z-_]{3,})/",
                  function ($matches) use ($words) {
                    //return "<a href=\"javascript:void(0);\" onclick=\"newquery('$1');\">".$matches[0]."</a>";

                    $term = trim($matches[0]);
                    $matchingterms = array_filter($words, function($var) use ($term) { return strlen($var) > 3 && preg_match('/^.*'.$var.'.*$/i', $term); });

                    if (count($matchingterms) > 0) {
                      $aclass = "class='query'";
                    } else {
                      $aclass = "";
                    }
                    return "<a $aclass href=\"javascript:void(0);\" onclick=\"newquery('".$term."');\">".$term."</a>";
                  },
                    $e
                  );
                print $e;
                print "</code>";
              }
            }
          }
        }

        //print "<li>".htmlentities($restext)."<br/>";

        if ($CONF['body'] == 'excerpt' && !empty($reply[$c]))
          print ($reply[$c])."</div></li>";
        //else
        //  print htmlentities($row['body'])."</li>";
      }
      print "</li><div id='doganalysis' title='Dog Analysis'></div><div id='maxresults' title='Purrspective' onclick=\"togglemaxresults();\"></div><div id='sick' title='SICK'onclick=\"togglegraphonly();\" onmouseenter=\"sickfaderesults();\" onmouseleave=\"sickunfaderesults();\"></div></ol>";

      if ($numberOfPages > 1) {
        print "<div class='pages'>&nbsp;&nbsp;&nbsp;Page $currentPage of $numberOfPages&nbsp;&nbsp;&nbsp; ";
        print pagesString($currentPage,$numberOfPages)."</div>";
      }

      print "<pre class=\"results\">$query_info.";
      printf("&nbsp;&nbsp;Displaying results (%d&#8594;%d)/%d. Max context lines: 500 unique.",($currentOffset)+1,min(($currentOffset)+$CONF['page_size'],$resultCount),$resultCount);
      print "</pre>";

    } else {

      //Error Message
      print "<pre class=\"results\">Unable to get results for '".htmlentities($qo)."'</pre>";

    }
  } else {
    print "<div id='noresultsindices'>";
    print "$indicesdiv";
    print "</div>";
  }

}



#########################################
# Functions
# Created by Barry Hunter for use in the geograph.org.uk project, reused here because convenient :)

function linktoself($params,$selflink= '') {
  $a = array();
  $b = explode('?',$_SERVER['REQUEST_URI']);
  if (isset($b[1]))
    parse_str($b[1],$a);

  if (isset($params['value']) && isset($a[$params['name']])) {
    if ($params['value'] == 'null') {
      unset($a[$params['name']]);
    } else {
      $a[$params['name']] = $params['value'];
    }

  } else {
    foreach ($params as $key => $value)
      $a[$key] = $value;
  }

  if (!empty($params['delete'])) {
    if (is_array($params['delete'])) {
      foreach ($params['delete'] as $del) {
        unset($a[$del]);
      }
    } else {
      unset($a[$params['delete']]);
    }
    unset($a['delete']);
  }
  if (empty($selflink)) {
    $selflink = $_SERVER['SCRIPT_NAME'];
  }
  if ($selflink == '/index.php') {
    $selflink = '/';
  }

  return htmlentities($selflink.(count($a)?("?".http_build_query($a,'','&')):''));
}

function pagesString($currentPage,$numberOfPages,$postfix = '',$extrahtml ='') {
  static $r;
  if (!empty($r))
    return($r);

  $start = max(1,$currentPage-5);
  $endr = min($numberOfPages+1,$currentPage+8);

  if ($start > 1)
    $r .= "<a href=\"javascript:void(0);\" onclick=\"changepage('".(1)."');\">1</a>";
  if ($currentPage > 1)
    $r .= "<a href=\"javascript:void(0);\" onclick=\"changepage('".($currentPage-1)."');\">&#8656; prev</a>";
  for($pindex = $start;$pindex<$endr;$pindex++) {
    if ($pindex == $currentPage)
      $r .= "<b>$pindex</b>";
    else
      $r .= "<a href=\"javascript:void(0);\" onclick=\"changepage('".($pindex)."');\">$pindex</a>";
  }
  //if ($pindex < $numberOfPages + 1)
  //  $r .= "<a href=\"javascript:void(0);\" onclick=\"changepage('".($numberOfPages)."');\">$numberOfPages</a>";
  if ($endr < $numberOfPages+1) {
    //$r .= "<b>$numberOfPages pages</b>";
    $r .= "<a class=\"\" href=\"javascript:void(0);\" onclick=\"changepage('".($numberOfPages)."');\">$numberOfPages</a>";
  }

  if ($numberOfPages > $currentPage)
    $r .= "<a href=\"javascript:void(0);\" onclick=\"changepage('".($currentPage+1)."');\">next &#8658;</a>";

  return $r;
}

if (! $_GET['qtype'] == "change") {
if ($longestWordLength > 2) {
?>

</div>
<div id="suggestions">
<?php

  #echo $longestWord;

  #$nerrors = 0;
  #while ($c < 2
  print "<span class='fadedtext'>Longest word:</span>&nbsp;<span class='boldened'>$longestWord</span>";
  # This adds about 100ms to a full query (including changing pages).
  if (true) {
    $suggestions = preg_grep('/'.$longestWord.'/', file($index.'-suggestions.txt'));
    $suggestions = array_filter(array_map('trim', $suggestions));
  } else {
    $suggestions = array();
  }
  $cnum = count($suggestions);
  if ($cnum > 0) {
    reset($suggestions);
    $firstkey = key($suggestions);
    $firstval = $suggestions[$firstkey];
    reset($suggestions);
    if ($cnum > 0 && (! ($cnum == 1 && $firstval == strtolower($q)))) {
      print "<div class='suggestionssep'>Closest matches</div>";
      foreach ($suggestions as $s) {
        if ($s != $q)
          print "<a href=\"javascript:void(0);\" onclick=\"newquery('".$s."');\">$s</a>";
      }
    }
    #foreach ($suggestions as $s) {
    #  print "<a href=\"javascript:void(0);\" onclick=\"newquery('".str_replace(array("\r", "\n"), '', $s)."');\">$s</a>";
    #}
    $nerrors = 0;
    $oldsuggestions = $suggestions;
    # 8 is agrep's max number of errors
    # Doesn't make sense to allow for lots of errors with short words
    while ($nerrors <= 8 && $nerrors <= (strlen($longestWord) / 4) && count($suggestions) < 20 && count($suggestions) > 0) {
      $nerrors += 1;
      $oldsuggestions = $suggestions + $oldsuggestions;
      //foreach ($words as $word) {
      // get the intersection of each of the suggestion sets for each word
      exec("agrep -$nerrors $longestWord suggestions.txt", $suggestions);
      $suggestions = array_diff($suggestions, $oldsuggestions);
      if (count($suggestions) > 0) {
        print "<span class='fade'>";
        print "<div class='suggestionssep'>Edit distance: $nerrors</div>";
        foreach ($suggestions as $s) {
          print "<a href=\"javascript:void(0);\" onclick=\"newquery('".str_replace(array("\r", "\n"), '', $s)."');\">$s</a>";
        }
      }
    }
    for ($x = 0; $x < 8; $x++) {
      print "</span>";
    }
  }

  ////$suggestions = array_map("stripnewlines", $s);


?>
</div>
<?php
}
}
?>
<div id="cleared"></div>
