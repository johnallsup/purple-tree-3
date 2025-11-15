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
function make_tag($x) {
  $x = ltrim($x,"#");
  return "<span class='hashtag'><a href='/.t/$x'>".$x."</a></span>"; 
}
if( isset($meta["tags"]) ) {
  $tags = $meta["tags"];
  $tags = preg_split("/\s+/",trim($tags));
  $tags = array_map("make_tag",$tags);
  $tags = implode(" ",$tags);
  echo "<span class='hashtags'>$tags</span>";
}
