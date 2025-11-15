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
#uncomment to debug
#error_reporting(E_ALL);
#ini_set('display_errors', '1');

define('PHP_DIR',dirname(__FILE__));

$uri = $_SERVER["REQUEST_URI"];
$urid = urldecode($uri);
if( $uri != $urid ) {
  header('Location: '.$urid, true, 303);
  exit();
}

$req = preg_replace('@^/+|/+$@','',$uri);
$req = explode("?",$req)[0];
$query = $_SERVER["QUERY_STRING"];
require(CONFIG_DIR.'/config.php'); // , defines SITE_DIR early in case of access denied
require_once('defs.php');

if( $req === "" ) {
  #echo "Root:";
  require("Do_Root.php");
} else if( preg_match('@^\.api(/.*)?$@',$req) ) {
  #echo "API:";
  require("Do_Api.php");
} else if( preg_match('@^(js|css)/@',$req) ) {
  #echo "Static:";
  require("Do_StaticFile.php");
} else if( preg_match('#^([a-zA-Z0-9_+@=-]+/+)*[a-zA-Z0-9_+%@=-]+/?$#',$req) ) {
  #echo "Page:";
  require("Do_Page.php");
} else if( preg_match('#^([a-zA-Z0-9_+@=-]+/+)*[a-zA-Z0-9_+%@=-]+\.[a-zA-Z0-9_+%@=\.-]*[a-zA-Z0-9_+%@=-]$#',$req) ) {
  #echo "File:";
  require("Do_File.php");
} else if( preg_match('#^([a-zA-Z0-9_+@=-]+/+)*\.[a-zA-Z0-9_+%@=-]+(/.*)?$#',$req) ) {
  #echo "Special:";
  require("Do_Special.php");
} else {
  echo "NotMatch:";
  require("Do_NotMatch.php");
}
