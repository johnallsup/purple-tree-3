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
require("dir_maker.php");
function generate_dir_content($wiki,$dir) {
  $storage = $wiki->storage;

  $pages = array_map(function($x) { $a = basename($x,".".PAGE_EXT); return "[[$a]]";; },$dir->pages);
  $dirs = array_map(function($x) { return "[[$x]]"; },$dir->dirs);
  $files = array_map(function($x) { return "[[$x]]"; },$dir->files);
  $t = "";
  if( count($pages) > 0 ) {
    $t .= "## Pages\n";
    $t .= implode(" ",$pages);
    $t .= "\n\n";
  }
  if( count($dirs) > 0 ) {
    $t .= "## Subdirectories\n";
    $t .= implode(" ",$dirs);
    $t .= "\n\n";
  }
  if( count($files) > 0 ) {
    $t .= "## Files\n";
    $t .= implode(" ",$files);
    $t .= "\n\n";
  }
  return $t;
}
if( $action === "edit" ) {
  $page_source = "";
} else {
  $about_path = ltrim($subdir."/.about.".PAGE_EXT,"/");
  $page_source = "";
  if( $storage->has($about_path) ) {
    $metalines = [];
    $about_source = $storage->get($about_path); 
    while( preg_match('/^[A-Za-z0-9-]+:/',$about_source) ) {
      $xs = explode("\n",$about_source,2);
      array_push($metalines,array_shift($xs));
      if( count($xs) == 0 ) $about_source = "";
      else $about_source = $xs[0];
    }
    $xs = explode("/",$subdir);
    $d = array_pop($xs);
    $s = implode("/",$xs);
    if( $s !== "" ) {
      $s = "<span class='ancestors'>$s/</span>";
    }
    $d = "<span class='dirname'>$d</span>";
    $page_source .= implode("\n",$metalines)."\n\n<h1 class='about'> About $s$d</h1>\n\n".$about_source;
  } else {
    $sd = $subdir === "" ? "Wiki ".SITE_TITLE : $subdir;
    $page_source = "Default **HOME page** for *$sd*.";
  }
  $dir_maker = new DirMaker($wiki);
  $dir_maker->get_dir_contents();
  $dir = generate_dir_content($wiki,$dir_maker);
  if( $dir !== "" ) $page_source .= "\n\n# Directory\n\n$dir";
}
require("RenderPage.php");
