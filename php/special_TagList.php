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
/*
/path/to/.tags
displays a list of tags in pages under that folder
*/

require("PageLike.php");
$headerTitle = "Tag List";

//$search_words = $ur;
$tag_list = from_data_json("tag_lists");
if( isset($tag_list[$l]) ) {
  $tags = $tag_list[$l];
} else {
  $tags = null;
} 

$html = "<div class='search-results'>\n";
if( !is_null($tags) ) {
  $html .= "<ul>\n";
  foreach($tags as $tag) {
    $href = "/$l/.t/$tag";
    $html .= "<li><a href='$href'>$tag</a></li>\n";
  }
  $html .= "</ul>\n";
} else {
  $html .= "<p>No tags in $l</p>\n";
}

$page_rendered = $html;

require("RenderPageLike.php");
