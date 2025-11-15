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

$url = $_SERVER['REQUEST_URI'];
$url = preg_replace("@^/+@","",$url);
$storage = new VersionedStorage(FILES_DIR,VERSIONS_DIR);
if( !is_auth("view") ) {
  include("AccessDenied.php");
  exit();
}
if( $storage->has($url) ) {
  $mime = $storage->get_mime_type($url);
  $fpath = $storage->fpath($url);
  http_response_code(200);
  if( preg_match('/\.ptmd$/',$fpath) ) {
    header("Content-type: text/plain");
  } else {
    header("Content-type: $mime");
  }
  readfile($fpath);
  exit();
} else {
  $xs = explode("/",$url);
  $filename = array_pop($xs);
  $subdir = implode("/",$xs);
  if( $subdir == "" ) $subdir = "/";
  $paths = $storage->find_leaf_to_root($subdir,$filename);
  if( count($paths) > 0 ) {
    $path = $paths[0];
    $fpath = $storage->fpath($path);
    $mime = $storage->get_mime_type($path);
    http_response_code(200);
    header("Content-type: $mime");
    readfile($fpath);
    exit();
  }
}
http_response_code(404);
exit();
?>
