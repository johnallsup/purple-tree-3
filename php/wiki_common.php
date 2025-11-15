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

$storage = new VersionedStorage(FILES_DIR,VERSIONS_DIR);

$wiki = new Wiki();
if( !is_auth("edit") ) {
  if( isset($_GET['action']) && preg_match("@^view|typing@",$xxx = $_GET['action']) ) {
    $action = $xxx;
  } else {
    $action = "view";
  }
} else if( isset($_GET['action']) ) {
  $action = $_GET['action'];
  if( ! preg_match("@^view|edit|versions|typing$@",$action) ) {
    echo "fail $action\n";
    invalid_action($action,"1");
  }
} else {
  $action = "view";
}
$fontsize = null;
if( isset($_GET['fs']) ) {
  $fontsize = $_GET['fs'];
}
if( isset($_GET['fontsize']) ) {
  $fontsize = $_GET['fontsize'];
}

$url = $_SERVER['REQUEST_URI'];
if( preg_match('@//+@',$url) ) {
  $newurl = preg_replace('@//+@','/',$url);
  $wiki->redirect($newurl); 
}
$url = explode("?",$url,2)[0];
$url = preg_replace("@^/+@","",$url);
if( !preg_match("@^(.*/)?([^/]*)$@",$url,$m) ) {
  http_response_code(400);
  echo "invalid path 1453: $url";
  exit();
}

[ $x, $subdir, $pagename ] = $m;
$subdir = rtrim($subdir,"/");
$wiki->storage = $storage;
$wiki->action = $action;
$wiki->url = $url;
$wiki->pagename = $pagename;
$wiki->subdir = $subdir;
$wiki->config = get_config($wiki);
$wiki->pagemtime = 0;
$wiki->path = null;
$wiki->navbar_path = null;

