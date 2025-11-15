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
$req = urldecode($req);
$fpath = STATIC_DIR."/".$req;
if( file_exists($fpath) ) {
  require('util_getmime.php');
  $mime = get_mime_type_for_fpath($fpath);
  http_response_code(200);
  header("Content-type: $mime");
  readfile($fpath);
  exit;
} else {
  require('error_404.php');
  exit;
}
  

