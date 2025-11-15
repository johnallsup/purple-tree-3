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
// For C-d sidebar
if( ! isset($postdata["path"]) ) {
  serve_error_json("nopath","No path specified",400);
}
require_once("utils.php");
require_once("common.php");
require_once("wiki_common.php");
$path = $postdata["path"];
$path = trim($path,"/");

[ $dirs, $pages, $files ] = get_dir_contents($storage,$path);
$dirs = array_values($dirs);
$pages = array_map(function($x) {
  $n = strlen(".".PAGE_EXT);
  $l = strlen($x);
  return substr($x,0,$l-$n);
},array_values($pages));
$files = array_values($files);
$dir = [ "pages" => $pages, "dirs" => $dirs, "files" => $files ];
$json = json_encode($dir);
echo $json;
exit();

$pages = from_data_json("pages");
if( is_null($pages) ) $pages = [];
$pages = array_filter($pages,function($x) use($path) {
  if( $path === "" ) return true;
  $x = substr($x,0,strlen($x)-strlen(PAGE_EXT)-1);
  if( $x == $path ) return true; # exact match
  if( strlen($x) < strlen($path) ) { return false; }
  $xs = explode("/",$x);
  $ps = explode("/",$path);
  if( count($ps) < count($xs) ) {
    for( $i = 0; $i < count($ps); $i++ ) {
      if( $xs[$i] !== $ps[$i]) {
        return false;
      }
    }
    return true;
  }
  return false;
});
$pages = array_values($pages);
$response_data = [ "path" => $path, "pages" => $pages, "debug_received" => $postdata ];
serve_json($response_data,200);
