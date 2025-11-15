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
if( !isset($theme) ) { $theme = "default"; }
# echo "Do_Page\n";
require("PageLike.php");

### HELPER FUNCTIONS
function invalid_action($action,$msg = "") {
  global $wiki;
  http_response_code(400);
  echo "Invalid action: $action";
  exit();
}
function gen_dir() {
  global $wiki;
  $subdir = $wiki->subdir;
  $storage = $wiki->storage;
  $pages = $storage->getglob($subdir."/*.".PAGE_EXT);
  $files = $storage->getglob($subdir."/*.*");
  $dirs = $storage->getglob($subdir."/*");
  $dirs = array_filter($dirs, function ($x) use($storage) { return $storage->isdir($x); } );
  $files = array_filter($files,function ($x) { return !preg_match('/\\.'.PAGE_EXT.'$/',$x); });
  $dirs = array_map(function($x) { return basename(trim($x,"/")); },$dirs);
  $dirs = array_filter($dirs,function($x) { return $x !== "" ; });
  $files = array_map(function($x) { return basename(trim($x,"/")); },$files);
  $pages = array_map(function($x) { return basename(trim($x,"/")); },$pages);
  $pages = array_filter($pages, function($x) { return preg_match('/^[^\.]+\.'.PAGE_EXT.'$/',$x); });
  $pages = array_map(function($x) { return preg_replace('/\.'.PAGE_EXT.'$/',"",$x); },$pages);
  $files = array_diff($files,$pages);
  $pages = array_values($pages);
  $files = array_values($files);
  $dirs = array_values($dirs);
  return [ $pages, $dirs, $files ];
}
function gen_dir_json() {
  [ $pages, $dirs, $files ] = gen_dir();
  $data = [ "pages" => $pages, "dirs" => $dirs, "files" => $files ];
  return json_encode($data);
}

### REDIRECTS
if( $_SERVER['QUERY_STRING'] == "e" ) {
  $newuri = preg_replace('/\?e$/','?action=edit',$_SERVER['REQUEST_URI']);
  $wiki->redirect($newuri);
}
if( $_SERVER['QUERY_STRING'] == "t" || $_SERVER['QUERY_STRING'] == "ty" ) {
  $newuri = preg_replace('/\?t\w*$/','?action=typing',$_SERVER['REQUEST_URI']);
  $wiki->redirect($newuri);
}
// if the url names a directory d, redirect to d/home
if( $storage->isdir($url) ) {
  $url = trim($url,"/");
  $wiki->redirect("/$url/home");
  exit();
}

### INITIAL VARS
$path = $url.".".PAGE_EXT;
$navbar_path = $subdir."/.navbar.".PAGE_EXT;

# Page specific -- PageLike's should copy and modify this bit
$wiki->path = $path;
$wiki->navbar_path = $navbar_path;

http_response_code(200);
header("Content-type: text/html");
if( $storage->has($navbar_path) ) {
  $navbar_source = $storage->get($navbar_path);
} 

### CHECK AUTH
if( is_auth("edit") && isset($_GET['action']) ) {
  $action = $_GET['action'];
  if( ! preg_match("@^view|edit|versions|typing$@",$action) ) {
    invalid_action($action,"1");
  }
} else if( isset($_GET['action']) ) {
  $action = $_GET['action'];
  if( preg_match("@^(edit|versions)$@",$action) ) {
    $action = "view";
  } else if( ! preg_match("@^(view|typing)$@",$action) ) {
    invalid_action($action,"1");
  } 
}

### DISPATCH 
if( $wiki->action === "versions" ) {
  http_response_code(200);
  require("RenderVersions.php");
  exit();
}
if( isset($_GET['version']) && is_auth("edit") ) {
  # only editors can access previous versions
  if( $action !== "view" && $action !== "edit" ) {
    invalid_action($action,"for version");
    exit();
  }
  $when = $_GET['version'];
  if( ! $storage->has_version($path,$when) ) {
    $page_source = "Version $when for page $path does not exist.";
  } else { 
    $page_source = $storage->get_version($path,$when);
    $wiki->pagemtime = $when;
  }
  require("RenderPage.php");
  exit();
}
if( ! $storage->has($path) ) {
  $wiki->pagemtime = "null";
  if( $path !== "home.ptmd" ) {
    if( $pagename === "home" ) {
      $xs = explode("/",$path);
      array_pop($xs);
      $x = implode("/",$xs);
      if( ! $storage->isdir($x) ) { http_response_code(404); }
    } else {
      http_response_code(404); // special case
    }
  }
  if( $pagename === "home" ) {
    require("RenderDefaultHomePage.php");
  } else {
    require("RenderDefaultPage.php");
  }
  exit();
}
$page_source = $storage->get($path);
$wiki->pagemtime = $storage->getmtime($path);
require("RenderPage.php");
exit();

### TEMPLATE HANDOFF
#require(TEMPLATES_DIR."/$theme/Template.php");
