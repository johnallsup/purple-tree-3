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
if( $action === "edit" ) {
  $page_source = "";
} else {
  $t = "Page **".$pagename."** in *".$subdir."* does not exist.";
  if( is_auth("edit") ) {
    $pagename_u = urlencode($pagename);
    $t .= " [Edit to create]($pagename_u?action=edit)[e].";
    $t .= " or go to e.g. [[$pagename_u/home]][h] and create a page to make a subdirectory.";
  }
  $filenames = $storage->getregex($subdir,$pagename,"i");
  if( count($filenames) > 0 ) {
    $q = "";
    foreach($filenames as $fn) {
      if( preg_match('/(^.*)\.ptmd$/',$fn,$m) ) {
        $q .= "* [[".$m[1]."]]\n";
      }
    }
    if( $q !== "" ) {
      $t .= "\n\nPerhaps you mean:\n$q\n";
    }
  }
  $page_source = "$t";
}
require("RenderPage.php");
