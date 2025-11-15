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


$navbar_source = null;
$options = [];

# Pagelike common
if( !is_auth("view") ) {
  include("AccessDenied.php");
  exit();
}

require_once("wiki_common.php");
require_once("accumulator.php");

$htmlmeta = new Accumulator($wiki);
$scripts = new Accumulator($wiki);
$styles = new Accumulator($wiki);
$bodyclasses = new Accumulator($wiki);

$htmlmeta->add("<!-- COMMON META -->");
$scripts->add("<!-- COMMON SCRIPTS -->");
$styles->add("<!-- COMMON STYLES -->");

$scripts->addscrs(["/js/jq.js","/js/wiki_ajax.js","/js/ui.js","/js/wiki_common.js"]);
if( $wiki->action === "edit" ) {
  // later split things here and in header depending on auth status
  $scripts->addscrs(["/js/wiki_edit.js"]);
} else {
  $scripts->addscs("/js/wiki_view.js");
  $scripts->addscsni("/js/highlight.min.js");
}
$styles->addstss(["/css/wiki_common.css","/css/ui.css"]);
if( is_mobile ) {
  $bodyclasses->add("mobile");
  $htmlmeta->add("<meta name='viewport' content='width=device-width, initial-scale=1' />");
  $styles->addsts("/css/wiki_mobile.css");
  if( $wiki->action === "edit" ) {
    $styles->addstss(["/css/wiki_edit.css","/css/wiki_mobile_edit.css"]);
  } else {
    $styles->addstss(["/css/wiki_view.css","/css/wiki_mobile_view.css"]);
  }
} else {
  $styles->addsts('/css/wiki_desktop.css');
  if( $wiki->action === "edit" ) {
    $styles->addstss(['/css/wiki_edit.css','/css/wiki_desktop_edit.css']);
  } else {
    $styles->addstss(['/css/wiki_view.css','/css/wiki_desktop_view.css']);
  }
}
$styles->addstsni("/css/a11y-dark.css","screen");
if( is_auth("edit") ) {
  $scripts->add("<!-- EDIT SCRIPTS -->");
  #$scripts->add("<script src='/js/wiki_uploader.js'></script>");
  #$scripts->addscs('/js/wiki_uploader.js'); 
  $styles->add("<!-- EDIT STYLES -->");
  #$styles->add("<link rel='stylesheet' href='/css/wiki_auth.css'/>");
  $styles->addsts('/css/wiki_auth.css');
  $bodyclasses->add("auth");
  $bodyclasses->add("droptarget");
  $can_edit = true;
} else {
  $can_edit = false;
}
function hue_to_hsl($hue,$sat="100%",$lum="50%") {
  return "hsl($hue,$sat,$lum)";
}
function generate_bg_gradient($colors=null) {
  global $url;
  if( is_null($colors) ) {
    $pcs = explode("/",$url);
    array_pop($pcs);
    $colors = array_map(function($x) {
      $hash = hash("md5",$x);
      return hue_to_hsl(360.0*intval(substr($hash,0,4),16)/65536.0);
    },$pcs);
  }
  if( count($colors) == 0 ) {
    array_push($colors,hue_to_hsl(330.0));
    array_push($colors,"black");
  }
  if( count($colors) == 1 ) {
    array_push($colors,"black");
  }
  $step = 100.0/(count($colors)); 
  $grad = "linear-gradient(to bottom, ".implode(", ",$colors).")";
  $lastcolor = array_pop($colors);
  $bg = "$lastcolor $grad";
  $comment = "/* colors: ".(is_null($colors) ? "null" : implode(", ",$colors))." */";
  $css = "$comment\nhtml {
  height: 100%
}
body {
  height: 100%;
  background-color: black;
  background-image: linear-gradient(to bottom, #0005, #000), $grad;
  background-repeat: repeat;
  background-attachment: fixed;
}";
  return $css;
}
function generate_header_gradient($color = "black") {
  #two gradients: top to bottom, color to transparent black
  $host = $_SERVER['SERVER_NAME'];
  $hash = hash("sha256",$host);
  $a = "#".substr($hash,0,6);
  $b = "#".substr($hash,6,6);
  $c = "#".substr($hash,12,6);
  $d = "#".substr($hash,18,6);
  $colors1 = [ $a, $b, $c,$d ];
  $colors2 = [ $color, "#0000" ];
  $grad1 = "linear-gradient(to right, ".implode(", ",$colors1).") no-repeat border-box";
  $grad2 = "linear-gradient(to bottom, ".implode(", ",$colors2).") no-repeat border-box";
  $grad = "$grad2, $grad1";
  $bg = $grad;
  $css = "header section.title {
  background: $bg;
}";
  return $css;
}
if( isset($wiki->config["gradient_colors"]) ) {
  $bg_gradient_colors = $wiki->config["gradient_colors"];
  $bg_gradient_colors = explode(",",$bg_gradient_colors);
  $bg_gradient_colors = array_map(function($x) { return trim($x); },$bg_gradient_colors);
} else {
  $bg_gradient_colors = null;
}
$styles->addsty(generate_bg_gradient($bg_gradient_colors));
$styles->addsty(generate_header_gradient($action === "edit" ? "#007" : "#000"));
