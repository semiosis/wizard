<?php

function minimisestring($s) {
  $s = str_replace("/home/shane/programs/strings/fake/home/smulliga/Public", "", $s);
  $s = str_replace("/home/shane/programs/strings/real/home/smulliga/Public", "", $s);
  $s = str_replace("/home/shane/projects/ebl/packages/thirdParty", "", $s);
  $s = str_replace("/home/shane/dump/mirror", "", $s);
  $s = str_replace("/home/shane/projects", "", $s);
  $s = str_replace("/home/shane/source/git", "", $s);
  $s = str_replace("/home/shane/notes", "", $s);
  $s = str_replace("/home/shane", "", $s);
  $s = str_replace("/ebl", "", $s);
  //$s = str_replace("/config/vim/common/bundle/YouCompleteMe/third_party/ycmd/third_party", "/YCMD3P", $s);
  //$s = str_replace("/config/vim/common/bundle/YouCompleteMe/third_party", "/YCM3P", $s);
  //$s = str_replace("/config/vim/common/bundle", "/VIMBUNDLE", $s);
  //$s = str_replace("/config/vim/vim/bundle", "/VIMBUNDLE", $s);
  return $s;
}

?>