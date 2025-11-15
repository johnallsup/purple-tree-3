<?php
/*
Purple Tree 3
Copyright (C) 2023-2025

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, version 3.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see https://www.gnu.org/licenses/.
*/
require_once("common.php");
if( !is_auth("view") ) {
  include("AccessDenied.php");
  exit();
}

$url = substr($_SERVER["REQUEST_URI"],1);
$url0 = $url;
$url = preg_replace('@//+@','/',$url);
$url = ltrim($url,"/");
if( $url !== $url0 ) {
  header("Location: /$url", true, 303);
  exit();
}
$url = urldecode($url);
if( preg_match('/^(.*)\?e$/',$url,$m) ) {
  $nurl = $m[1]."?action=edit";
  header("Location: /$nurl", true, 303);
  exit();
}



$us = explode("/",$url);
$si = -1;
foreach($us as $i => $v) {
  if( $v[0] === "." ) {
    $si = $i;
    break;
  }
}
$match_all = false;
$case_sensitive = false;
if( $si >= 0 ) {
  $ul = array_slice($us,0,$si);
  $ur = array_slice($us,$si+1);
  $ux = $us[$si];
  $ux = explode("?",$ux)[0];
  $l = implode("/",$ul);
  $r = implode("/",$ur);
  #echo "l='$l' x='$ux' r='$r'\n";
  switch($ux) {
  case ".w": # Word Search
  case ".word": # Word Search
    require("special_WordSearch.php");
    return;
  case ".wa": # Word Search All
    $match_all = true;
    require("special_WordSearch.php");
    return;
  case ".wac": # Word Search All
    $match_all = true;
    $case_sensitive = true;
    require("special_WordSearch.php");
    return;
  case ".wc": # Case Sensitive Word Search
  case ".c":  # Case Sensitive Word Search
    $case_sensitive = true;
    require("special_WordSearch.php");
    return;
  case ".wr": # Words Matching Regex
    require("special_WordsRegex.php");
    return;
  case ".r": # Recent
  case ".recent": # Recent
    $recent = "pages";
    require("special_Recent.php");
    return;
  case ".rf": # Recent Files
  case ".recf": # Recent Files
  case ".recentf": # Recent Files
  case ".recentfiles": # Recent Files
    $recent = "files";
    require("special_Recent.php");
    return;
  case ".t": # Tag Search
    $match_all = false;
    require("special_TagSearch.php");
    return;
  case ".ta":  # Tag Search
    $match_all = true;
    require("special_TagSearch.php");
    return;
  case ".tags":
    require("special_TagList.php");
    return;
  case ".d": # Directory
  case ".dir": # Directory
    $dir_type = "all";
    require("special_Dir.php");
    return;
  case ".dd": # Directory of subdirs
    $dir_type = "dirs";
    require("special_Dir.php");
    return;
  case ".df": # Directory of files
  case ".f": # Directory of files
    echo "Dir (files)\n";
    $dir_type = "files";
    require("special_Dir.php");
    return;
  case ".dp": # Directory of pages
  case ".p": # Directory of pages
    echo "Dir (pages)\n";
    $dir_type = "pages";
    require("special_Dir.php");
    return;
  case ".di": # Directory of images
  case ".i": # Directory of images
    require("special_ImgDir.php");
    return;
  case ".navbar": # Open/edit .navbar
  case ".config": # Open/edit .config
  case ".about": # Open/edit .about
  case ".css": # Open/edit .css
    require("Do_Page.php");
    return;
  default:
    echo "Unrecognised $ux\n";
    break;
  }
} else {
  echo "Invalid special URL\n";
}
?><h1>Special</h1>

<h1>Info</h1>
<?php
require("info.php");
?>
