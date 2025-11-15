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
$headerTitle = "Tag Search";

$search_words = $ur;
$by_word = from_data_json("by_tag");
if( is_null($by_word) ) {
  http_response_code(500);
  echo "Indexes not built";
  exit(); 
}
if( count($search_words) == 0 ) {
  require("WordSearchPage.php");
  return;
}
$matches = [];
$nomatches = [];
$words = [];
if( $match_all && count($search_words) > 0 ) {
# Strategy:
# first word, construct assoc array
# word -> int, init to 1
# other words, if word in array, increment
# then filter array for those whose value is #words
  # Copy strategy over to .wa
  $sws = $search_words;
  $nws = count($sws);
  if( $nws > 0 ) {
    $w1 = array_shift($sws);
    if( array_key_exists($w1,$by_word) ) {
      $xs = $by_word[$w1];
      $ms = [];
      foreach($xs as $k => $v) {
        $ms[$v] = 1;
      }
      foreach($sws as $w) {
        if( array_key_exists($w,$by_word) ) {
          foreach($by_word[$w] as $k => $v) {
            if( array_key_exists($v,$ms) ) {
              $ms[$v] += 1;
            }
          }
        }
      }
      foreach($ms as $k => $v) {
        if( $v == $nws ) {
          $matches[$k] = true;
        }
      }
    }
  } 
} else {
  # match at least one
  foreach($search_words as $word) {
    if( array_key_exists($word,$by_word) ) {
      $words[$word] = true;
      foreach($by_word[$word] as $i => $page) {
        $matches[$page] = true;
      }
    } else {
      $nomatches[$word] = true;
    }
  }
}

$html = "<div class='search-results'>\n";
foreach($nomatches as $word => $val) {
  $html .= "<p class='search-result no-match not-in-index'>Tag '$word' not in the index.</p>\n";
}
$t = "<ul class='search-result search-terms'>\n";
foreach($words as $word => $val) {
  $t .= "<li class='search-term'>$word</li>\n";
}
$t .= "</ul>\n";
$html .= $t;

// $l is left part of url (scope)
$matches = array_keys($matches);
$matches = array_filter($matches, function($x) use($l) {
  if( strlen($l) >= strlen($x) ) { return false; }
  if( substr($x,0,strlen($l)) !== $l ) { return false; }
  return true;
});
if( count($matches) == 0 ) {
  $html .= "<p class='search-result no-match no-matching-pages'>No matches.</p>\n";
} else {
  sort($matches);
  $html .= "<ol>\n";
  foreach($matches as $page) {
    $page = preg_replace("@\\.".PAGE_EXT."$@","",$page);
    $cs = explode("/",$page);
    $pn = array_pop($cs);
    $dn = implode("/",$cs);
    $t = "<a class='pagelink' href='/$page'>$pn</a> in ";
    if( $dn == "" ) {
      $t .= "<a class='dirlink' href='/'>root</a>";
    } else {
      $t .= "<a class='dirlink' href='/$dn/home'>$dn</a>";
    }
    $html .= "  <li>$t</li>\n";
  }
  $html .= "</ol>\n";
}

$page_rendered = $html;

require("RenderPageLike.php");
