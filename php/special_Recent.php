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

require("PageLike.php");
require_once("mtimes.php");
$headerTitle = "Recent in ";
if( $subdir !== "" ) {
  $headerTitle .= $subdir;
} else {
  $headerTitle .= "/";
} 

if( $recent === "files" ) {
  $recents = from_data_json("recent_files");
} else {
  $recents = from_data_json("recent_pages");
}
if( is_null($recents) ) {
  $recents = [];
}
$vrecents = [];
if( data_file_exists(RECENT_WRITES_FILE) ) {
  $recent_writes = from_data(RECENT_WRITES_FILE);
  $lines = explode("\n",$recent_writes);
  foreach($lines as $line) {
    $xs = explode(":",$line);
    if( count( $xs ) == 2 ) {
      array_push($vrecents,$xs);
    }
  }
}

function make_recent_page_row($rel_path,$when) {
  [ $mtime_fmt_long, $mtime_fmt_short, $mtime_fmt_short_ago, $mtime_fmt_ago ] = fmt_time($when);
  $rel_path = substr($rel_path,0,strlen($rel_path)-strlen(PAGE_EXT)-1);
  $tr = "<tr><td class='page'><a href='$rel_path'>$rel_path</a> (<a href='$rel_path?action=edit'>edit</a>)</td><td class='mtime'><span class='datetime'>$mtime_fmt_long</span> <span class='ago'>($mtime_fmt_ago)</span><td></tr>\n";
  return $tr;
}
function make_recent_file_row($rel_path,$when) {
  [ $mtime_fmt_long, $mtime_fmt_short, $mtime_fmt_short_ago, $mtime_fmt_ago ] = fmt_time($when);
  $tr = "<tr><td class='file'><a href='$rel_path'>$rel_path</a></td><td class='mtime'>$mtime_fmt_long<td><td class='ago'>$mtime_fmt_short_ago</td></tr>\n";
  #$tr = "<tr><td class='page'><a href='$rel_path'>$rel_path</a> (<a href='$rel_path?action=edit'>edit</a>)</td><td class='mtime'><span class='datetime'>$mtime_fmt_long</span> <span class='ago'>($mtime_fmt_ago)</span><td></tr>\n";
  return $tr;
}
$html = "<div class='recent-results recent-$recent'>\n";
$dn = $wiki->subdir;
if( $dn === "" ) {
  $ddn = "/";
} else {
  $ddn = $dn;
}
$vrecents = array_reverse($vrecents);
$vrecents_shown = [];
$rows = [];
for($i = 0; $i < count($vrecents); $i++) {
  $xs = $vrecents[$i];
  if( count($xs) == 2 ) { # skip invalid lines
    [ $when, $path ] = $xs;
    if( $recent == "pages" && preg_match("/^(.*)\\.".PAGE_EXT."$/",$path,$m) ) {
      # path relative to dirname
      if( $dn !== "" ) $dn = trim($dn,"/")."/";
      $l = min(strlen($path),strlen($dn));
      if( strlen($path) > $l && substr($path,0,$l) === $dn ) {
        if( strlen($dn) > 0 ) {
          $path_rel = substr($path,$l);
        } else {
          $path_rel = $path;
        }
        if( ! isset($vrecents_shown[$path_rel]) ) {
          $tr = make_recent_page_row($path_rel,$when);
          array_push($rows,$tr);
          $vrecents_shown[$path_rel] = true;
        }
      }
    } else if( $recent != "pages" && ! preg_match("/\\.".PAGE_EXT."$/",$path) ) {
      $l = min(strlen($path),strlen($dn));
      if( strlen($path) > $l && substr($path,0,$l) === $dn ) {
        if( strlen($dn) > 0 ) {
          $path_rel = substr($path,$l+1);
        } else {
          $path_rel = $path;
        }
        $tr = make_recent_file_row($path_rel,$when);
        array_push($rows,$tr);
      }
    }
  }
}
if( count($rows) > 0 ) {
  $html .= "<h1>Very recent $recent in $ddn</h1>\n";
  $html .= "<table class='recents very-recents recent-$recent'>\n";
  $html .= implode("",$rows);
  $html .= "</table>\n";
}
                      
$rows = [];
for($i = 0; $i < count($recents); $i++) {
  $xs = $recents[$i];
  [ $when, $path ] = $xs;
  $when = intval($when);
  if( $recent == "pages" && preg_match("/^(.*)\\.".PAGE_EXT."$/",$path,$m) ) {
    # path relative to dirname
    if( $dn !== "" ) $dn = trim($dn,"/")."/";
    $l = min(strlen($path),strlen($dn));
    if( strlen($path) > $l && substr($path,0,$l) === $dn ) {
      if( strlen($dn) > 0 ) {
        $path_rel = substr($path,$l);
      } else {
        $path_rel = $path;
      }
      if( preg_match('@^/*\.@',$path_rel) ) {
        # filter out dot files to anybody not authorised to edit
        if( ! is_auth("edit") ) {
          continue;
        }
      }
      $tr = make_recent_page_row($path_rel,$when);
      array_push($rows,$tr);
    }
  } else if( $recent != "pages" && ! preg_match("/\\.".PAGE_EXT."$/",$path) ) {
    $l = min(strlen($path),strlen($dn));
    if( strlen($path) > $l && substr($path,0,$l) === $dn ) {
      if( strlen($dn) > 0 ) {
        $path_rel = substr($path,$l);
      } else {
        $path_rel = $path;
      }
      if( preg_match('@^/*\.@',$path_rel) ) {
        # filter out dot files to anybody not authorised to edit
        if( ! is_auth("edit") ) {
          continue;
        }
      }
      $tr = make_recent_file_row($path_rel,$when);
      array_push($rows,$tr);
    }
  }
}
if( count($rows) > 0 ) {
  $html .= "<h1>Recent $recent in $ddn</h1>\n";
  $html .= "<table class='recents recent-$recent'>\n";
  $html .= implode("",$rows);
  $html .= "</table>\n";
}

$page_source = "<recents>";
$page_rendered = $html;

require("RenderPageLike.php");

