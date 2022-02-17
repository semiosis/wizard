<?php
$CONF['mysql_host'] = "localhost";
$CONF['mysql_username'] = "root";
$CONF['mysql_password'] = "oracle";
$CONF['mysql_database'] = "fileindexdb";

$basicindextypes = array(
  'home' => array(
    'title' => 'Notes',
    'indiceslist' => 'homeindex',
    'humanname' => 'personal files',
    'sort' => "@relevance DESC, @id DESC",
    'graphsort' => "@relevance DESC, @id ASC"
  ),
  'libraries' => array(
    'title' => 'Libraries',
    'indiceslist' => 'librariesindex',
    'humanname' => 'python libaries',
    'sort' => "@relevance DESC, @id DESC",
    'graphsort' => "@relevance DESC, @id ASC"
  ),
  'git' => array(
    'title' => 'git',
    'indiceslist' => 'gitindex',
    'humanname' => 'git files',
    'sort' => "@relevance DESC, @id DESC",
    'graphsort' => "@relevance DESC, @id ASC"
  ),
  'system' => array(
    'title' => 'System',
    'indiceslist' => 'systemindex',
    'humanname' => 'system files',
    'sort' => "@relevance DESC, @id DESC",
    'graphsort' => "@relevance DESC, @id ASC"
  ),
  'all' => array(
    'title' => 'Everything',
    'indiceslist' => 'allindex',
    'humanname' => 'system files',
    'sort' => "@relevance DESC, @id DESC",
    'graphsort' => "@relevance DESC, @id ASC"
  )
);
/* Reintroduce these */
/*    'sort' => "@relevance DESC, minimised DESC, @id DESC",*/
/*    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"*/


$shaneindextypes = array(
  'home' => array(
    'title' => 'Notes',
    'indiceslist' => 'homeindex',
    'humanname' => 'personal files',
    'sort' => "@relevance DESC, minimised DESC, @id DESC",
    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"
  ),
  'shane' => array(
    'title' => 'Shane',
    'indiceslist' => 'shaneindex',
    'humanname' => 'personal files',
    'sort' => "@relevance DESC, minimised DESC, @id DESC",
    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"
  ),
  'mygit' => array(
    'title' => 'Source',
    'indiceslist' => 'mygitindex',
    'humanname' => 'External git',
    'sort' => "@relevance DESC, minimised DESC, @id DESC",
    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"
  )
);

$indextypes = array(
  'packages' => array(
    'title' => 'Code',
    'indiceslist'=> 'packagesindex',
    'humanname' => 'the codebase',
    'sort' => "@relevance DESC, minimised DESC, @id DESC",
    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"
  ),
  'logs' => array(
    'title' => 'HIL',
    'indiceslist'=> 'logsindex',
    'humanname' => 'HIL logs',
    'sort' => "minimised DESC, @relevance DESC, @id DESC",
    'graphsort' => "minimised DESC, @relevance DESC, @id ASC"
  ),
  'jenkins' => array(
    'title' => 'Jenkins',
    'indiceslist'=> 'jenkinsindex',
    'humanname' => 'the jenkins logs',
    'sort' => "minimised DESC, @relevance DESC, @id DESC",
    'graphsort' => "minimised DESC, @relevance DESC, @id ASC"
  ),
  'rtm' => array(
    'title' => 'RTM',
    'indiceslist'=> 'rtmindex',
    'humanname' => 'the RTM',
    'sort' => "@relevance DESC, minimised DESC, @id DESC",
    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"
  ),
  'thirdparty' => array(
    'title' => '3rd Party',
    'indiceslist'=> 'thirdpartyindex',
    'humanname' => 'the third party libraries',
    'sort' => "@relevance DESC, minimised DESC, @id DESC",
    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"
  ),
  'public' => array(
    'title' => 'Public',
    'indiceslist'=> 'publicindex',
    'humanname' => 'Public',
    'sort' => "@relevance DESC, minimised DESC, @id DESC",
    'graphsort' => "@relevance DESC, minimised DESC, @id ASC"
  )
);

/*if ($user == "shane") {
  $indextypes = array_merge($indextypes, $shaneindextypes);
}*/

$indextypes = $basicindextypes;

?>