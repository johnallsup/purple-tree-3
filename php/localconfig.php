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
if( isset($wiki->config["css"]) ) {
  $config_css = $wiki->config["css"];
  $csss = explode(",",$config_css);
  foreach( $csss as $css ) {
    $css = trim($css);
    $css_paths = $storage->find_root_to_leaf($subdir,$css);
    if( count($css_paths) > 0 ) {
      $css_output = "/* LOCAL STYLES */\n\n";
      $css_content = [];
      foreach($css_paths as $css_path) {
        $css1 = $storage->get($css_path);
        if( preg_match('@^/\* replace \*/\s*$@m',$css1) ) {
          $xs = explode("\n",$css1,2);
          $css1 = $xs[1];
          $css_content = [];
        }
        array_push($css_content,"/* /$css_path */\n".$css1);
      }
      $css_output .= implode("\n\n",$css_content);
      $styles->addsty(trim($css_output));
    }
  }
}
