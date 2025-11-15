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
function array_get($array,$key,$default) {
  if( array_key_exists($key,$array) ) {
    return $array[$key];
  } else {
    return $default;
  }
}

function pagename_with_action($action) {
  global $wiki;
  $pagename = $wiki->pagename;
  $a = $_GET;
  $a["action"] = $action;
  $s = [];
  foreach($a as $k => $v) {
    array_push($s,"$k=$v");
  }
  return "$pagename?".implode("&",$s);
}

function compute_page_size($src) {
  $nchars = strlen($src);
  $words = preg_split("@\s+@s",$src);
  $nwords = count($words);
  $lines = explode("\n",$src);
  $nlines = count($lines);
  return [ $nlines, $nwords, $nchars ];
}

function data_file_exists($fn) {
  $fn = DATA_DIR."/{$fn}";
  return file_exists($fn);
}
function from_data($fn) {
  $fn = DATA_DIR."/{$fn}";
  if( ! file_exists($fn) ) return null;
  $x = file_get_contents($fn);
  return $x;
}
function append_to_data($fn,$text) {
  $fn = DATA_DIR."/{$fn}";
  file_put_contents($fn,$text,FILE_APPEND);
}

function from_data_json($fn) {
  $x = from_data("{$fn}.json");
  if( $x === null ) return null;
  $d = json_decode($x,true);
  return $d;
}

function v($x,$m=null) { if(!is_null($m)) echo "$m\n"; var_dump($x); }

function get_mime_type_for_fpath($fpath) {
  # from Craig Tucker's answer here:
  # https://stackoverflow.com/questions/35299457/getting-mime-type-from-file-name-in-php 
  $xs = explode(".",$fpath);
  $ext = strtolower(array_pop($xs));

  $mimet = array( 
      'txt' => 'text/plain',
      'htm' => 'text/html',
      'html' => 'text/html',
      'php' => 'text/html',
      'css' => 'text/css',
      'js' => 'application/javascript',
      'json' => 'application/json',
      'xml' => 'application/xml',
      'swf' => 'application/x-shockwave-flash',
      'flv' => 'video/x-flv',

      // images
      'png' => 'image/png',
      'jpe' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'jpg' => 'image/jpeg',
      'gif' => 'image/gif',
      'bmp' => 'image/bmp',
      'ico' => 'image/vnd.microsoft.icon',
      'tiff' => 'image/tiff',
      'tif' => 'image/tiff',
      'svg' => 'image/svg+xml',
      'svgz' => 'image/svg+xml',

      // archives
      'zip' => 'application/zip',
      'rar' => 'application/x-rar-compressed',
      'exe' => 'application/x-msdownload',
      'msi' => 'application/x-msdownload',
      'cab' => 'application/vnd.ms-cab-compressed',

      // audio/video
      'mp3' => 'audio/mpeg',
      'qt' => 'video/quicktime',
      'mov' => 'video/quicktime',

      // adobe
      'pdf' => 'application/pdf',
      'psd' => 'image/vnd.adobe.photoshop',
      'ai' => 'application/postscript',
      'eps' => 'application/postscript',
      'ps' => 'application/postscript',

      // ms office
      'doc' => 'application/msword',
      'rtf' => 'application/rtf',
      'xls' => 'application/vnd.ms-excel',
      'ppt' => 'application/vnd.ms-powerpoint',
      'docx' => 'application/msword',
      'xlsx' => 'application/vnd.ms-excel',
      'pptx' => 'application/vnd.ms-powerpoint',


      // open office
      'odt' => 'application/vnd.oasis.opendocument.text',
      'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
  );

  if (isset( $mimet[$ext] )) {
    return $mimet[$ext];
  } else {
    return 'application/octet-stream';
  }
}
