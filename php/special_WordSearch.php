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
$headerTitle = "Word Search";

$search_words = explode("/",$url);
$base_url_components = [];
while( count($search_words) > 0 && $search_words[0][0] !== "." ) {
  array_push($base_url_components,array_shift($search_words));
}
$base_url = implode("/",$base_url_components);
if( count($search_words) === 0 ) {
  echo "No special 4298";
  exit();
}
$special = array_shift($search_words);

if( $case_sensitive ) {
  $by_word = from_data_json("by_word_cs");
} else {
  $by_word = from_data_json("by_word_ic");
}
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
$opts = [ "all" => $match_all ];
$opts_lookup = [ "a" => "all" ];
$ws = [];
// Compile list of search words
foreach($search_words as $word) {
  if( $word === "" || $word[0] !== "." ) {
    if( ! $case_sensitive ) {
      $ws[strtolower($word)] = true;
    } 
  } else {
    $word = substr($word,1);
    if( array_key_exists($word,$opts_lookup) ) {
      $word = $opts_lookup[$word];
    }
    $opts[$word] = true;
  }
}
$ws = array_keys($ws);
if( $opts["all"] && count($ws) > 1 ) {
  // AND rather than OR
  $w0 = array_shift($ws);
  $ms = [];
  if( array_key_exists($w0,$by_word) ) {
    $ms = [];
    foreach($by_word[$w0] as $i => $page) {
      $ms[$page] = true;
    }
    foreach($ws as $word) {
      if( ! array_key_exists($word,$by_word) ) {
        $ms = [];
        break;
      }
      $mss = $by_word[$word];
      $msm = [];
      foreach($mss as $page) {
        $msm[$page] = true;
      }
      foreach($ms as $page => $val) {
        if( ! array_key_exists($page,$msm) ) {
          unset($ms[$page]);
        }
      }
    }
  }
  $matches = $ms;
} else {
  // OR search -- match any word
  foreach($ws as $word) {
    if( $word !== "" && $word[0] === "." ) continue;
    if( ! $case_sensitive ) {
      $word = strtolower($word);
    }
    // blank word matches all (so we get a recursive dir)
    if( strlen($word) === 0 || array_key_exists($word,$by_word) ) {
      $words[$word] = true;
      foreach($by_word[$word] as $i => $page) {
        $matches[$page] = true;
      }
    } else {
      $nomatches[$word] = true;
    }
  }
}
$words = array_keys($words);
$nomatches = array_keys($nomatches);
$matches = array_keys($matches);

$html = "<div class='search-results'>\n";
foreach($nomatches as $word) {
  $word_regex_url = "/.wr/$word";
  if( $base_url !== "" ) {
    $word_regex_url = "/".$base_url.$word_regex_url;
  }
  $html .= "<p class='search-result no-match not-in-index'>Word '$word' not in the index.
   Try <a href='$word_regex_url'>a regex match against the sites word list</a>.</p>\n";
}
$t = "<ul class='search-result search-terms'>\n";
foreach($words as $word) {
  if( $word === "" ) {
    $word = "<i>(empty string)</i>";
  }
  $t .= "<li class='search-term'>$word</li>\n";
}
$t .= "</ul>\n";
$html .= $t;

$matches = array_filter($matches, function($x) use($l) {
  if( strlen($l) >= strlen($x) ) { return false; }
  if( substr($x,0,strlen($l)) !== $l ) { return false; }
  return true;
});
if( count($matches) == 0 ) {
  $html .= "<p class='search-result no-match no-matching-pages'>No matches.</p>\n";
} else {
  usort($matches,function($a,$b) use($storage) { return $storage->getmtime($a) < $storage->getmtime($b) ? 1 : -1; });

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
