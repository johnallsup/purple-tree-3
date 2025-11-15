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
if( isset($wiki->config["favicon"]) ) {
  $favicon = $wiki->config["favicon"];
  if( $favicon !== "" ) {
    $favicon_href = null;
    if( $favicon[0] == "/" ) {
      $favicon_href = $favicon;
    } else {
      $favicons = $storage->find_leaf_to_root($subdir,$favicon);
      if( count($favicons) > 0 ) {
        $favicon_href = "/".$favicons[0];
      }
    }
    if( $favicon_href !== null ) {
      echo "<link rel='icon' type='image/x-icon' href='$favicon_href'>";
    }
  }
}
